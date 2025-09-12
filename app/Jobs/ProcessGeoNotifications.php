<?php

namespace App\Jobs;

use App\Services\GeoNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGeoNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Execute the job.
     */
    public function handle(GeoNotificationService $geoNotificationService): void
    {
        Log::info('Starting geo-notification processing job');
        
        try {
            $geoNotificationService->sendLocationBasedNotifications();
            Log::info('Geo-notification processing job completed successfully');
        } catch (\Exception $e) {
            Log::error('Geo-notification processing job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Geo-notification processing job failed permanently', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}