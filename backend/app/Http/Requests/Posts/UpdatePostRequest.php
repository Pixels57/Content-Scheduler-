<?php

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Platform;
use App\Models\Post;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Using policy in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'image_url' => 'nullable|string',
            'scheduled_time' => 'sometimes|date',
            'status' => 'sometimes|in:draft,scheduled,published',
            'platform_ids' => 'sometimes|array',
            'platform_ids.*' => 'exists:platforms,id',
        ];
    }
    
    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Get the post being updated
            $postId = $this->route('post');
            $post = Post::with('platforms')->findOrFail($postId);
            
            // Get Instagram platform
            $instagramPlatform = Platform::where('type', 'instagram')->first();
            if (!$instagramPlatform) {
                return; // No Instagram platform found
            }
            
            // Determine platform IDs for this update
            $platformIds = $this->input('platform_ids', $post->platforms->pluck('id')->toArray());
            
            // Check if Instagram is included in platform_ids
            $hasInstagram = in_array($instagramPlatform->id, $platformIds);
            if (!$hasInstagram) {
                return; // Instagram is not selected
            }
            
            // Determine image URL for this update
            $imageUrl = $this->input('image_url', $post->image_url);
            
            // If Instagram is selected and image is empty or being cleared, add error
            if ($hasInstagram && empty($imageUrl)) {
                $validator->errors()->add('image_url', 'An image is required when posting to Instagram.');
            }
        });
    }
} 