<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class SchedulerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }
        
        // This will execute only when we're handling a web request
        register_shutdown_function(function () {
            try {
                Log::info('Running post processor on server shutdown');
                Artisan::call('app:process-scheduled-posts');
            } catch (\Exception $e) {
                Log::error('Failed to run post processor: ' . $e->getMessage());
            }
        });
        
        // Also set up a simple tick function if possible
        if (function_exists('register_tick_function')) {
            // This is a counter to avoid running too frequently
            $GLOBALS['scheduler_counter'] = 0;
            
            register_tick_function(function () {
                // Only run once every 60 seconds
                if (!isset($GLOBALS['scheduler_last_run']) || (time() - $GLOBALS['scheduler_last_run']) >= 60) {
                    $GLOBALS['scheduler_last_run'] = time();
                    try {
                        Artisan::call('app:process-scheduled-posts');
                    } catch (\Exception $e) {
                        // Silently fail
                    }
                }
            });
        }
    }
} 