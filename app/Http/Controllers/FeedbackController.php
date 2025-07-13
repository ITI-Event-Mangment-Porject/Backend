<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseApiController;
use App\Models\FeedbackAndAnalytics\FeedbackForm;
use App\Models\FeedbackAndAnalytics\FeedbackResponse;
use App\Models\Event\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class FeedbackController extends BaseApiController
{
    public function getEventFeedbackForms($eventId): JsonResponse
    {
        $event = Event::find($eventId);
        if (!$event) {
            return $this->sendError('Event not found.', [], 404);
        }

        $form = FeedbackForm::where('event_id', $eventId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$form) {
            return $this->sendError('No active feedback form found for this event.', [], 404);
        }

        return $this->sendResponse($form, 'Event feedback form retrieved successfully.');
    }

    public function createFeedbackForm(Request $request, $eventId): JsonResponse
    {
        $event = Event::find($eventId);
        if (!$event) {
            return $this->sendError('Event not found.', [], 404);
        }

        $existingForm = FeedbackForm::where('event_id', $eventId)
            ->where('is_active', true)
            ->first();

        if ($existingForm) {
            return $this->sendError('An active feedback form already exists for this event.', [], 409);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'form_config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $form = FeedbackForm::create([
            'event_id' => $eventId,
            'title' => $request->title,
            'description' => $request->description,
            'form_config' => $request->form_config,
            'created_by' => auth()->id(),
        ]);

        return $this->sendResponse($form, 'Feedback form created successfully.', 201);
    }

    public function submitFeedbackResponse(Request $request, $formId): JsonResponse
    {
        $form = FeedbackForm::find($formId);
        if (!$form || !$form->is_active) {
            return $this->sendError('Form not found or inactive.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'responses' => 'required|array',
            'overall_rating' => 'nullable|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $existingResponse = FeedbackResponse::where('form_id', $formId)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingResponse) {
            return $this->sendError('You have already submitted a response for this form.', [], 409);
        }

        $response = FeedbackResponse::create([
            'form_id' => $formId,
            'user_id' => auth()->id(),
            'event_id' => $form->event_id,
            'responses' => $request->responses,
            'overall_rating' => $request->overall_rating,
        ]);

        return $this->sendResponse($response, 'Feedback response submitted successfully.', 201);
    }

    public function getFeedbackResponses($eventId): JsonResponse
    {
        $event = Event::find($eventId);
        if (!$event) {
            return $this->sendError('Event not found.', [], 404);
        }

        $form = FeedbackForm::where('event_id', $eventId)
            ->first();

        if (!$form) {
            return $this->sendError('No active feedback form found for this event.', [], 404);
        }

        $responses = FeedbackResponse::where('form_id', $form->id)
            ->with('user:id,first_name,last_name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(6);

        $responses->getCollection()->transform(function ($response) {
            $response->responses = is_array($response->responses)
                ? json_encode($response->responses)
                : $response->responses;
            return $response;
        });

        $averageRating = FeedbackResponse::where('form_id', $form->id)
            ->whereNotNull('overall_rating')
            ->avg('overall_rating');

        return $this->sendResponse([
            'form' => $form,
            'responses' => $responses,
            'total_responses' => $responses->total(),
            'average_rating' => $averageRating
        ], 'Feedback responses retrieved successfully for event.');
    }

    public function toggleFeedbackForm($formId): JsonResponse
    {
        $form = FeedbackForm::find($formId);
        if (!$form) {
            return $this->sendError('Form not found.', [], 404);
        }

        $form->update(['is_active' => !$form->is_active]);

        return $this->sendResponse($form, 'Feedback form status toggled successfully.');
    }
}
