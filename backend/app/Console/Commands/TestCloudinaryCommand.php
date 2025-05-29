<?php

namespace App\Console\Commands;

use App\Services\CloudinaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCloudinaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cloudinary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Cloudinary image upload';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Cloudinary image upload...');
        
        // Sample tiny 1x1 transparent PNG as base64
        $sampleImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        
        try {
            $cloudinary = app(CloudinaryService::class);
            
            $this->info('Configuration:');
            $this->line("Cloud Name: " . config('services.cloudinary.cloud_name'));
            $this->line("API Key: " . config('services.cloudinary.api_key'));
            
            $this->info('Uploading test image...');
            
            $result = $cloudinary->uploadImage($sampleImage, [
                'folder' => 'test',
                'public_id' => 'test_' . time()
            ]);
            
            if ($result) {
                $this->info('✅ Image uploaded successfully!');
                $this->line('Image URL: ' . $result);
                
                // This URL would be stored in your database
                $this->info('This URL should be stored in your database.');
            } else {
                $this->error('❌ Failed to upload image to Cloudinary.');
                $this->line('Check the logs for more details.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Cloudinary test error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 