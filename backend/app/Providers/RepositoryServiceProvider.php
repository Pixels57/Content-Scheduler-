<?php

namespace App\Providers;

use App\Interfaces\AuthServiceInterface;
use App\Interfaces\PlatformRepositoryInterface;
use App\Interfaces\PostRepositoryInterface;
use App\Interfaces\PublishingServiceInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Repositories\PlatformRepository;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PublishingService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(PlatformRepositoryInterface::class, PlatformRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        
        // Services
        $this->app->bind(PublishingServiceInterface::class, PublishingService::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 