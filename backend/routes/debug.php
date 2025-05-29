<?php

use App\Helpers\DebugHelper;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Debug routes - only available in local environment
Route::prefix('debug')->group(function () {
    // Simple debug test
    Route::get('/test', function () {
        DebugHelper::log('Debug test from API', [
            'timestamp' => now()->toDateTimeString(),
            'random' => rand(1000, 9999)
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Debug test successful',
            'timestamp' => now()->toDateTimeString(),
            'logs_at' => storage_path('logs/laravel.log')
        ]);
    });
    
    // Test Cloudinary connection
    Route::get('/cloudinary', function () {
        try {
            $cloudinary = app(CloudinaryService::class);
            
            // Log the config
            Log::info('Cloudinary debug config', [
                'cloud_name' => $cloudinary->cloudName,
                'api_key' => $cloudinary->apiKey,
                'api_secret_length' => strlen($cloudinary->apiSecret)
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Cloudinary connection tested',
                'config' => [
                    'cloud_name' => $cloudinary->cloudName,
                    'api_key' => $cloudinary->apiKey,
                    'api_secret' => substr($cloudinary->apiSecret, 0, 4) . '...' . substr($cloudinary->apiSecret, -4),
                ]
            ]);
        } catch (\Exception $e) {
            DebugHelper::log('Cloudinary test error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary test failed: ' . $e->getMessage(),
                'logs_at' => storage_path('logs/laravel.log')
            ], 500);
        }
    });
    
    // Test Cloudinary upload
    Route::post('/cloudinary/upload', function (Request $request) {
        try {
            $cloudinary = app(CloudinaryService::class);
            
            $image = $request->input('image');
            if (!$image) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No image provided'
                ], 400);
            }
            
            Log::info('Attempting to upload test image to Cloudinary');
            
            $result = $cloudinary->uploadImage($image);
            
            DebugHelper::log('Cloudinary test upload', [
                'result' => $result
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Image uploaded successfully',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            DebugHelper::log('Cloudinary upload test error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload test failed: ' . $e->getMessage(),
                'logs_at' => storage_path('logs/laravel.log')
            ], 500);
        }
    });
    
    // View recent logs
    Route::get('/logs', function (Request $request) {
        $lines = $request->input('lines', 50);
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Log file not found'
            ], 404);
        }
        
        // Get the last X lines from the log file
        $logContent = shell_exec("tail -n {$lines} {$logPath}");
        
        return response()->json([
            'status' => 'success',
            'log_path' => $logPath,
            'log_content' => $logContent
        ]);
    });
}); 