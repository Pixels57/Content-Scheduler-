<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'Twitter',
                'type' => 'Social Media',
                'character_limit' => 280,
                'status' => 'active'
            ],
            [
                'name' => 'Instagram',
                'type' => 'Social Media',
                'character_limit' => 2200,
                'status' => 'active'
            ],
            [
                'name' => 'Facebook',
                'type' => 'Social Media',
                'character_limit' => 63206,
                'status' => 'active'
            ],
            [
                'name' => 'LinkedIn',
                'type' => 'Professional Network',
                'character_limit' => 3000,
                'status' => 'active'
            ]
        ];
        
        foreach ($platforms as $platformData) {
            Platform::create($platformData);
        }
    }
} 