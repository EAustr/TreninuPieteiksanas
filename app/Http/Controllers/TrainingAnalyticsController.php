<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TrainingAnalyticsController extends Controller
{
    public function index(): JsonResponse
    {
        $totalSessions = TrainingSession::count();
        $totalParticipants = AttendanceRecord::distinct('user_id')->count('user_id');

        $presentAttendance = AttendanceRecord::where('status', 'present')->count();
        $totalAttendance = AttendanceRecord::count();
        $averageAttendance = $totalAttendance > 0
            ? round(($presentAttendance / $totalAttendance) * 100)
            : 0;

        $upcomingSessions = TrainingSession::where('start_time', '>', now())->count();

        return response()->json([
            'totalSessions' => $totalSessions,
            'totalParticipants' => $totalParticipants,
            'averageAttendance' => $averageAttendance,
            'upcomingSessions' => $upcomingSessions,
        ]);
    }
}
