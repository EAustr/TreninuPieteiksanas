<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class AttendanceRecordController extends Controller
{
    use AuthorizesRequests;

    public function update(Request $request, AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:present,absent,late,registered'],
        ]);

        $attendanceRecord->update([
            'status' => $validated['status'],
        ]);

        return response()->json($attendanceRecord->load('user'));
    }

    public function heatmap()
    {
        $user = Auth::user();

        // Get attendance records for the last 12 weeks where user actually attended sessions
        $startDate = now()->subWeeks(12)->startOfDay();

        $records = AttendanceRecord::where('user_id', $user->id)
            ->where('status', 'present')
            ->whereHas('trainingSession', function ($query) use ($startDate) {
                $query->where('start_time', '>=', $startDate);
            })
            ->with('trainingSession')
            ->get()
            ->map(function ($record) {
                return [
                    'date' => $record->trainingSession->start_time->format('Y-m-d'),
                    'attended' => true
                ];
            });

        return response()->json($records);
    }

    public function attendedTrainings(Request $request){
        $user = $request->user();
        $trainings = DB::table('attendance_records')
            ->join('training_sessions', 'attendance_records.training_session_id', '=', 'training_sessions.id')
            ->where('attendance_records.user_id', $user->id)
            ->select('training_sessions.*')
            ->get();

        return response()->json($trainings);
    }
}