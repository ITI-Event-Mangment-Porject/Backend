<?php

namespace App\Http\Controllers;

use App\Models\Feedback_and_Analytics\FeedbackForm;
use App\Models\Feedback_and_Analytics\FeedbackResponse;
use App\Models\Event\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    
    // Get feedback forms for a specific event
     
    public function getEventFeedbackForms($eventId): JsonResponse
    {
        $event = Event::find($eventId);
        if (!$event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        $forms = FeedbackForm::where('event_id', $eventId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($forms);
    }

    
      //Create new feedback form (Admin only)
     
    public function createFeedbackForm(Request $request, $eventId): JsonResponse
    {
        // Check event exists
        if (!Event::find($eventId)) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        // Check admin permission
        if (!auth()->user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'form_config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $form = FeedbackForm::create([
            'event_id' => $eventId,
            'title' => $request->title,
            'description' => $request->description,
            'form_config' => $request->form_config,
            'created_by' => auth()->id(),
        ]);

        return response()->json($form, 201);
    }

    
    //  Submit feedback response (Students)
    
    public function submitFeedbackResponse(Request $request, $formId): JsonResponse
    {
        $form = FeedbackForm::find($formId);
        if (!$form || !$form->is_active) {
            return response()->json(['error' => 'Form not found or inactive'], 404);
        }

        // Check if user already submitted response
        if (FeedbackResponse::where('form_id', $formId)->where('user_id', auth()->id())->exists()) {
            return response()->json(['error' => 'Response already submitted'], 409);
        }

        $validator = Validator::make($request->all(), [
            'responses' => 'required|array',
            'overall_rating' => 'nullable|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $response = FeedbackResponse::create([
            'form_id' => $formId,
            'user_id' => auth()->id(),
            'event_id' => $form->event_id,
            'responses' => $request->responses,
            'overall_rating' => $request->overall_rating,
        ]);

        return response()->json($response, 201);
    }

    
    //  Get feedback responses (Admin only)
     
    public function getFeedbackResponses($formId): JsonResponse
    {
        // Check admin permission
        if (!auth()->user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $form = FeedbackForm::find($formId);
        if (!$form) {
            return response()->json(['error' => 'Form not found'], 404);
        }

        $responses = FeedbackResponse::where('form_id', $formId)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'form' => $form,
            'responses' => $responses,
            'total_responses' => $responses->count(),
            'average_rating' => $responses->whereNotNull('overall_rating')->avg('overall_rating')
        ]);
    }

    
    //   Toggle form status (activate/deactivate)
     
    public function toggleFeedbackForm($formId): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $form = FeedbackForm::find($formId);
        if (!$form) {
            return response()->json(['error' => 'Form not found'], 404);
        }

        $form->update(['is_active' => !$form->is_active]);

        return response()->json($form);
    }
}