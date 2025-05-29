<?php

namespace App\Console\Commands;

use App\Helpers\DebugHelper;
use App\Services\CloudinaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:debug {--cloudinary : Test Cloudinary connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug command for testing features';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting debug command...');
        
        if ($this->option('cloudinary')) {
            $this->testCloudinary();
        } else {
            $this->testDebugger();
        }
        
        $this->info('Debug command completed.');
    }
    
    /**
     * Test the Cloudinary connection
     */
    protected function testCloudinary()
    {
        $this->info('Testing Cloudinary connection...');
        
        try {
            $cloudinary = app(CloudinaryService::class);
            
            // Output configuration
            $this->info('Cloudinary configuration:');
            $this->table(
                ['Key', 'Value'],
                [
                    ['Cloud Name', $cloudinary->cloudName],
                    ['API Key', $cloudinary->apiKey],
                    ['API Secret', substr($cloudinary->apiSecret, 0, 4) . '...' . substr($cloudinary->apiSecret, -4)],
                ]
            );
            
            // Log test message
            Log::info('Cloudinary debug test', [
                'cloudName' => $cloudinary->cloudName,
                'apiKey' => $cloudinary->apiKey,
                'apiSecretLength' => strlen($cloudinary->apiSecret)
            ]);
            
            $this->info('Cloudinary test data logged to storage/logs/laravel.log');
            
        } catch (\Exception $e) {
            $this->error('Cloudinary test failed: ' . $e->getMessage());
            $this->line('Exception trace:');
            $this->line($e->getTraceAsString());
            
            Log::error('Cloudinary debug error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Test the debugger functionality
     */
    protected function testDebugger()
    {
        $this->info('Testing debugger...');
        
        // Create some test data
        $testData = [
            'message' => 'Debug test',
            'timestamp' => now()->toDateTimeString(),
            'random' => rand(1000, 9999)
        ];
        
        // Log using our helper
        DebugHelper::log('Debug test from command', $testData);
        DebugHelper::logDump($testData, 'Test data dump');
        DebugHelper::trace('Debug trace test');
        
        $this->info('Debug data logged to storage/logs/laravel.log');
        $this->line('Check the log file for details.');
    }
} 