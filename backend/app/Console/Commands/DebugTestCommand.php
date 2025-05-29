<?php

namespace App\Console\Commands;

use App\Helpers\DebugHelper;
use App\Services\CloudinaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DebugTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:test {--cloudinary-upload= : Base64 image to test Cloudinary upload}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run debug tests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running debug tests...');
        
        // Test Cloudinary
        $this->testCloudinary();
        
        // Test Cloudinary upload if provided
        $uploadTest = $this->option('cloudinary-upload');
        if ($uploadTest) {
            $this->testCloudinaryUpload($uploadTest);
        }
        
        $this->info('Debug tests complete. Check the logs for details.');
    }
    
    /**
     * Test Cloudinary configuration
     */
    protected function testCloudinary()
    {
        $this->info('Testing Cloudinary configuration...');
        
        try {
            $cloudinary = app(CloudinaryService::class);
            
            $this->info("Cloud Name: {$cloudinary->cloudName}");
            $this->info("API Key: {$cloudinary->apiKey}");
            $this->info("API Secret: " . substr($cloudinary->apiSecret, 0, 3) . '...' . substr($cloudinary->apiSecret, -3));
            
            // Log the info
            Log::info('Cloudinary debug test config', [
                'cloud_name' => $cloudinary->cloudName,
                'api_key' => $cloudinary->apiKey,
                'api_secret_length' => strlen($cloudinary->apiSecret)
            ]);
            
            $this->info('✅ Cloudinary configuration loaded successfully');
        } catch (\Exception $e) {
            $this->error('❌ Cloudinary configuration test failed: ' . $e->getMessage());
            DebugHelper::log('Cloudinary config test error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Test Cloudinary upload with a sample image
     */
    protected function testCloudinaryUpload($image = null)
    {
        $this->info('Testing Cloudinary upload...');
        
        try {
            $cloudinary = app(CloudinaryService::class);
            
            // Use provided image or a simple test image
            if (!$image) {
                // Sample tiny 1x1 transparent PNG as base64
                $image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
            }
            
            Log::info('Attempting to upload test image to Cloudinary');
            $this->line('Uploading test image...');
            
            $result = $cloudinary->uploadImage($image);
            
            DebugHelper::log('Cloudinary test upload result', [
                'result' => $result
            ]);
            
            if ($result && isset($result['secure_url'])) {
                $this->info('✅ Image uploaded successfully');
                $this->line('URL: ' . $result['secure_url']);
            } else {
                $this->warn('⚠️ Upload completed but no URL returned');
                $this->line('Result: ' . json_encode($result));
            }
        } catch (\Exception $e) {
            $this->error('❌ Cloudinary upload test failed: ' . $e->getMessage());
            DebugHelper::log('Cloudinary upload test error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 