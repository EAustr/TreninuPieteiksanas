<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingSessionController extends Controller
{
    private GoogleCalendarService $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    // ... your existing methods ...

    public function syncWithGoogleCalendar(TrainingSession $trainingSession)
    {
        try {
            $user = Auth::user();
            if (!$user->google_calendar_token) {
                return response()->json(['message' => 'Google Calendar not connected'], 400);
            }

            $this->calendarService->setAccessToken(json_decode($user->google_calendar_token, true));

            // Create or update event in Google Calendar
            $eventData = [
                'title' => 'Training Session',
                'description' => $trainingSession->notes ?? 'Training session at the gym',
                'start_time' => $trainingSession->start_time,
                'end_time' => $trainingSession->end_time,
            ];

            // Store the Google Calendar event ID with the training session
            if (!$trainingSession->google_calendar_event_id) {
                $event = $this->calendarService->createEvent($eventData);
                $trainingSession->update([
                    'google_calendar_event_id' => $event->getId()
                ]);
            } else {
                $this->calendarService->updateEvent($trainingSession->google_calendar_event_id, $eventData);
            }

            return response()->json(['message' => 'Successfully synced with Google Calendar']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to sync with Google Calendar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
