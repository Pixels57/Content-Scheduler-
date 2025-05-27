<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\StorePostRequest;
use App\Http\Requests\Posts\UpdatePostRequest;
use App\Http\Requests\Posts\DeletePostRequest;
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
    public function store(StorePostRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
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
    public function update(UpdatePostRequest $request, string $id): JsonResponse
    {
        $post = $this->postRepository->getPostById($id);
        
        // Check if the post belongs to the authenticated user
        $this->authorize('update', $post);
        
        $validated = $request->validated();
        
        $updatedPost = $this->postRepository->updatePost($id, $validated);
        
        return response()->json($updatedPost);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeletePostRequest $request, string $id): JsonResponse
    {
        $post = $this->postRepository->getPostById($id);
        
        // Check if the post belongs to the authenticated user
        $this->authorize('delete', $post);

        $validated = $request->validated();
        
        $this->postRepository->deletePost($id);
        
        return response()->json(null, 204);
    }
}
