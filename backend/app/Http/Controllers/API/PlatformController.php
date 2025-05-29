<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Interfaces\PlatformRepositoryInterface;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Platform\TogglePlatformRequest;

class PlatformController extends Controller
{
    /**
     * The platform repository instance.
     *
     * @var PlatformRepositoryInterface
     */
    protected $platformRepository;
    
    /**
     * The activity logger instance.
     *
     * @var ActivityLogger
     */
    protected $activityLogger;
    
    /**
     * Create a new controller instance.
     *
     * @param PlatformRepositoryInterface $platformRepository
     * @param ActivityLogger $activityLogger
     * @return void
     */
    public function __construct(PlatformRepositoryInterface $platformRepository, ActivityLogger $activityLogger)
    {
        $this->platformRepository = $platformRepository;
        $this->activityLogger = $activityLogger;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $platforms = $this->platformRepository->getAllPlatforms();
        
        return response()->json([
            'data' => $platforms
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:platforms,name',
            'type' => 'required|string|max:255',
            'character_limit' => 'sometimes|integer|min:1',
        ]);

        $platform = $this->platformRepository->createPlatform($validated);
        
        // Log platform creation
        $this->activityLogger->log(
            'create', 
            'platform', 
            $platform->id, 
            'Created new platform: ' . $platform->name,
            $validated
        );
        
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
            'name' => 'sometimes|string|max:255|unique:platforms,name,' . $id,
            'type' => 'sometimes|string|max:255',
            'character_limit' => 'sometimes|integer|min:1',
        ]);

        $platform = $this->platformRepository->updatePlatform($id, $validated);
        
        // Log platform update
        $this->activityLogger->log(
            'update', 
            'platform', 
            $id, 
            'Updated platform: ' . $platform->name,
            $validated
        );
        
        return response()->json($platform);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $platform = $this->platformRepository->getPlatformById($id);
        
        // Log platform deletion before deleting
        $this->activityLogger->log(
            'delete', 
            'platform', 
            $id, 
            'Deleted platform: ' . $platform->name
        );
        
        $this->platformRepository->deletePlatform($id);
        
        return response()->json(null, 204);
    }
    
    /**
     * Toggle active platforms for a user.
     */
    public function toggleUserPlatforms(TogglePlatformRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $user = $request->user();
        
        // Here we would typically have a user_platform pivot table
        // For this simple example, we'll just return the platforms
        foreach ($validated['platform_ids'] as $platformId) {
            $platform = $this->platformRepository->getPlatformById($platformId);
            if ($platform->status == 'active') {
                $this->platformRepository->updatePlatform($platformId, ['status' => 'inactive']);
            } else {
                $this->platformRepository->updatePlatform($platformId, ['status' => 'active']);
            }
        }

        $platforms = $this->platformRepository->getAllPlatforms();
        
        // Log platform settings change
        $this->activityLogger->logPlatformSettingsChange($validated['platform_ids']);
        
        return response()->json([
            'message' => 'Platforms updated successfully',
            'platforms' => $platforms,
        ]);
    }
}
