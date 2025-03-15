<?php

namespace App\Policies;

use App\Models\TrainingSession;
use App\Models\User;

class TrainingSessionPolicy
{
    public function update(User $user, TrainingSession $trainingSession): bool
    {
        return $user->id === $trainingSession->trainer_id;
    }

    public function delete(User $user, TrainingSession $trainingSession): bool
    {
        return $user->id === $trainingSession->trainer_id;
    }
}
