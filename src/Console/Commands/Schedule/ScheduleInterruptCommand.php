<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Schedule;

use Illuminate\Support\Facades\Date;
use Maginium\Framework\Cache\Interfaces\FactoryInterface;
use Maginium\Framework\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command to interrupt the current schedule run.
 *
 * This command sends an interrupt signal for the ongoing schedule execution
 * by updating a cache key that is monitored during the scheduler's run.
 */
#[AsCommand(name: 'schedule:interrupt')]
class ScheduleInterruptCommand extends Command
{
    /**
     * The console command description.
     *
     * Provides a brief summary of the command's functionality.
     *
     * @var string|null
     */
    protected ?string $description = 'Interrupt the current schedule run';

    /**
     * Execute the console command.
     *
     * This method is the entry point for the command's execution logic.
     * It sets an interrupt flag in the cache, signaling the scheduler to stop its current run.
     *
     * @param FactoryInterface $cache The cache factory instance used to store the interrupt signal.
     *
     * @return void
     */
    public function handle(FactoryInterface $cache): void
    {
        // Store the interrupt signal in the cache with an expiration time of the current minute's end.
        $cache->store()->put(
            'illuminate:schedule:interrupt', // Key to signal schedule interruption.
            true, // Value indicating the interrupt status.
            Date::now()->endOfMinute(), // Expiration time set to the end of the current minute.
        );

        // Output a message indicating the interrupt signal has been broadcasted.
        $this->components->info('Broadcasting schedule interrupt signal.');
    }
}
