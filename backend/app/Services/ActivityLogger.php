<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log a user activity
     *
     * @param string $action The action performed (create, update, delete, etc.)
     * @param string|null $entityType The type of entity (post, platform, etc.)
     * @param int|null $entityId The ID of the entity
     * @param string $description Human-readable description of the action
     * @param array|null $metadata Additional data related to the action
     * @return ActivityLog
     */
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        string $description = '',
        ?array $metadata = null
    ): ActivityLog {
        $userId = Auth::id();
        
        if (!$userId) {
            // If there's no authenticated user, don't log
            // This would typically be the case for public routes
            throw new \Exception('Cannot log activity: No authenticated user.');
        }
        
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
    
    /**
     * Log a login activity
     *
     * @param int $userId
     * @return ActivityLog
     */
    public function logLogin(int $userId): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => 'login',
            'description' => 'User logged in',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
    
    /**
     * Log a logout activity
     *
     * @return ActivityLog|null
     */
    public function logLogout(): ?ActivityLog
    {
        $userId = Auth::id();
        
        if (!$userId) {
            return null;
        }
        
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => 'logout',
            'description' => 'User logged out',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
    
    /**
     * Log a post creation
     *
     * @param int $postId
     * @param array $postData
     * @return ActivityLog
     */
    public function logPostCreation(int $postId, array $postData): ActivityLog
    {
        return $this->log(
            'create',
            'post',
            $postId,
            'Created a new post: ' . ($postData['title'] ?? 'Untitled'),
            [
                'title' => $postData['title'] ?? 'Untitled',
                'status' => $postData['status'] ?? 'draft',
                'platforms' => $postData['platform_ids'] ?? [],
            ]
        );
    }
    
    /**
     * Log a post update
     *
     * @param int $postId
     * @param array $postData
     * @return ActivityLog
     */
    public function logPostUpdate(int $postId, array $postData): ActivityLog
    {
        return $this->log(
            'update',
            'post',
            $postId,
            'Updated post: ' . ($postData['title'] ?? 'Untitled'),
            [
                'title' => $postData['title'] ?? null,
                'status' => $postData['status'] ?? null,
                'platforms' => $postData['platform_ids'] ?? null,
            ]
        );
    }
    
    /**
     * Log a post deletion
     *
     * @param int $postId
     * @param string $postTitle
     * @return ActivityLog
     */
    public function logPostDeletion(int $postId, string $postTitle): ActivityLog
    {
        return $this->log(
            'delete',
            'post',
            $postId,
            'Deleted post: ' . $postTitle
        );
    }
    
    /**
     * Log a platform settings change
     *
     * @param array $platformIds
     * @return ActivityLog
     */
    public function logPlatformSettingsChange(array $platformIds): ActivityLog
    {
        return $this->log(
            'update',
            'platform_settings',
            null,
            'Updated platform settings',
            [
                'platform_ids' => $platformIds,
            ]
        );
    }
    
    /**
     * Log a profile update
     *
     * @param array $userData
     * @return ActivityLog
     */
    public function logProfileUpdate(array $userData): ActivityLog
    {
        return $this->log(
            'update',
            'profile',
            Auth::id(),
            'Updated profile information',
            [
                'name' => $userData['name'] ?? null,
                'email' => $userData['email'] ?? null,
                // Don't log password changes for security
            ]
        );
    }
} 