<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGeoNotifications;
use Illuminate\Console\Command;

class ProcessGeoNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'geo-notifications:process
                            {--immediate : Process notifications immediately instead of queuing}';

    /**
     * The console command description.
     */
    protected $description = 'Process pending geo-location notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting geo-notification processing...');

        if ($this->option('immediate')) {
            // Process immediately
            $job = new ProcessGeoNotifications();
            $job->handle(app(\App\Services\GeoNotificationService::class));
            $this->info('Geo-notifications processed immediately.');
        } else {
            // Queue the job
            ProcessGeoNotifications::dispatch();
            $this->info('Geo-notification processing job queued.');
        }

        return self::SUCCESS;
    }
}