<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Commands;

use Magento\Email\Model\Template\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Maginium\Foundation\Enums\ExecutableTypes;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Application\Application;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\AppState;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Prompts;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for serving React-based email templates.
 *
 * This command collects email files from the app and vendor directories,
 * copies them to the var/tmp/emails directory, and triggers the email dev process.
 */
#[AsCommand('email:build')]
class ReactEmailBuild extends Command
{
    /**
     * @var Config
     */
    protected Config $emailConfig;

    /**
     * The Output directory.
     */
    protected string $distDir;

    /**
     * A brief description of the static content deploy command.
     */
    protected ?string $description = 'Build React-based email templates by deploying them to the appropriate directory.';

    /**
     * Handle the email serving execution.
     *
     * This method is responsible for collecting email files, linking them to
     * `var/tmp/emails`, and triggering the email build process to prepare emails for serving.
     *
     * @return int The exit code indicating the success or failure of the email serving process.
     */
    public function handle(Config $emailConfig): int
    {
        try {
            // Set emailConfig value
            $this->emailConfig = $emailConfig;

            // Step 1: Prepare the email directory (creates it if it doesn't exist)
            Prompts::spinner(function() {
                // 3-second delay
                sleep(3);

                $this->prepareDirectory();
            }, '🛠️ Preparing email directory...');

            // Step 2: Collect email files from app and vendor directories
            $collectedFiles = Prompts::spinner(function() {
                // 3-second delay
                sleep(3);

                return $this->collectFiles();
            }, '📦 Collecting email files from source directories...');

            // Step 3: Link collected email files to the target directory
            Prompts::spinner(function() use ($collectedFiles) {
                // 3-second delay
                sleep(3);

                $this->linkFiles($collectedFiles);
            }, '📂 Linking email files to target directory...');

            // Step 4: Output success message to the console
            // Prompts::success('React emails deployed successfully!');
            $this->outputEmailBuildCommand();

            // Return success exit code (0 indicates successful execution)
            return self::SUCCESS;
        } catch (Exception $e) {
            // Handle any exceptions that occur during the process
            Prompts::error('Error: ' . $e->getMessage());

            // Return failure exit code (1 indicates failure)
            return self::FAILURE;
        }
    }

    /**
     * Prepare the target directory for email files.
     *
     * Checks if the target directory exists, and creates it if necessary.
     */
    protected function prepareDirectory(): void
    {
        // Get the base directory (VAR_DIR) where email files will be stored
        $varDir = Filesystem::getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath();

        // Construct the full path to the target email directory
        $this->distDir = Path::join($varDir, DirectoryList::TMP, 'emails');

        // Check if the target directory exists
        if (! Filesystem::exists($this->distDir)) {
            // Create the directory if it doesn't exist
            Filesystem::makeDirectory($this->distDir);
        }
    }

    /**
     * Collect email files based on predefined patterns.
     *
     * Scans the source directories and collects email files that match the specified patterns.
     * Supports dynamic directory structures like "view/base", "view/admin", etc.
     *
     * @return Collection A collection of email files with their vendor and file paths.
     */
    protected function collectFiles(): Collection
    {
        // Set the frontend context for email templates
        AppState::setFrontend();

        // Get available email templates
        $emailTemplates = $this->emailConfig->getAvailableTemplates();

        // Initialize collection to store the collected files
        $collectedFiles = Collection::make();

        // Loop through each template and collect React files
        foreach ($emailTemplates as $template) {
            // Get the type of the email template
            $type = $this->emailConfig->getTemplateType($template['value']);

            // If it's a 'react' template, add it to the collection
            if ($type === 'react') {
                $collectedFiles->push(DataObject::make([
                    'vendor' => $template['group'],  // Vendor name (e.g., Maginium_Test)
                    'file' => $this->emailConfig->getTemplateFilename($template['value']),  // Path to the template file
                ]));
            }
        }

        // Return the collection of email files
        return $collectedFiles;
    }

    /**
     * Link email files to the target directory.
     *
     * This method processes each collected file, determines its target directory,
     * and creates a symlink in the target location.
     */
    protected function linkFiles(Collection $collectedFiles): void
    {
        // Loop through each file in the collected files
        foreach ($collectedFiles as $fileData) {
            // Get the full path to the source email file
            $file = $fileData->getFile();

            // Get the vendor name associated with the file (e.g., Maginium_Test)
            $vendor = $fileData->getVendor();

            // Step 1: Determine the target directory where the symlink will be created
            $targetDir = $this->getTargetDirectory($vendor);

            // Step 2: Get the relative path of the file from the base email folder
            $relativePath = $this->getRelativePath($file);

            // Step 3: Extract the target path by checking if the file is under the 'email' or 'emails' directories
            $targetPath = $this->getTargetPath($relativePath, $targetDir);

            // Proceed only if the path under 'email' or 'emails' is found
            if ($targetPath) {
                // Step 4: Ensure the target directory exists
                $this->createDirectory($targetPath);

                // Step 5: Create the symlink from the source file to the target directory
                $this->createSymlink($file, $targetPath);
            }
        }
    }

    /**
     * Output the email dev command for manual execution.
     *
     * This method prepares the command to compile the React-based email templates
     * and outputs it for manual execution by the user.
     */
    protected function outputEmailBuildCommand(): void
    {
        // Format the command for the email dev process
        $command = Application::formatExecutableCommandString(['email', 'dev'], executionType: ExecutableTypes::YARN);

        // Prepare the arguments for the command
        $args = "--dir={$this->distDir}";

        // Combine the command with its arguments
        $fullCommand = Str::format('%s %s', $command, $args);

        // Output the information to the user with emojis
        Prompts::info('✨ To build the emails, please run the following command manually:');
        Prompts::info("💻 {$fullCommand}");
        Prompts::info('ℹ️  This allows you to monitor the process and handle any issues directly.');
    }

    /**
     * Get the target directory to store email files.
     *
     * @param string $vendor The vendor name.
     *
     * @return string The target directory for the emails.
     */
    private function getTargetDirectory(string $vendor): string
    {
        return Path::join($this->distDir, Str::replace('_', '/', $vendor) . '/emails');
    }

    /**
     * Get the relative path of the email file from the base path.
     *
     * @param string $file The full path of the file.
     *
     * @return string The relative path from the base path.
     */
    private function getRelativePath(string $file): string
    {
        return Str::replace(base_path(), '', $file);
    }

    /**
     * Get the target path for the symlink based on the relative path.
     *
     * @param string $relativePath The relative path of the file.
     * @param string $targetDir The target directory for the emails.
     *
     * @return string|null The target path, or null if no valid email folder is found.
     */
    private function getTargetPath(string $relativePath, string $targetDir): ?string
    {
        // Split the relative path into parts
        $pathParts = explode(DIRECTORY_SEPARATOR, $relativePath);

        // Find the index of the 'email' or 'emails' folder
        $emailFolderIndex = $this->getEmailFolderIndex($pathParts);

        // If no 'email' or 'emails' folder is found, return null
        if ($emailFolderIndex === false) {
            return null;
        }

        // Slice the path to get subdirectories, excluding the file name
        $subDirectories = Arr::slice($pathParts, $emailFolderIndex + 1, -1);

        // Return the appropriate target path with prebuildd subdirectory structure
        return empty($subDirectories)
            ? $targetDir
            : Path::join($targetDir, implode(DIRECTORY_SEPARATOR, $subDirectories));
    }

    /**
     * Find the index of the 'email' or 'emails' folder in the path.
     *
     * @param array $pathParts The parts of the relative path.
     *
     * @return int|false The index of the 'email' or 'emails' folder, or false if not found.
     */
    private function getEmailFolderIndex(array $pathParts)
    {
        return Arr::search('email', $pathParts) !== false
            ? Arr::search('email', $pathParts)
            : Arr::search('emails', $pathParts);
    }

    /**
     * Create the target directory if it doesn't exist.
     *
     * @param string $targetPath The target directory path.
     *
     * @return void
     */
    private function createDirectory(string $targetPath): void
    {
        // Check if the target path exists
        if (! Filesystem::exists($targetPath)) {
            // If not create the target directory
            Filesystem::makeDirectory($targetPath);
        }
    }

    /**
     * Create the symlink from the source file to the target path.
     *
     * @param string $file The source file.
     * @param string $targetPath The target directory where the symlink will be created.
     *
     * @return void
     */
    private function createSymlink(string $file, string $targetPath): void
    {
        // Joining paths
        $symlinkTarget = Path::join($targetPath, basename($file));

        // Remove the existing symlink or file if it exists
        if (Filesystem::exists($symlinkTarget)) {
            Filesystem::delete($symlinkTarget);
        }

        // Create the symlink
        Filesystem::copy($file, $symlinkTarget);
    }
}
