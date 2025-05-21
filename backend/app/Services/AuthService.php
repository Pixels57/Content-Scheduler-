<?php

namespace App\Services;

use App\Interfaces\AuthServiceInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    /**
     * The user repository implementation.
     *
     * @var UserRepositoryInterface
     */
    protected $userRepository;
    
    /**
     * Create a new service instance.
     *
     * @param UserRepositoryInterface $userRepository
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    /**
     * Register a new user
     *
     * @param array $userData
     * @return array
     */
    public function register(array $userData): array
    {
        $user = $this->userRepository->createUser($userData);
        
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return [
            'user' => $user,
            'token' => $token,
        ];
    }
    
    /**
     * Attempt to log in a user
     *
     * @param array $credentials
     * @return array
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        $user = $this->userRepository->getUserByEmail($credentials['email']);
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return [
            'user' => $user,
            'token' => $token,
        ];
    }
    
    /**
     * Log out the current user
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        $user->currentAccessToken()->delete();
        return true;
    }
    
    /**
     * Update user profile
     *
     * @param User $user
     * @param array $profileData
     * @return User
     */
    public function updateProfile(User $user, array $profileData): User
    {
        return $this->userRepository->updateUser($user->id, $profileData);
    }
} 