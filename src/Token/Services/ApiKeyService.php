<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\Token\Services;

use Exception;
use Magento\Integration\Api\IntegrationServiceInterface as BaseApiKeyServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Integration as ApiKey;
use Pixicommerce\Foundation\Exceptions\InvalidArgumentException;
use Pixicommerce\Foundation\Exceptions\LocalizedException;
use Pixicommerce\Framework\Support\Facades\Config;
use Pixicommerce\Framework\Token\Interfaces\ApiKeyServiceInterface;

/**
 * Service class for managing Magento 2 API apiKeys and access tokens.
 *
 * This class handles creating apiKeys, generating access tokens,
 * and managing configuration settings for API keys in Magento 2.
 */
class ApiKeyService implements ApiKeyServiceInterface
{
    /**
     * @var OauthServiceInterface Manages OAuth tokens for apiKeys.
     */
    private OauthServiceInterface $oauthService;

    /**
     * @var BaseApiKeyServiceInterface Handles API key-related apiKey operations.
     */
    private BaseApiKeyServiceInterface $apiKeyService;

    /**
     * Constructor to inject required dependencies.
     *
     * @param OauthServiceInterface $oauthService OAuth service for token generation.
     * @param BaseApiKeyServiceInterface $apiKeyService Base apiKey service for API keys.
     */
    public function __construct(
        OauthServiceInterface $oauthService,
        BaseApiKeyServiceInterface $apiKeyService,
    ) {
        $this->oauthService = $oauthService;
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * Retrieves an existing API key by its name.
     *
     * @param string|null $name Name of the API key. Uses default configuration if not provided.
     *
     * @throws LocalizedException Throws when the API key is not found or misconfigured.
     *
     * @return ApiKey Returns the API key instance.
     */
    public function get(?string $name = null): ApiKey
    {
        $name ??= $this->getDefaultApiKeyName(); // Use default name if none is provided.

        // Find the API key by name using the base API key service.
        $apiKey = $this->apiKeyService->findByName($name);

        if (! $apiKey || ! $apiKey->getId()) {
            // Throw an exception if the API key is not found.
            throw LocalizedException::make(__('API key not found or improperly configured.'));
        }

        // Set access token data to the model
        $apiKey->setData(self::TOKEN, value: $this->getAccessToken($apiKey));

        return $apiKey;
    }

    /**
     * Creates a new API key and generates an access token for it.
     *
     * @param string|null $name Optional name for the API key. Uses default configuration if not provided.
     * @param string|null $email Optional email for the API key. Uses default configuration if not provided.
     * @param array $permissions List of permissions for the API key. Defaults to all permissions.
     *
     * @throws LocalizedException Throws when API key creation or token generation fails.
     *
     * @return string|null Returns the generated access token, or null if creation fails.
     */
    public function create(?string $name = null, ?string $email = null, array $permissions = ['*']): ?string
    {
        try {
            // Step 1: Prepare the data required for API key creation.
            $apiKeyData = $this->prepareData($name, $email, $permissions);

            // Step 2: Create the API key using the base service.
            $apiKey = $this->apiKeyService->create($apiKeyData);

            if (! $apiKey || ! $apiKey->getId()) {
                // Throw an exception if the API key creation fails.
                throw LocalizedException::make(__('API key creation failed.'));
            }

            // Step 3: Generate and return the associated access token.
            return $this->generateAccessToken($apiKey);
        } catch (Exception $e) {
            // Wrap the exception into a localized exception with user-friendly messaging.
            throw LocalizedException::make(__('Failed to create API key: %1', $e->getMessage()), $e);
        }
    }

    /**
     * Generates an access token for a given API key.
     *
     * @param ApiKey $apiKey The API key for which to generate the token.
     *
     * @throws LocalizedException Throws when the consumer ID is missing or token generation fails.
     *
     * @return string|null Returns the generated access token, or null if generation fails.
     */
    private function generateAccessToken(ApiKey $apiKey): ?string
    {
        if (! $apiKey->getConsumerId()) {
            // Ensure the API key has an associated consumer ID.
            throw LocalizedException::make(__('API key consumer ID is missing.'));
        }

        // Create the access token via the OAuth service.
        $accessToken = $this->oauthService->createAccessToken((int)$apiKey->getConsumerId(), true);

        if (! $accessToken) {
            // Throw an exception if token generation fails.
            throw LocalizedException::make(__('Failed to generate access token.'));
        }

        // Activate the API key and save changes.
        $this->apiKeyService->update([ApiKey::STATUS => ApiKey::STATUS_ACTIVE]);

        // Retrieve and return the generated access token.
        return $this->getAccessToken($apiKey);
    }

    /**
     * Retrieves the access token associated with a given API key.
     *
     * @param ApiKey $apiKey The API key instance.
     *
     * @return string|null Returns the access token, or null if not found or inactive.
     */
    private function getAccessToken(ApiKey $apiKey): ?string
    {
        if ($apiKey->getStatus() === ApiKey::STATUS_ACTIVE && $apiKey->getConsumerId()) {
            // Fetch the token from the OAuth service.
            $token = $this->oauthService->getAccessToken((int)$apiKey->getConsumerId());

            return $token ? $token->getToken() : null;
        }

        return null; // Return null if the API key is inactive or lacks a consumer ID.
    }

    /**
     * Prepares data required for creating an API key.
     *
     * @param string|null $name Name for the API key.
     * @param string|null $email Email associated with the API key.
     * @param array $permissions List of permissions for the API key.
     *
     * @throws InvalidArgumentException Throws if the email format is invalid.
     *
     * @return array Returns the prepared data for API key creation.
     */
    private function prepareData(?string $name = null, ?string $email = null, array $permissions = ['*']): array
    {
        $name ??= $this->getDefaultApiKeyName(); // Use default name if not provided.
        $email ??= $this->getDefaultDeveloperEmail(); // Use default email if not provided.

        if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Validate the email format.
            throw InvalidArgumentException::make(__('Invalid email format: %1', $email));
        }

        $hasAllPermissions = $permissions === ['*']; // Check if all permissions are granted.

        // Return the prepared API key data.
        return [
            ApiKey::NAME => $name,
            ApiKey::EMAIL => $email,
            ApiKey::STATUS => ApiKey::STATUS_INACTIVE,
            ApiKey::SETUP_TYPE => ApiKey::TYPE_MANUAL,
            static::ALL_RESOURCES => $hasAllPermissions,
            static::RESOURCES => $hasAllPermissions ? [static::BACKEND_POLICY] : $permissions,
        ];
    }

    /**
     * Retrieves the default API key name from configuration.
     *
     * @return string Returns the default API key name.
     */
    private function getDefaultApiKeyName(): string
    {
        return Config::getString(static::XML_PATH_CONFIG_INTEGRATION_NAME);
    }

    /**
     * Retrieves the default developer email from configuration.
     *
     * @return string Returns the default developer email.
     */
    private function getDefaultDeveloperEmail(): string
    {
        return Config::getString(static::XML_PATH_CONFIG_DEVELOPER_EMAIL);
    }
}
