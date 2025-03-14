<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AttendanceRecordController extends Controller
{
    use AuthorizesRequests;

    public function update(Request $request, AttendanceRecord $attendanceRecord)
    {
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validate([
            'status' => 'required|in:registered,attended,absent'
        ]);

        $attendanceRecord->update([
            'status' => $validated['status']
        ]);

        return $attendanceRecord->load('user');
    }
} 