<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Platform;
use App\Repositories\PostRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPostWithImageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:post-with-image';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test creating a post with an image upload';

    /**
     * Execute the console command.
     */
    public function handle(PostRepository $postRepository)
    {
        $this->info('Testing creating a post with image upload...');
        
        try {
            // Get a user for the test
            $user = User::first();
            
            if (!$user) {
                $this->error('No users found in the database. Please create a user first.');
                return 1;
            }
            
            $this->info("Using user: {$user->name} (ID: {$user->id})");
            
            // Get some platforms for the test
            $platforms = Platform::where('user_id', $user->id)->get();
            
            if ($platforms->isEmpty()) {
                $this->warn('No platforms found for this user. The post will be created without platforms.');
                $platformIds = [];
            } else {
                $platformIds = $platforms->pluck('id')->toArray();
                $this->info("Using " . count($platformIds) . " platforms");
            }
            
            // Sample tiny 1x1 transparent PNG as base64
            $sampleImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
            
            // Create post data
            $postData = [
                'title' => 'Test Post with Image ' . now()->format('Y-m-d H:i:s'),
                'content' => 'This is a test post with an uploaded image to Cloudinary.',
                'image_url' => $sampleImage, // Base64 image will be uploaded to Cloudinary
                'scheduled_time' => now()->addDay()->format('Y-m-d H:i:s'),
                'status' => 'draft',
                'platform_ids' => $platformIds,
            ];
            
            $this->info('Creating post...');
            
            // Create the post
            $post = $postRepository->createPost($postData, $user->id);
            
            if ($post) {
                $this->info('✅ Post created successfully!');
                $this->info("Post ID: {$post->id}");
                $this->info("Title: {$post->title}");
                $this->info("Status: {$post->status}");
                
                if ($post->image_url) {
                    $this->info("Image URL: {$post->image_url}");
                    $this->info("✅ Image was successfully uploaded to Cloudinary and URL stored in database!");
                } else {
                    $this->error("❌ Image URL was not saved in the database.");
                }
                
                $this->info("Post has " . count($post->platforms) . " platforms attached");
            } else {
                $this->error('❌ Failed to create post.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Test post with image error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
} 