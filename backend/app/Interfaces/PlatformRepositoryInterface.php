<?php

namespace App\Interfaces;

use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;

interface PlatformRepositoryInterface
{
    /**
     * Get all platforms
     *
     * @return Collection
     */
    public function getAllPlatforms(): Collection;
    
    /**
     * Get a platform by ID
     *
     * @param int $platformId
     * @return Platform
     */
    public function getPlatformById(int $platformId): Platform;
    
    /**
     * Create a new platform
     *
     * @param array $platformData
     * @return Platform
     */
    public function createPlatform(array $platformData): Platform;
    
    /**
     * Update an existing platform
     *
     * @param int $platformId
     * @param array $newDetails
     * @return Platform
     */
    public function updatePlatform(int $platformId, array $newDetails): Platform;
    
    /**
     * Delete a platform
     *
     * @param int $platformId
     * @return bool
     */
    public function deletePlatform(int $platformId): bool;
} 