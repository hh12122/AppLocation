<?php

namespace App\Console\Commands;

use App\Jobs\CleanupGeoNotifications;
use Illuminate\Console\Command;

class CleanupGeoNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'geo-notifications:cleanup
                            {--immediate : Clean up immediately instead of queuing}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old geo-location notifications and user locations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting geo-notification cleanup...');

        if ($this->option('immediate')) {
            // Process immediately
            $job = new CleanupGeoNotifications();
            $job->handle(app(\App\Services\GeoNotificationService::class));
            $this->info('Geo-notifications cleaned up immediately.');
        } else {
            // Queue the job
            CleanupGeoNotifications::dispatch();
            $this->info('Geo-notification cleanup job queued.');
        }

        return self::SUCCESS;
    }
}