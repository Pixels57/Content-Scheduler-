<?php

namespace App\Repositories;

use App\Interfaces\PostRepositoryInterface;
use App\Models\Post;
use App\Models\Platform;
use App\Services\CloudinaryService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PostRepository implements PostRepositoryInterface
{
    /**
     * @var CloudinaryService
     */
    protected $cloudinaryService;

    /**
     * PostRepository constructor.
     * 
     * @param CloudinaryService $cloudinaryService
     */
    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * Get all posts with filters
     *
     * @param array $filters
     * @param int $userId
     * @return LengthAwarePaginator
     */
    public function getAllPosts(array $filters, int $userId): LengthAwarePaginator
    {
        $query = Post::where('user_id', $userId)
            ->with('platforms')
            ->select('id', 'title', 'content', 'image_url', 'scheduled_time', 'status', 'created_at', 'updated_at');
        
        // Apply status filter
        if (isset($filters['status']) && in_array($filters['status'], ['draft', 'scheduled', 'published'])) {
            $query->where('status', $filters['status']);
        }
        
        // Apply date range filters
        if (isset($filters['from_date'])) {
            $query->where('scheduled_time', '>=', $filters['from_date']);
        }
        
        if (isset($filters['to_date'])) {
            $query->where('scheduled_time', '<=', $filters['to_date']);
        }
        
        return $query->latest()->paginate(10);
    }
    
    /**
     * Get a post by ID
     *
     * @param int $postId
     * @return Post
     */
    public function getPostById(int $postId): Post
    {
        return Post::with('platforms')->findOrFail($postId);
    }
    
    /**
     * Validate that Instagram posts have an image
     * 
     * @param array $platformIds
     * @param string|null $imageUrl
     * @throws ValidationException
     */
    protected function validateInstagramImageRequirement(array $platformIds, ?string $imageUrl): void
    {
        // Skip validation if no platforms are provided
        if (empty($platformIds)) {
            return;
        }
        
        // Get Instagram platform
        $instagramPlatform = Platform::where('name', 'Instagram')->first();
        
        // Skip validation if no Instagram platform exists
        if (!$instagramPlatform) {
            return;
        }
        
        // Check if Instagram is selected but no image is provided
        if (in_array($instagramPlatform->id, $platformIds) && empty($imageUrl)) {
            Log::warning('Instagram selected but no image provided');
            throw ValidationException::withMessages([
                'image_url' => ['An image is required for Instagram posts. Please upload an image.']
            ]);
        }
    }
    
    /**
     * Validate post content against platform character limits
     * 
     * @param array $platformIds
     * @param string $content
     * @throws ValidationException
     */
    protected function validatePlatformCharacterLimits(array $platformIds, string $content): void
    {
        // Skip validation if no platforms are provided
        if (empty($platformIds) || empty($content)) {
            return;
        }
        
        // Get selected platforms with their character limits
        $platforms = Platform::whereIn('id', $platformIds)
            ->get(['id', 'name', 'type', 'character_limit']);
            
        $contentLength = mb_strlen($content);
        $errors = [];
        
        foreach ($platforms as $platform) {
            // Only validate if the platform has a character limit set
            if ($platform->character_limit) {
                // Check if content exceeds the character limit
                if ($contentLength > $platform->character_limit) {
                    $errors[] = "Content exceeds the {$platform->name} character limit of {$platform->character_limit} characters. Current length: {$contentLength} characters.";
                    Log::warning("Content exceeds character limit for {$platform->name}", [
                        'limit' => $platform->character_limit,
                        'content_length' => $contentLength,
                        'platform' => $platform->name
                    ]);
                }
            }
        }
        
        // Throw validation exception if any limits are exceeded
        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'content' => $errors
            ]);
        }
    }
    
    /**
     * Validate daily scheduled post limit (10 posts per day)
     * 
     * @param int $userId
     * @param string|null $scheduledTime
     * @param string $status
     * @param int|null $excludePostId
     * @throws ValidationException
     */
    protected function validateDailyScheduledPostLimit(int $userId, ?string $scheduledTime, string $status, ?int $excludePostId = null): void
    {
        // Skip validation if not a scheduled post
        if ($status !== 'scheduled' || empty($scheduledTime)) {
            return;
        }
        
        // Convert scheduled time to date object
        $scheduledDate = date('Y-m-d', strtotime($scheduledTime));
        
        // Count posts scheduled for the same day by this user
        $query = Post::where('user_id', $userId)
            ->where('status', 'scheduled')
            ->whereDate('scheduled_time', $scheduledDate);
            
        // Exclude current post when updating
        if ($excludePostId) {
            $query->where('id', '!=', $excludePostId);
        }
        
        $scheduledPostCount = $query->count();
        
        // Check if user has exceeded the daily limit (10 posts)
        if ($scheduledPostCount >= 10) {
            Log::warning('User exceeded daily scheduled post limit', [
                'user_id' => $userId,
                'scheduled_date' => $scheduledDate,
                'current_count' => $scheduledPostCount
            ]);
            
            throw ValidationException::withMessages([
                'scheduled_time' => ['You have reached the daily limit of 10 scheduled posts for ' . $scheduledDate . '. Please select another date.']
            ]);
        }
    }
    
    /**
     * Create a new post
     *
     * @param array $postData
     * @param int $userId
     * @return Post
     */
    public function createPost(array $postData, int $userId): Post
    {
        // Skip validation if required data is missing
        if (isset($postData['platform_ids']) && is_array($postData['platform_ids'])) {
            // Validate Instagram image requirement
            $imageUrl = $postData['image_url'] ?? null;
            $this->validateInstagramImageRequirement($postData['platform_ids'], $imageUrl);
            
            // Validate character limits
            if (isset($postData['content'])) {
                $this->validatePlatformCharacterLimits($postData['platform_ids'], $postData['content']);
            }
        }
        
        // Validate daily scheduled post limit
        $this->validateDailyScheduledPostLimit(
            $userId, 
            $postData['scheduled_time'] ?? null, 
            $postData['status']
        );
        
        // Handle image upload if provided
        $imageUrl = null;
        if (isset($postData['image_url']) && !empty($postData['image_url'])) {
            Log::info('Uploading image to Cloudinary for new post');
            
            // Upload the image to Cloudinary and get the URL
            $imageUrl = $this->cloudinaryService->uploadImage($postData['image_url']);
            
            Log::info('Image uploaded to Cloudinary', ['url' => $imageUrl]);
        }
        
        // Create the post with the Cloudinary URL
        $post = Post::create([
            'title' => $postData['title'],
            'content' => $postData['content'],
            'image_url' => $imageUrl, // Store the Cloudinary URL
            'scheduled_time' => $postData['scheduled_time'],
            'status' => $postData['status'],
            'user_id' => $userId,
        ]);
        
        Log::info('Post created', [
            'post_id' => $post->id,
            'has_image' => !empty($imageUrl)
        ]);
        
        // Attach platforms if provided
        if (isset($postData['platform_ids']) && is_array($postData['platform_ids'])) {
            $post->platforms()->attach($postData['platform_ids']);
        }
        
        // Load the platforms relationship
        $post->load('platforms');
        
        return $post;
    }
    
    /**
     * Update an existing post
     *
     * @param int $postId
     * @param array $newDetails
     * @return Post
     */
    public function updatePost(int $postId, array $newDetails): Post
    {
        $post = Post::findOrFail($postId);
        
        // Determine the image URL for validation (either from update or existing)
        $imageUrl = array_key_exists('image_url', $newDetails) 
            ? $newDetails['image_url'] 
            : $post->image_url;
            
        // Determine platform IDs for validation (either from update or existing)
        $platformIds = isset($newDetails['platform_ids']) && is_array($newDetails['platform_ids'])
            ? $newDetails['platform_ids']
            : $post->platforms->pluck('id')->toArray();
        
        // Determine content for validation (either from update or existing)
        $content = isset($newDetails['content'])
            ? $newDetails['content']
            : $post->content;
            
        // Validate Instagram image requirement
        $this->validateInstagramImageRequirement($platformIds, $imageUrl);
        
        // Validate character limits
        $this->validatePlatformCharacterLimits($platformIds, $content);
        
        // Validate daily scheduled post limit (only if status or scheduled_time is changing)
        $newStatus = $newDetails['status'] ?? $post->status;
        $newScheduledTime = $newDetails['scheduled_time'] ?? $post->scheduled_time;
        
        // Only validate if post is becoming scheduled or changing scheduled date
        if (($newStatus === 'scheduled' && ($post->status !== 'scheduled' || $newScheduledTime !== $post->scheduled_time))) {
            $this->validateDailyScheduledPostLimit(
                $post->user_id,
                $newScheduledTime,
                $newStatus,
                $postId // Exclude current post
            );
        }

        Log::info('Updating post', ['post_id' => $postId]);

        // Update basic fields
        if (isset($newDetails['title'])) {
            $post->title = $newDetails['title'];
        }
        
        if (isset($newDetails['content'])) {
            $post->content = $newDetails['content'];
        }
        
        // Handle image update
        if (array_key_exists('image_url', $newDetails)) {
            if (!empty($newDetails['image_url'])) {
                // Only upload new image if it's a base64 string (not an existing URL)
                if (strpos($newDetails['image_url'], 'data:image/') === 0) {
                    Log::info('Uploading new image to Cloudinary for post update', ['post_id' => $postId]);
                    
                    // Upload to Cloudinary and get the URL
                    $cloudinaryUrl = $this->cloudinaryService->uploadImage($newDetails['image_url']);
                    
                    Log::info('Image uploaded to Cloudinary for post update', [
                        'post_id' => $postId,
                        'url' => $cloudinaryUrl
                    ]);
                    
                    if ($cloudinaryUrl) {
                        $post->image_url = $cloudinaryUrl; // Store the Cloudinary URL
                    } else {
                        Log::error('Failed to upload image to Cloudinary', ['post_id' => $postId]);
                    }
                } else {
                    // Already a URL, keep as is
                    $post->image_url = $newDetails['image_url'];
                }
            } else {
                // Clear the image
                $post->image_url = null;
            }
        }

        if (isset($newDetails['scheduled_time'])) {
            $post->scheduled_time = $newDetails['scheduled_time'];
        }
        
        if (isset($newDetails['status'])) {
            $post->status = $newDetails['status'];
        }
        
        $post->save();
        
        Log::info('Post updated', ['post_id' => $postId]);
        
        // Update platforms if provided
        if (isset($newDetails['platform_ids']) && is_array($newDetails['platform_ids'])) {
            $post->platforms()->sync($newDetails['platform_ids']);
        }
        
        // Load the platforms relationship
        $post->load('platforms');
        
        return $post;
    }
    
    /**
     * Delete a post
     *
     * @param int $postId
     * @return bool
     */
    public function deletePost(int $postId): bool
    {
        $post = Post::findOrFail($postId);
        return $post->delete();
    }
    
    /**
     * Get posts scheduled to be sent
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getScheduledPostsToBeSent()
    {
        return Post::scheduledToBeSent()->with('platforms')->get();
    }
    
    /**
     * Test Instagram image validation and character limits directly
     * This is a utility method for testing purposes
     * 
     * @param array $platformIds
     * @param string|null $imageUrl
     * @param string|null $content
     * @return bool
     * @throws ValidationException
     */
    public function testInstagramImageValidation(array $platformIds, ?string $imageUrl, ?string $content = null): bool
    {
        try {
            // Test Instagram image validation
            $this->validateInstagramImageRequirement($platformIds, $imageUrl);
            
            // Test character limits if content is provided
            if ($content) {
                $this->validatePlatformCharacterLimits($platformIds, $content);
            }
            
            return true;
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Get analytics data for posts
     * 
     * @param int $userId
     * @param array $filters Optional filters for date range
     * @return array
     */
    public function getAnalytics(int $userId, array $filters = []): array
    {
        // Apply date range filters if provided
        $query = Post::where('user_id', $userId);
        
        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }
        
        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }
        
        // Get counts by status
        $statusCounts = $query->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Calculate total posts
        $totalPosts = array_sum($statusCounts);
        
        // Get posts per platform
        $platformPostsQuery = Platform::select('platforms.id', 'platforms.name', 'platforms.type')
            ->selectRaw('count(posts.id) as post_count')
            ->leftJoin('post_platform', 'platforms.id', '=', 'post_platform.platform_id')
            ->leftJoin('posts', function ($join) use ($userId) {
                $join->on('post_platform.post_id', '=', 'posts.id')
                    ->where('posts.user_id', '=', $userId);
            })
            ->groupBy('platforms.id', 'platforms.name', 'platforms.type');
            
        // Apply date filters to platform query if needed
        if (isset($filters['from_date'])) {
            $platformPostsQuery->where('posts.created_at', '>=', $filters['from_date']);
        }
        
        if (isset($filters['to_date'])) {
            $platformPostsQuery->where('posts.created_at', '<=', $filters['to_date']);
        }
        
        $platformPosts = $platformPostsQuery->get()
            ->map(function ($platform) {
                return [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'type' => $platform->type,
                    'post_count' => $platform->post_count
                ];
            });
            
        // Calculate success rate (published / total scheduled)
        $scheduledCount = $statusCounts['scheduled'] ?? 0;
        $publishedCount = $statusCounts['published'] ?? 0;
        $successRate = $scheduledCount > 0 
            ? round(($publishedCount / ($scheduledCount + $publishedCount)) * 100, 2) 
            : 0;
            
        // Calculate post distribution percentages
        $statusPercentages = [];
        foreach ($statusCounts as $status => $count) {
            $statusPercentages[$status] = $totalPosts > 0
                ? round(($count / $totalPosts) * 100, 2)
                : 0;
        }
        
        // Return all analytics data
        return [
            'total_posts' => $totalPosts,
            'posts_by_status' => $statusCounts,
            'status_percentages' => $statusPercentages,
            'success_rate' => $successRate,
            'platforms' => $platformPosts,
        ];
    }
} 