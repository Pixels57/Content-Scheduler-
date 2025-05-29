<?php

namespace App\Interfaces;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface
{
    /**
     * Get all posts with filters
     *
     * @param array $filters
     * @param int $userId
     * @return LengthAwarePaginator
     */
    public function getAllPosts(array $filters, int $userId): LengthAwarePaginator;
    
    /**
     * Get a post by ID
     *
     * @param int $postId
     * @return Post
     */
    public function getPostById(int $postId): Post;
    
    /**
     * Create a new post
     *
     * @param array $postData
     * @param int $userId
     * @return Post
     */
    public function createPost(array $postData, int $userId): Post;
    
    /**
     * Update an existing post
     *
     * @param int $postId
     * @param array $newDetails
     * @return Post
     */
    public function updatePost(int $postId, array $newDetails): Post;
    
    /**
     * Delete a post
     *
     * @param int $postId
     * @return bool
     */
    public function deletePost(int $postId): bool;
    
    /**
     * Get posts scheduled to be sent
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getScheduledPostsToBeSent();
    
    /**
     * Get analytics data for posts
     * 
     * @param int $userId
     * @param array $filters Optional filters for date range
     * @return array
     */
    public function getAnalytics(int $userId, array $filters = []): array;
} 