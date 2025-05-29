<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Interfaces\AuthServiceInterface;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * The auth service instance.
     *
     * @var AuthServiceInterface
     */
    protected $authService;
    
    /**
     * The activity logger instance.
     *
     * @var ActivityLogger
     */
    protected $activityLogger;
    
    /**
     * Create a new controller instance.
     *
     * @param AuthServiceInterface $authService
     * @param ActivityLogger $activityLogger
     * @return void
     */
    public function __construct(AuthServiceInterface $authService, ActivityLogger $activityLogger)
    {
        $this->authService = $authService;
        $this->activityLogger = $activityLogger;
    }
    
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = $this->authService->register($validated);
        
        // Log the login after registration
        $this->activityLogger->logLogin($result['user']->id);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $result['user'],
            'token' => $result['token'],
        ], 201);
    }

    /**
     * Log in the user.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $result = $this->authService->login($validated);
        
        // Log the login activity
        $this->activityLogger->logLogin($result['user']->id);

        return response()->json([
            'message' => 'User logged in successfully',
            'user' => $result['user'],
            'token' => $result['token'],
        ]);
    }

    /**
     * Log out the user.
     */
    public function logout(Request $request): JsonResponse
    {
        // Log the logout activity before actually logging out
        $this->activityLogger->logLogout();
        
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'User logged out successfully',
        ]);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        $user = $this->authService->updateProfile($request->user(), $validated);
        
        // Log the profile update
        $this->activityLogger->logProfileUpdate($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }
}
