<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // CRITICAL: DO NOT CHANGE THIS LINE!
        // Must use ['web', 'auth'] NOT ['auth:web']
        //
        // Why? Because:
        // - 'web' middleware group includes session handling, cookies, CSRF
        // - Without 'web', sessions don't work and you get 401 errors
        // - ['auth:web'] alone = NO SESSION = 401 Unauthorized
        // - ['web', 'auth'] = WITH SESSION = Works correctly
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        require base_path('routes/channels.php');
    }
}
