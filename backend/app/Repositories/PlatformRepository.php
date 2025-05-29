<?php

namespace App\Repositories;

use App\Interfaces\PlatformRepositoryInterface;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;

class PlatformRepository implements PlatformRepositoryInterface
{
    /**
     * Get all platforms
     *
     * @return Collection
     */
    public function getAllPlatforms(): Collection
    {
        return Platform::all();
    }
    
    /**
     * Get a platform by ID
     *
     * @param int $platformId
     * @return Platform
     */
    public function getPlatformById(int $platformId): Platform
    {
        return Platform::findOrFail($platformId);
    }
    
    /**
     * Create a new platform
     *
     * @param array $platformData
     * @return Platform
     */
    public function createPlatform(array $platformData): Platform
    {
        return Platform::create([
            'name' => $platformData['name'],
            'type' => $platformData['type'],
            'character_limit' => $platformData['character_limit'] ?? 280, // Default to 280 characters
            'status' => $platformData['status'] ?? 'active', // Default to active
        ]);
    }
    
    /**
     * Update an existing platform
     *
     * @param int $platformId
     * @param array $newDetails
     * @return Platform
     */
    public function updatePlatform(int $platformId, array $newDetails): Platform
    {
        $platform = Platform::findOrFail($platformId);
        $platform->update($newDetails);
        return $platform;
    }
    
    /**
     * Delete a platform
     *
     * @param int $platformId
     * @return bool
     */
    public function deletePlatform(int $platformId): bool
    {
        $platform = Platform::findOrFail($platformId);
        return $platform->delete();
    }
} 