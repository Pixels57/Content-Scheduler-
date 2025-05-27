<?php

namespace App\Services;

use App\Interfaces\PostRepositoryInterface;
use App\Interfaces\PublishingServiceInterface;
use App\Models\Post;
use App\Models\Platform;
use Illuminate\Support\Facades\Log;

class PublishingService implements PublishingServiceInterface
{
    /**
     * The post repository implementation.
     *
     * @var PostRepositoryInterface
     */
    protected $postRepository;
    
    /**
     * Create a new service instance.
     *
     * @param PostRepositoryInterface $postRepository
     * @return void
     */
    public function __construct(PostRepositoryInterface $postRepository)
    {
        $this->postRepository = $postRepository;
    }
    
    /**
     * Publish a post to a specific platform
     *
     * @param Post $post
     * @param Platform $platform
     * @return bool
     */
    public function publishToSocialMedia(Post $post, Platform $platform): bool
    {
        try {
            // Validate post content for the specific platform
            $validationResult = $this->validateForPlatform($post, $platform);
            
            if (!$validationResult['isValid']) {
                Log::warning("Post #{$post->id} failed validation for {$platform->name}", [
                    'post' => $post->id,
                    'platform' => $platform->id,
                    'errors' => $validationResult['errors']
                ]);
                
                return false;
            }
            
            // In a real application, this would actually call the platform's API
            // For this demo, we'll just simulate a successful publishing
            
            // Ensure the relationship exists in the pivot table
            if (!$post->platforms->contains($platform->id)) {
                $post->platforms()->attach($platform->id);
            }
            
            // Update platform status if needed
            // $platform->status = 'published'; // Uncomment if you have a status field on the platform table
            // $platform->save();
            
            Log::info("Published post #{$post->id} to {$platform->name}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to publish post #{$post->id} to {$platform->name}", [
                'post' => $post->id,
                'platform' => $platform->id,
                'error' => $e->getMessage()
            ]);
            
            // Update platform status if needed
            // $platform->status = 'failed'; // Uncomment if you have a status field on the platform table
            // $platform->save();
            
            return false;
        }
    }
    
    /**
     * Validate post content for a specific platform
     *
     * @param Post $post
     * @param Platform $platform
     * @return array
     */
    public function validateForPlatform(Post $post, Platform $platform): array
    {
        $result = [
            'isValid' => true,
            'errors' => []
        ];
        
        switch ($platform->type) {
            case 'twitter':
                // Twitter has a character limit
                if (strlen($post->content) > 280) {
                    $result['isValid'] = false;
                    $result['errors'][] = 'Content exceeds Twitter\'s 280 character limit';
                }
                break;
                
            case 'instagram':
                // Instagram requires an image
                if (empty($post->image_url)) {
                    $result['isValid'] = false;
                    $result['errors'][] = 'Instagram posts require an image';
                }
                break;
                
            case 'linkedin':
                // LinkedIn has longer content expectations
                if (strlen($post->content) < 50) {
                    $result['isValid'] = false;
                    $result['errors'][] = 'LinkedIn posts should have at least 50 characters for better engagement';
                }
                break;
        }
        
        return $result;
    }
    
    /**
     * Process all posts that are due for publishing
     *
     * @return int Number of posts processed
     */
    public function processDuePosts(): int
    {
        $posts = $this->postRepository->getScheduledPostsToBeSent();
        $count = 0;
        
        foreach ($posts as $post) {
            $success = $this->processPost($post);
            if ($success) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Process a single post for publishing
     *
     * @param Post $post
     * @return bool
     */
    protected function processPost(Post $post): bool
    {
        try {
            // Update the post status
            $post->status = 'published';
            $post->save();
            
            $allPlatformsSuccess = true;
            
            // Process each platform
            foreach ($post->platforms as $platform) {
                $platformSuccess = $this->publishToSocialMedia($post, $platform);
                $allPlatformsSuccess = $allPlatformsSuccess && $platformSuccess;
            }
            
            Log::info("Successfully processed post #{$post->id}");
            
            return $allPlatformsSuccess;
        } catch (\Exception $e) {
            Log::error("Failed to process post #{$post->id}", [
                'post' => $post->toArray(),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
} 