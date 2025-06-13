<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event\Event; // Assuming you have an Event model
use App\Http\Requests\StoreEventRequest; // Assuming you have a StoreEventRequest for validation
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Requests\UpdateEventRequest; // Assuming you have an UpdateEventRequest for validation
use Illuminate\Http\Response;
use App\Http\Controllers\API\BaseApiController; // Assuming you have a BaseApiController for response handling
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Http\Filters\Event\EventStatusFilter;
use App\Http\Filters\Event\EventDateRangeFilter;
use App\Http\Filters\Event\EventTypeFilter;
use App\Http\Filters\Event\EventRegistrationStatusFilter; // Assuming you have a filter for registration status





class EventController extends BaseApiController
{
    //
    public function index(Request $request)
    {
        $events = QueryBuilder::for(Event::class)
            ->allowedFilters([
                // Simple filters
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('visibility_type'),
                AllowedFilter::partial('title'),
                AllowedFilter::partial('description'),
                AllowedFilter::partial('location'),
                
                // Custom filters for complex logic
                AllowedFilter::custom('status_group', new EventStatusFilter),
                AllowedFilter::custom('date_range', new EventDateRangeFilter),
                AllowedFilter::custom('event_type', new EventTypeFilter),
                
                // Relationship filters
                AllowedFilter::exact('creator.name'),
                AllowedFilter::scope('active'),
                
            ])
            ->allowedSorts([
                'title',
                'start_date',
                'end_date',
                'created_at',
                'updated_at',
                'registration_deadline',
                AllowedSort::field('creator_name', 'creator.name'),
            ])
            ->allowedIncludes([
                'creator',
                'sessions',
                'registrations',
                'staffAssignments',
            ])
            ->with(['creator:id,first_name,last_name']) // Always include creator info
            ->paginate($request->get('per_page', 15));
        // Check if events were found
        if ($events->count() == 0) {
            return $this->sendError('No events found', [], 404);
        }

        return $this->sendResponse($events, 'Events retrieved successfully', 200);
    }

    public function show(Event $event_flexible)
    {
        $event = QueryBuilder::for(Event::class)
            ->where('id', $event_flexible->id)
            ->allowedIncludes([
                'creator',
                'sessions',
                'registrations',
                'staffAssignments',
                'jobFairParticipations',
                'feedbackForms',
                'aiInsights',
            ])
            ->first();
        if (!$event) {
            return $this->sendError('Event not found', [], 404);
        }

        return $this->sendResponse($event, 'Event retrieved successfully');
    }

    public function store(StoreEventRequest $request)
    {
        $validatedData = $request->validated();
        $event = Event::create($validatedData);

        if (!$event) {
            return $this->sendError('Failed to create event', [], 500);
        }

        return $this->sendResponse($event, 'Event created successfully', 201);
    }

    public function update(UpdateEventRequest $request, Event $event_flexible)
    {
        $validatedData = $request->validated();

        $updated = $event_flexible->update($validatedData);

        if (!$updated) {
            return $this->sendError('Failed to update event', [], 500);
        }

        return $this->sendResponse($event_flexible, 'Event updated successfully', 200);
    }
    
    public function destroy(Event $event_flexible)
    {
        // This method will delete an event
        $deleted = $event_flexible->delete();
        if (!$deleted) {
            return $this->sendError('Failed to delete event', [], 500);
        }
        
        return $this->sendResponse([], 'Event deleted successfully', 200);
    }
    
    public function publish(Event $event_flexible)
    {
        // This method will publish an event
        $event_flexible->status = 'published';
        $saved = $event_flexible->save();

        if (!$saved) {
            return $this->sendError('Failed to publish event', [], 500);
        }

        return $this->sendResponse($event_flexible, 'Event published successfully', 200);
    }
    public function archive(Event $event_flexible)
    {
        // This method will archive an event
        $event_flexible->status = 'archived';
        $event_flexible->archived_at = now();
        $saved = $event_flexible->save();

        if (!$saved) {
            return $this->sendError('Failed to archive event', [], 500);
        }

        return $this->sendResponse($event_flexible, 'Event archived successfully', 200);
    }
    public function banner(Event $event_flexible)
    {
        // This method will return the banner image of an event
        if (!$event_flexible->banner_image) {
            return $this->sendError('Banner image not found', [], 404);
        }
        // Assuming banner_image is a URL or path to the image
        if (!filter_var($event_flexible->banner_image, FILTER_VALIDATE_URL)) {
            return $this->sendError('Invalid banner image URL', [], 400);
        }
        // Return the banner image URL
        return $this->sendResponse([
            'banner_image' => $event_flexible->banner_image
        ], 'Banner image retrieved successfully');
    }
}
