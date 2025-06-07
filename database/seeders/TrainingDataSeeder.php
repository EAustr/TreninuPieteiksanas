<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TrainingSession;
use App\Models\TrainingCategory;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class TrainingDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing trainer
        $trainer = User::where('role', 'trainer')->first();
        if (!$trainer) {
            $this->command->error('No trainer found in the database. Please run the DatabaseSeeder first.');
            return;
        }

        // Create additional athlete users
        $athleteNames = [
            'Emma Wilson',
            'James Smith',
            'Sophia Chen',
            'Lucas Rodriguez',
            'Olivia Kim',
            'Noah Johnson',
            'Ava Martinez',
            'Ethan Brown',
            'Isabella Lee',
            'Mason Garcia',
            'Mia Patel',
            'Alexander Wong',
            'Charlotte Taylor',
            'Benjamin Anderson',
            'Amelia White'
        ];

        foreach ($athleteNames as $name) {
            User::firstOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $name)) . '@example.com'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => 'athlete',
                ]
            );
        }

        // Get all users for attendance
        $users = User::where('role', 'athlete')->get();
        if ($users->isEmpty()) {
            $this->command->error('No athletes found in the database.');
            return;
        }

        // Get existing categories
        $categories = TrainingCategory::all();
        if ($categories->isEmpty()) {
            $this->command->error('No training categories found. Please run the TrainingCategorySeeder first.');
            return;
        }

        // Create training sessions for the next 30 days
        $startDate = Carbon::now();
        for ($i = 0; $i < 30; $i++) {
            $sessionDate = $startDate->copy()->addDays($i);

            // Create 2 sessions per day
            for ($j = 0; $j < 2; $j++) {
                $startTime = $sessionDate->copy()->setHour(10 + ($j * 4))->setMinute(0);
                $endTime = $startTime->copy()->addHours(2);

                $session = TrainingSession::create([
                    'trainer_id' => $trainer->id,
                    'category_id' => $categories->random()->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'max_participants' => 12,
                    'notes' => "Training session {$i}-{$j}",
                ]);

                // Create a full session on the first day
                if ($i === 0 && $j === 0) {
                    // Register all 12 participants
                    $participants = $users->take(12);
                    foreach ($participants as $user) {
                        AttendanceRecord::create([
                            'user_id' => $user->id,
                            'training_session_id' => $session->id,
                            'status' => 'registered',
                        ]);
                    }
                } else {
                    // Random number of participants for other sessions
                    $participantCount = rand(3, min(12, $users->count()));
                    $randomUsers = $users->random($participantCount);
                    foreach ($randomUsers as $user) {
                        AttendanceRecord::create([
                            'user_id' => $user->id,
                            'training_session_id' => $session->id,
                            'status' => 'registered',
                        ]);
                    }
                }
            }
        }
    }
}
