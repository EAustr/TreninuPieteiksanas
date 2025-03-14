<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TrainingSessionController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return TrainingSession::with(['trainer', 'attendanceRecords.user'])
            ->orderBy('start_time')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'max_participants' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        $startDateTime = $validated['date'] . ' ' . $validated['start_time'];
        $endDateTime = $validated['date'] . ' ' . $validated['end_time'];

        $session = TrainingSession::create([
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'max_participants' => $validated['max_participants'],
            'notes' => $validated['notes'],
            'trainer_id' => Auth::id()
        ]);

        return $session->load(['trainer', 'attendanceRecords.user']);
    }

    public function update(Request $request, TrainingSession $trainingSession)
    {
        $this->authorize('update', $trainingSession);

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'max_participants' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        $startDateTime = $validated['date'] . ' ' . $validated['start_time'];
        $endDateTime = $validated['date'] . ' ' . $validated['end_time'];

        $trainingSession->update([
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'max_participants' => $validated['max_participants'],
            'notes' => $validated['notes']
        ]);

        return $trainingSession->load(['trainer', 'attendanceRecords.user']);
    }

    public function destroy(TrainingSession $trainingSession)
    {
        $this->authorize('delete', $trainingSession);
        
        $trainingSession->delete();
        return response()->json(['message' => 'Training session deleted successfully']);
    }

    public function register(TrainingSession $trainingSession)
    {
        if ($trainingSession->attendanceRecords()->count() >= $trainingSession->max_participants) {
            return response()->json(['message' => 'Training session is full'], 422);
        }

        $existingRecord = $trainingSession->attendanceRecords()
            ->where('user_id', Auth::id())
            ->first();

        if ($existingRecord) {
            return response()->json(['message' => 'Already registered for this session'], 422);
        }

        $record = $trainingSession->attendanceRecords()->create([
            'user_id' => Auth::id(),
            'status' => 'registered'
        ]);

        return $record->load('user');
    }

    public function cancelRegistration(TrainingSession $trainingSession)
    {
        $record = $trainingSession->attendanceRecords()
            ->where('user_id', Auth::id())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Not registered for this session'], 404);
        }

        $record->delete();
        return response()->json(['message' => 'Registration cancelled successfully']);
    }
} 