<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestScheduledPostCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-scheduled-post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test scheduled post in the past to test the scheduler';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating a test scheduled post...');
        $this->info('Current time: ' . now()->format('Y-m-d H:i:s'));
        
        // Find a user
        $user = User::first();
        
        if (!$user) {
            $this->error('No users found in the database. Please create a user first.');
            return Command::FAILURE;
        }
        
        // Get some platforms
        $platforms = Platform::where('status', 'active')->take(2)->get();
        
        if ($platforms->isEmpty()) {
            $this->error('No active platforms found. Please create some platforms first.');
            return Command::FAILURE;
        }
        
        // Create a post scheduled in the past (5 minutes ago)
        $post = Post::create([
            'title' => 'Test Scheduled Post',
            'content' => 'This is a test post created by the scheduler test command.',
            'scheduled_time' => now()->subMinutes(5),
            'status' => 'scheduled',
            'user_id' => $user->id,
        ]);
        
        // Attach platforms
        $post->platforms()->attach($platforms->pluck('id')->toArray());
        
        $this->info("Created post ID: {$post->id}");
        $this->info("Post is scheduled for: {$post->scheduled_time}");
        $this->info("Attached platforms: {$platforms->pluck('name')->implode(', ')}");
        
        $this->info('');
        $this->info('Now run: php artisan app:process-scheduled-posts');
        $this->info('This should publish the post automatically.');
        
        return Command::SUCCESS;
    }
} 