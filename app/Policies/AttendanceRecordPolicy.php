<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->trainingSession->trainer_id;
    }
} 