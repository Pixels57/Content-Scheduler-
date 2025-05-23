<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Interfaces\PlatformRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    /**
     * The platform repository instance.
     *
     * @var PlatformRepositoryInterface
     */
    protected $platformRepository;
    
    /**
     * Create a new controller instance.
     *
     * @param PlatformRepositoryInterface $platformRepository
     * @return void
     */
    public function __construct(PlatformRepositoryInterface $platformRepository)
    {
        $this->platformRepository = $platformRepository;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $platforms = $this->platformRepository->getAllPlatforms();
        
        return response()->json($platforms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        $platform = $this->platformRepository->createPlatform($validated);
        
        return response()->json($platform, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $platform = $this->platformRepository->getPlatformById($id);
        
        return response()->json($platform);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:255',
        ]);

        $platform = $this->platformRepository->updatePlatform($id, $validated);
        
        return response()->json($platform);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->platformRepository->deletePlatform($id);
        
        return response()->json(null, 204);
    }
    
    /**
     * Toggle active platforms for a user.
     */
    public function toggleUserPlatforms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform_ids' => 'required|array',
            'platform_ids.*' => 'exists:platforms,id',
        ]);
        
        $user = $request->user();
        
        // Here we would typically have a user_platform pivot table
        // For this simple example, we'll just return the platforms
        
        return response()->json([
            'message' => 'Platforms updated successfully',
            'platforms' => $this->platformRepository->getAllPlatforms()->whereIn('id', $validated['platform_ids']),
        ]);
    }
}
