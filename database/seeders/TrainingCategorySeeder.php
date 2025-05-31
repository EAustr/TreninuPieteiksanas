<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TrainingCategory;

class TrainingCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Serve & Attack Training',
                'description' => 'Focus on serving techniques and attacking skills including spikes, tips, and roll shots.',
                'color' => '#EF4444', // Red
            ],
            [
                'name' => 'Precision Training',
                'description' => 'Develop accuracy and control in all aspects of the game, including serving, setting, and hitting.',
                'color' => '#3B82F6', // Blue
            ],
            [
                'name' => 'Passing & Control Training',
                'description' => 'Improve ball control, passing techniques, and first contact skills.',
                'color' => '#10B981', // Green
            ],
            [
                'name' => 'Block & Defense Training',
                'description' => 'Enhance blocking techniques and defensive positioning, including digging and floor defense.',
                'color' => '#8B5CF6', // Purple
            ],
        ];

        foreach ($categories as $category) {
            TrainingCategory::create($category);
        }
    }
}
