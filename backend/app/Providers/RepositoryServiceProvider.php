<?php

namespace App\Providers;

use App\Interfaces\PlatformRepositoryInterface;
use App\Interfaces\PostRepositoryInterface;
use App\Interfaces\PublishingServiceInterface;
use App\Repositories\PlatformRepository;
use App\Repositories\PostRepository;
use App\Services\PublishingService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(PlatformRepositoryInterface::class, PlatformRepository::class);
        $this->app->bind(PublishingServiceInterface::class, PublishingService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 