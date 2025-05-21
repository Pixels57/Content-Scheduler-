<?php

namespace App\Interfaces;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Register a new user
     *
     * @param array $userData
     * @return array
     */
    public function register(array $userData): array;
    
    /**
     * Attempt to log in a user
     *
     * @param array $credentials
     * @return array
     */
    public function login(array $credentials): array;
    
    /**
     * Log out the current user
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool;
    
    /**
     * Update user profile
     *
     * @param User $user
     * @param array $profileData
     * @return User
     */
    public function updateProfile(User $user, array $profileData): User;
} 