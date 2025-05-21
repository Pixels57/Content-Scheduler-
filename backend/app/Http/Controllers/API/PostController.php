<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Interfaces\PostRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * The post repository instance.
     *
     * @var PostRepositoryInterface
     */
    protected $postRepository;
    
    /**
     * Create a new controller instance.
     *
     * @param PostRepositoryInterface $postRepository
     * @return void
     */
    public function __construct(PostRepositoryInterface $postRepository)
    {
        $this->postRepository = $postRepository;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->status,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ];
        
        $posts = $this->postRepository->getAllPosts($filters, $request->user()->id);
        
        return response()->json($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|url',
            'scheduled_time' => 'required|date',
            'status' => 'required|in:draft,scheduled,published',
            'platform_ids' => 'required|array',
            'platform_ids.*' => 'exists:platforms,id',
        ]);
        
        $post = $this->postRepository->createPost($validated, $request->user()->id);
        
        return response()->json($post, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $post = $this->postRepository->getPostById($id);
        
        // Check if the post belongs to the authenticated user
        $this->authorize('view', $post);
        
        return response()->json($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $post = $this->postRepository->getPostById($id);
        
        // Check if the post belongs to the authenticated user
        $this->authorize('update', $post);
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'image_url' => 'nullable|url',
            'scheduled_time' => 'sometimes|date',
            'status' => 'sometimes|in:draft,scheduled,published',
            'platform_ids' => 'sometimes|array',
            'platform_ids.*' => 'exists:platforms,id',
        ]);
        
        $updatedPost = $this->postRepository->updatePost($id, $validated);
        
        return response()->json($updatedPost);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $post = $this->postRepository->getPostById($id);
        
        // Check if the post belongs to the authenticated user
        $this->authorize('delete', $post);
        
        $this->postRepository->deletePost($id);
        
        return response()->json(null, 204);
    }
}
