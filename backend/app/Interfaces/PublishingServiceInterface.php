<?php

namespace App\Interfaces;

use App\Models\Post;
use App\Models\Platform;

interface PublishingServiceInterface
{
    /**
     * Publish a post to a specific platform
     *
     * @param Post $post
     * @param Platform $platform
     * @return bool
     */
    public function publishToSocialMedia(Post $post, Platform $platform): bool;
    
    /**
     * Validate post content for a specific platform
     *
     * @param Post $post
     * @param Platform $platform
     * @return array
     */
    public function validateForPlatform(Post $post, Platform $platform): array;
    
    /**
     * Process all posts that are due for publishing
     *
     * @return int Number of posts processed
     */
    public function processDuePosts(): int;
} 