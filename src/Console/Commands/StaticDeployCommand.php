<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Enums\Commands;
use Maginium\Framework\Console\Enums\MagentoCommands;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\StoreManager;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for deploying static content in Magento 2.
 *
 * This command deploys static content (such as CSS, JavaScript, images, etc.) to the appropriate directories.
 * It is typically used after making changes to static assets or when preparing a Magento instance for production.
 */
#[AsCommand(Commands::STATIC_CONTENT_DEPLOY)]
class StaticDeployCommand extends Command
{
    /**
     * A brief description of the static content deploy command.
     */
    protected ?string $description = 'Deploy static content for Magento 2.';

    /**
     * The signature of the static content deploy command.
     */
    protected ?string $signature = '{--a|all : Deploy static content for all stores.}
        {--s|store= : Deploy static content for a specific store.}';

    /**
     * Handle the static content deployment execution.
     *
     * This method is responsible for executing the static content deployment logic. It deploys the necessary
     * static files for Magento 2 to make the website frontend ready. This is typically required when building
     * Magento for production or when assets like CSS, JS, or images have changed.
     *
     * @return int The exit code indicating the success or failure of the static content deployment.
     */
    public function handle(): int
    {
        try {
            // Check if the '--force' option is provided
            $forceOption = $this->options()->getForce();

            // Initialize the progress bar for deploying static content
            $progress = $this->progress('Deploying static content...', 5);

            // Start the progress bar
            $progress->start();

            // Step 1: Remove old static files to ensure a clean deployment
            $progress->advance();
            Filesystem::delete('pub/static/*'); // Delete old static content

            // Step 2: Create necessary directories if they don't exist
            $progress->advance();
            Filesystem::makeDirectory('pub/static');

            // Step 3: If --all is provided, deploy static content for all stores
            if ($this->options()->getAll()) {
                $progress->advance();
                $this->call(MagentoCommands::STATIC_CONTENT_DEPLOY); // Deploy static content for all stores
            } else {
                // Fetch all stores from the StoreManager
                $stores = StoreManager::getStores();
                $storeOptions = [];

                // Prepare store options for multi-select
                foreach ($stores as $store) {
                    $storeOptions[$store->getCode()] = $store->getName();
                }

                // Use multiselect to allow the user to select stores
                $selectedStores = $this->multiselect(
                    label: 'Which stores would you like to deploy static content for?',
                    options: $storeOptions,
                );

                // Step 4: Deploy static content for selected stores
                if (! empty($selectedStores)) {
                    foreach ($selectedStores as $storeId) {
                        $progress->advance();
                        $this->call('setup:static-content:deploy', ['--store' => $storeId, ['--force', $forceOption]]);
                    }
                } else {
                    ConsoleOutput::error('No stores selected for deployment.');

                    return self::FAILURE;
                }
            }

            // Step 5: Ensure permissions are correctly set for static content
            $progress->advance();
            Filesystem::chmod('pub/static', 0777); // Set appropriate permissions

            // Finish the progress bar after all steps are completed
            $progress->finish();

            // Return success exit code (0 indicates success)
            return self::SUCCESS;
        } catch (Exception $e) {
            // Catch any exceptions and handle the error
            // Output the error message to the console
            ConsoleOutput::error('Error: ' . $e->getMessage());

            // Return failure exit code (1 indicates failure)
            return self::FAILURE;
        }
    }
}
