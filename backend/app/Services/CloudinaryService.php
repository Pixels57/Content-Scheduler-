<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CloudinaryService
{
    protected $cloudName;
    protected $apiKey;
    protected $apiSecret;
    protected $uploadUrl;

    public function __construct()
    {
        // Hard-code the credentials for now to ensure they work
        $this->cloudName = 'dxy3lq6gh'; 
        $this->apiKey = '941913859728837';
        $this->apiSecret = 'R1IDiKXAcMkswyGb0Ac10wXk6tM';
        
        // Set the upload URL
        $this->uploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload";
        
        // Log initialization
        Log::info('CloudinaryService initialized', [
            'cloudName' => $this->cloudName
        ]);
    }

    /**
     * Upload an image to Cloudinary using direct cURL
     *
     * @param string $base64Image Base64 encoded image or image URL
     * @param array $options Additional upload options
     * @return string|null URL of the uploaded image or null on failure
     */
    public function uploadImage($base64Image, array $options = [])
    {
        try {
            // If it's already a URL, just return it
            if (filter_var($base64Image, FILTER_VALIDATE_URL)) {
                return $base64Image;
            }
            
            // Check if the image is a base64 string
            if (!preg_match('/^data:image\/(\w+);base64,/', $base64Image)) {
                Log::error('Invalid image format provided');
                return null;
            }
            
            // Log the upload attempt
            Log::info('Attempting to upload image to Cloudinary');
            
            // Get file data after removing the mimetype prefix
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
            $imageData = base64_decode($imageData);
            
            if (!$imageData) {
                Log::error('Failed to decode base64 image');
                return null;
            }
            
            // Create a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'cloudinary');
            file_put_contents($tempFile, $imageData);
            
            // Generate a unique public_id/filename
            $publicId = isset($options['public_id']) ? $options['public_id'] : 'post_' . time() . '_' . Str::random(8);
            $folder = isset($options['folder']) ? $options['folder'] : 'posts';
            
            // Prepare the post fields
            $timestamp = time();
            $signature = sha1("folder={$folder}&public_id={$publicId}&timestamp={$timestamp}{$this->apiSecret}");
            
            $postFields = [
                'file' => new \CURLFile($tempFile),
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'folder' => $folder,
                'public_id' => $publicId,
                'signature' => $signature
            ];
            
            // Initialize cURL
            $curl = curl_init();
            
            // Set cURL options
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->uploadUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification for local development
                CURLOPT_SSL_VERIFYHOST => false  // Disable SSL verification for local development
            ]);
            
            // Execute the cURL request
            $response = curl_exec($curl);
            $error = curl_error($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            // Close cURL and delete the temp file
            curl_close($curl);
            unlink($tempFile);
            
            // Check for errors
            if ($error) {
                Log::error('cURL error during Cloudinary upload: ' . $error);
                return null;
            }
            
            // Parse the response
            $result = json_decode($response, true);
            
            // Check if the response contains a secure URL
            if (isset($result['secure_url'])) {
                Log::info('Image uploaded to Cloudinary successfully', [
                    'url' => $result['secure_url']
                ]);
                return $result['secure_url'];
            } else {
                Log::error('Cloudinary response did not contain secure_url', [
                    'response' => $response
                ]);
                return null;
            }
        } catch (Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
} 