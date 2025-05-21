<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get a user by ID
     *
     * @param int $userId
     * @return User
     */
    public function getUserById(int $userId): User;
    
    /**
     * Get a user by email
     *
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User;
    
    /**
     * Create a new user
     *
     * @param array $userData
     * @return User
     */
    public function createUser(array $userData): User;
    
    /**
     * Update an existing user
     *
     * @param int $userId
     * @param array $newDetails
     * @return User
     */
    public function updateUser(int $userId, array $newDetails): User;
    
    /**
     * Delete a user
     *
     * @param int $userId
     * @return bool
     */
    public function deleteUser(int $userId): bool;
} 