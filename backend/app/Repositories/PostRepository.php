<?php

namespace App\Repositories;

use App\Interfaces\PostRepositoryInterface;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class PostRepository implements PostRepositoryInterface
{
    /**
     * Get all posts with filters
     *
     * @param array $filters
     * @param int $userId
     * @return LengthAwarePaginator
     */
    public function getAllPosts(array $filters, int $userId): LengthAwarePaginator
    {
        $query = Post::where('user_id', $userId)->select('id', 'status', 'created_at', 'updated_at');
        
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
     * Create a new post
     *
     * @param array $postData
     * @param int $userId
     * @return Post
     */
    public function createPost(array $postData, int $userId): Post
    {
        // Create the post
        $post = Post::create([
            'title' => $postData['title'],
            'content' => $postData['content'],
            'image_url' => $postData['image_url'] ?? null,
            'scheduled_time' => $postData['scheduled_time'],
            'status' => $postData['status'],
            'user_id' => $userId,
        ]);
        
        // Attach platforms if provided
        if (isset($postData['platform_ids']) && is_array($postData['platform_ids'])) {
            foreach ($postData['platform_ids'] as $platformId) {
                $post->platforms()->attach($platformId);
            }
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

        if($post->status == 'scheduled' || $post->status == 'published') {

            if (isset($newDetails['title'])) {
                $post->title = $newDetails['title'];
            }
            
            if (isset($newDetails['content'])) {
                $post->content = $newDetails['content'];
            }
            
            if (array_key_exists('image_url', $newDetails)) {
                $post->image_url = $newDetails['image_url'];
            }

            if ($post->status == 'scheduled') {
                if (isset($newDetails['scheduled_time'])) {
                    $post->scheduled_time = $newDetails['scheduled_time'];
                }
            }
            if (isset($newDetails['status'])) {
                $post->status = $newDetails['status'];
            }
        }
        
        // Update post fields
       
        
        $post->save();
        
        // Update platforms if provided
        if (isset($newDetails['platform_ids']) && is_array($newDetails['platform_ids'])) {
            $post->platforms()->sync($newDetails['platforms_ids']);
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
} 