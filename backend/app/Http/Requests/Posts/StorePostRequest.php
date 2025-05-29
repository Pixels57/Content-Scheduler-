<?php

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Platform;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Using auth middleware already
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|string',
            'scheduled_time' => 'required|date',
            'status' => 'required|in:draft,scheduled,published',
            'platform_ids' => 'required|array',
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
            // Check if Instagram is selected but no image is provided
            $platformIds = $this->input('platform_ids', []);
            $imageUrl = $this->input('image_url');
            
            // Get Instagram platform
            $instagramPlatform = Platform::where('type', 'instagram')->first();
            
            if ($instagramPlatform && in_array($instagramPlatform->id, $platformIds) && empty($imageUrl)) {
                $validator->errors()->add('image_url', 'An image is required when posting to Instagram.');
            }
        });
    }
} 