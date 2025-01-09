<?php

declare(strict_types=1);

namespace Maginium\Framework\Filesystem\Drivers;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use DateTimeInterface;
use Illuminate\Support\Traits\Conditionable;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Http\File;
use Maginium\Framework\Http\UploadedFile;
use Maginium\Framework\Support\Arr;
use Override;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * AwsS3V3Adapter is a custom adapter for integrating with AWS S3 using the Flysystem library.
 *
 * This adapter extends the base `FilesystemAdapter` to provide additional functionality,
 * such as generating URLs, temporary URLs, and handling S3-specific operations.
 */
class AwsS3V3 extends DriverFilesystem
{
    use Conditionable;

    /**
     * The AWS S3 client instance.
     *
     * @var S3Client
     */
    protected S3Client $client;

    /**
     * Constructor to initialize the AwsS3V3FilesystemAdapter.
     *
     * @param FilesystemOperator $driver The filesystem operator.
     * @param S3Adapter $adapter The Flysystem S3 adapter.
     * @param array $config Configuration options for the adapter.
     * @param S3Client $client The AWS S3 client instance.
     */
    public function __construct(
        FilesystemOperator $driver,
        S3Adapter $adapter,
        array $config,
        S3Client $client,
    ) {
        parent::__construct($driver, $adapter, $config);

        // Assign the AWS S3 client to the adapter.
        $this->client = $client;
    }

    /**
     * Retrieve the URL for a file located at the specified path.
     *
     * @param string $path The file path in the S3 bucket.
     *
     * @throws RuntimeException If the configuration is invalid.
     *
     * @return string The public URL of the file.
     */
    public function url($path): string
    {
        // Check if a base URL is defined in the configuration and use it if available.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl(
                $this->config['url'],
                $this->prefixer->prefixPath($path),
            );
        }

        // Generate the S3 URL for the file.
        return $this->client->getObjectUrl(
            $this->config['bucket'],
            $this->prefixer->prefixPath($path),
        );
    }

    /**
     * Determine if temporary URLs can be generated.
     *
     * @return bool True if temporary URLs are supported.
     */
    public function providesTemporaryUrls(): bool
    {
        return true;
    }

    /**
     * Generate a temporary URL for the file at the specified path.
     *
     * @param string $path The file path in the S3 bucket.
     * @param DateTimeInterface $expiration The expiration time of the URL.
     * @param array $options Additional options for generating the URL.
     *
     * @return string The temporary URL.
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        // Create a command to fetch the object from S3.
        $command = $this->client->getCommand('GetObject', Arr::merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $options));

        // Generate a presigned request for the command.
        $uri = $this->client->createPresignedRequest($command, $expiration)->getUri();

        // If a custom base URL is configured, replace it in the URI.
        if (isset($this->config['temporary_url'])) {
            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
        }

        return (string)$uri;
    }

    /**
     * Generate a temporary upload URL for the specified file path.
     *
     * @param string $path The file path in the S3 bucket.
     * @param DateTimeInterface $expiration The expiration time of the upload URL.
     * @param array $options Additional options for generating the URL.
     *
     * @return array An array containing the upload URL and required headers.
     */
    public function temporaryUploadUrl(string $path, DateTimeInterface $expiration, array $options = []): array
    {
        // Create a command to put the object in S3.
        $command = $this->client->getCommand('PutObject', Arr::merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $options));

        // Generate a presigned request for the command.
        $signedRequest = $this->client->createPresignedRequest($command, $expiration);

        $uri = $signedRequest->getUri();

        // If a custom base URL is configured, replace it in the URI.
        if (isset($this->config['temporary_url'])) {
            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
        }

        return [
            'url' => (string)$uri,
            'headers' => $signedRequest->getHeaders(),
        ];
    }

    /**
     * Retrieve the underlying AWS S3 client.
     *
     * @return S3Client The S3 client instance.
     */
    public function getClient(): S3Client
    {
        return $this->client;
    }

    /**
     * Write contents to a file after ensuring the bucket exists.
     *
     * @param string $path The path to the file.
     * @param mixed $contents The contents to write (string, resource, or file instance).
     * @param mixed $options Options for writing, such as visibility.
     *
     * @throws UnableToWriteFile|UnableToSetVisibility If writing fails and exceptions are enabled.
     *
     * @return string|bool Returns the file path if successful, or false on failure.
     */
    #[Override]
    public function put($path, $contents, $options = []): bool
    {
        // Convert options to an array if it's a string.
        $options = is_string($options) ? ['visibility' => $options] : (array)$options;

        // Extract bucket name from the path (adjust depending on your driver implementation).
        $bucketName = $this->config['bucket'];

        // Check if the bucket exists before proceeding.
        if (! $this->bucketExists($bucketName)) {
            if ($this->throwsExceptions()) {
                throw new UnableToWriteFile("The specified bucket '{$bucketName}' does not exist.");
            }

            return false;
        }

        // Handle file or uploaded file instances.
        if ($contents instanceof File || $contents instanceof UploadedFile) {
            return $this->putFile($path, $contents, $options);
        }

        try {
            // Handle streams and resources.
            if ($contents instanceof StreamInterface) {
                $this->driver->writeStream($path, $contents->detach(), $options);

                return true;
            }

            // Write the contents based on their type.
            is_resource($contents)
                ? $this->driver->writeStream($path, $contents, $options)
                : $this->driver->write($path, $contents, $options);
        } catch (UnableToWriteFile|UnableToSetVisibility $e) {
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }

    /**
     * Check if a bucket exists using the S3Client.
     *
     * This method verifies whether the specified bucket exists in the AWS S3 storage.
     *
     * @param string $bucketName The name of the bucket to check.
     *
     * @return bool True if the bucket exists, false otherwise.
     */
    protected function bucketExists(string $bucketName): bool
    {
        try {
            // Use the S3Client's HeadBucket operation to check bucket existence.
            $this->getClient()->headBucket([
                'Bucket' => $bucketName,
            ]);

            return true;
        } catch (AwsException $e) {
            // If the error is not a 404 (bucket not found), log the exception.
            if ($e->getStatusCode() !== 404) {
                // Log the error (you can replace this with your logger implementation).
                error_log('Error checking bucket existence: ' . $e->getMessage());
            }

            return false;
        }
    }
}
