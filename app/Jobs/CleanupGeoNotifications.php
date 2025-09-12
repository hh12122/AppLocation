<?php

namespace App\Jobs;

use App\Services\GeoNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupGeoNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120; // 2 minutes

    /**
     * Execute the job.
     */
    public function handle(GeoNotificationService $geoNotificationService): void
    {
        Log::info('Starting geo-notification cleanup job');
        
        try {
            $geoNotificationService->cleanup();
            Log::info('Geo-notification cleanup job completed successfully');
        } catch (\Exception $e) {
            Log::error('Geo-notification cleanup job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Geo-notification cleanup job failed permanently', [
            'exception' => $exception->getMessage(),
        ]);
    }
}