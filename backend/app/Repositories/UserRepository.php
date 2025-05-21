<?php

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get a user by ID
     *
     * @param int $userId
     * @return User
     */
    public function getUserById(int $userId): User
    {
        return User::findOrFail($userId);
    }
    
    /**
     * Get a user by email
     *
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
    
    /**
     * Create a new user
     *
     * @param array $userData
     * @return User
     */
    public function createUser(array $userData): User
    {
        return User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);
    }
    
    /**
     * Update an existing user
     *
     * @param int $userId
     * @param array $newDetails
     * @return User
     */
    public function updateUser(int $userId, array $newDetails): User
    {
        $user = User::findOrFail($userId);
        
        if (isset($newDetails['name'])) {
            $user->name = $newDetails['name'];
        }
        
        if (isset($newDetails['email'])) {
            $user->email = $newDetails['email'];
        }
        
        if (isset($newDetails['password'])) {
            $user->password = Hash::make($newDetails['password']);
        }
        
        $user->save();
        
        return $user;
    }
    
    /**
     * Delete a user
     *
     * @param int $userId
     * @return bool
     */
    public function deleteUser(int $userId): bool
    {
        $user = User::findOrFail($userId);
        return $user->delete();
    }
} 