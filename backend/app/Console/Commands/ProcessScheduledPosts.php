<?php

namespace App\Console\Commands;

use App\Interfaces\PublishingServiceInterface;
use Illuminate\Console\Command;

class ProcessScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-scheduled-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled posts that are due for publication';
    
    /**
     * The publishing service.
     *
     * @var PublishingServiceInterface
     */
    protected $publishingService;
    
    /**
     * Create a new command instance.
     *
     * @param PublishingServiceInterface $publishingService
     * @return void
     */
    public function __construct(PublishingServiceInterface $publishingService)
    {
        parent::__construct();
        $this->publishingService = $publishingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled posts...');
        
        $count = $this->publishingService->processDuePosts();
        
        $this->info("Processed {$count} posts.");
        
        return Command::SUCCESS;
    }
}
