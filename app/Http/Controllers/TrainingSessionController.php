<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use App\Models\AttendanceRecord;
use App\Models\TrainingCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class TrainingSessionController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $trainingSessions = TrainingSession::with(['attendance_records.user', 'category'])
            ->orderBy('start_time')
            ->get();

        $categories = TrainingCategory::all();

        return response()->json([
            'sessions' => $trainingSessions,
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'max_participants' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'category_id' => 'nullable|exists:training_categories,id',
        ]);

        $trainingSession = TrainingSession::create([
            'trainer_id' => Auth::id(),
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'max_participants' => $validated['max_participants'],
            'notes' => $validated['notes'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return response()->json($trainingSession->load(['attendance_records.user', 'category']));
    }

    public function update(Request $request, TrainingSession $trainingSession)
    {
        $this->authorize('update', $trainingSession);

        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'max_participants' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'category_id' => 'nullable|exists:training_categories,id',
        ]);

        $trainingSession->update([
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'max_participants' => $validated['max_participants'],
            'notes' => $validated['notes'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return response()->json($trainingSession->load(['attendance_records.user', 'category']));
    }

    public function destroy(TrainingSession $trainingSession)
    {
        $this->authorize('delete', $trainingSession);

        $trainingSession->delete();
        return response()->json(['message' => 'Training session deleted successfully']);
    }

    public function register(TrainingSession $trainingSession)
    {
        if ($trainingSession->attendance_records()->count() >= $trainingSession->max_participants) {
            return response()->json(['message' => 'Session is full'], 422);
        }

        $record = AttendanceRecord::firstOrCreate([
            'user_id' => Auth::id(),
            'training_session_id' => $trainingSession->id,
        ], [
            'status' => 'registered'
        ]);

        return response()->json($trainingSession->load(['attendance_records.user', 'category']));
    }

    public function unregister(TrainingSession $trainingSession)
    {
        $record = $trainingSession->attendance_records()
            ->where('user_id', Auth::id())
            ->first();

        if ($record) {
            $record->delete();
        }

        return response()->json($trainingSession->load(['attendance_records.user', 'category']));
    }
}
