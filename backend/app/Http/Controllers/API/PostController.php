<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\StorePostRequest;
use App\Http\Requests\Posts\UpdatePostRequest;
use App\Http\Requests\Posts\DeletePostRequest;
use App\Interfaces\PostRepositoryInterface;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    /**
     * The post repository instance.
     *
     * @var PostRepositoryInterface
     */
    protected $postRepository;
    
    /**
     * The activity logger instance.
     *
     * @var ActivityLogger
     */
    protected $activityLogger;
    
    /**
     * Create a new controller instance.
     *
     * @param PostRepositoryInterface $postRepository
     * @param ActivityLogger $activityLogger
     * @return void
     */
    public function __construct(PostRepositoryInterface $postRepository, ActivityLogger $activityLogger)
    {
        $this->postRepository = $postRepository;
        $this->activityLogger = $activityLogger;
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
    public function store(StorePostRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $post = $this->postRepository->createPost($validated, $request->user()->id);
        
        // Log the post creation
        $this->activityLogger->logPostCreation($post->id, $validated);
        
        return response()->json($post, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $post = $this->postRepository->getPostById($id);
        
        // Check if the post belongs to the authenticated user
        if($post->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $post
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, string $id): JsonResponse
    {
        $post = $this->postRepository->getPostById($id);
        
        // Check if the post belongs to the authenticated user
        if($post->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validated();
        
        $updatedPost = $this->postRepository->updatePost($id, $validated);
        
        // Log the post update
        $this->activityLogger->logPostUpdate($id, $validated);
        
        return response()->json([
            'status' => 'success',
            'data' => $updatedPost
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeletePostRequest $request, string $id): JsonResponse
    {
        $post = $this->postRepository->getPostById($id);
        
        // Check if the post belongs to the authenticated user
        if($post->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();
        
        // Log the post deletion before actually deleting
        $this->activityLogger->logPostDeletion($id, $post->title);
        
        $this->postRepository->deletePost($id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Post deleted successfully'
        ], 200);
    }

    /**
     * Get post analytics data
     */
    public function analytics(Request $request): JsonResponse
    {
        $analytics = $this->postRepository->getAnalytics($request->user()->id, []);
        
        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }
}
