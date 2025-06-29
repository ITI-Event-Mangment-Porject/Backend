<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\StoreEventSessionRequest;
use App\Http\Requests\UpdateEventSessionRequest;
use App\Models\Event\EventSession;
use App\Models\Event\Event;

class EventSessionController extends BaseApiController
{
    //
    public function index(Event $event_flexible)
    {
        // Logic to list event sessions
        $sessions = EventSession::where('event_id', $event_flexible->id)->get();
        if ($sessions->isEmpty()) {
            return $this->sendError('No sessions found for that event',[], 404);
        }
        return $this->sendResponse($sessions, 'Sessions retrieved successfully', 200);
    }
    public function show(Event $event_flexible,$sessionId)
    {
        // Logic to show a specific event session
        $session = $event_flexible->sessions()->where('id', $sessionId)->first();

        if (!$session) {
            return $this->sendError('Session not found for that event', [], 404);
        }
        return $this->sendResponse($session, 'Session retrieved successfully', 200);
    }
    
    public function createSession(StoreEventSessionRequest $request, Event $event_flexible)
    {
        try {
            $validatedData = $request->validated();
            
            if ($request->hasFile('speaker_image')) {
                $path = $request->file('speaker_image')->store('sessions/speakers', 'public');
                $validatedData['speaker_image'] = '/storage/' . $path;
            }
            
            $session = $event_flexible->sessions()->create([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'] ?? null,
                'speaker_name' => $validatedData['speaker_name'] ?? null,
                'speaker_bio' => $validatedData['speaker_bio'] ?? null,
                'speaker_image' => $validatedData['speaker_image'] ?? null,
                'start_time' => $validatedData['start_time'],
                'end_time' => $validatedData['end_time'],
                'location' => $validatedData['location'] ?? null,
                'session_order' => $validatedData['session_order'] ?? 1,
                'is_break' => $validatedData['is_break'] ?? false,
            ]);

            return $this->sendResponse($session, 'Session created for event', 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to create session', ['error' => $e->getMessage()], 500);
        }
    }
    public function update(UpdateEventSessionRequest $request,Event $event_flexible, $session)
    {
        try {
            $validatedData = $request->validated();
            
            if ($request->hasFile('speaker_image')) {
                $path = $request->file('speaker_image')->store('sessions/speakers', 'public');
                $validatedData['speaker_image'] = '/storage/' . $path;
            }
            
            if (empty($validatedData)) {
                return $this->sendError('No data provided for update.', [], 422);
            }
            
            $sessionToUpdate = $event_flexible->sessions()->findOrFail($session);
            $sessionToUpdate->update($validatedData);
            

            return $this->sendResponse($sessionToUpdate, 'Session updated successfully', 200);
        } catch (\Exception $e) {
            return $this->sendError('Failed to update session', ['error' => $e->getMessage()], 500);
        }
        
    }
    
    public function destroy(Event $event_flexible, $session)
    {
        try {
            $sessionToDelete = $event_flexible->sessions()->findOrFail($session);
            $sessionToDelete->delete();
            return $this->sendResponse(null, 'Session deleted successfully', 204);
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete session', ['error' => $e->getMessage()], 500);
        }
    }
}
