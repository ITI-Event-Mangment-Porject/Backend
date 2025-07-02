<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event\Event;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Controllers\API\BaseApiController;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Http\Filters\Event\EventStatusFilter;
use App\Http\Filters\Event\EventDateRangeFilter;
use App\Http\Filters\Event\EventTypeFilter;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class EventController extends BaseApiController
{
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 6);
            $page = (int) $request->get('page', 1);

            $eventsQuery = QueryBuilder::for(Event::class)
                ->allowedFilters([
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('type'),
                    AllowedFilter::exact('visibility_type'),
                    AllowedFilter::partial('title'),
                    AllowedFilter::partial('description'),
                    AllowedFilter::partial('location'),
                    AllowedFilter::custom('status_group', new EventStatusFilter),
                    AllowedFilter::custom('date_range', new EventDateRangeFilter),
                    AllowedFilter::custom('event_type', new EventTypeFilter),
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
                ->with(['creator:id,first_name,last_name']);

            $events = $eventsQuery->paginate($perPage, ['*'], 'page', $page);

            if ($events->count() === 0) {
                return $this->sendError('No events found', [], 404);
            }

            return $this->sendResponse($events, 'Events retrieved successfully');
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Server Error: ' . $e->getMessage(), [], 500);
        }
    }

    public function show(Event $event_flexible)
    {
        try {
            $event = QueryBuilder::for(Event::class)
                ->where('id', $event_flexible->id)
                ->orWhere('slug', $event_flexible->slug)
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
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Server Error: ' . $e->getMessage(), [], 500);
        }
    }

    public function store(StoreEventRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $event = Event::create($validatedData);

            if (!$event) {
                return $this->sendError('Failed to create event', [], 500);
            }

            return $this->sendResponse($event, 'Event created successfully', 201);
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Server Error: ' . $e->getMessage(), [], 500);
        }
    }

    public function update(UpdateEventRequest $request, Event $event_flexible)
    {
        try {
            $validatedData = $request->validated();

            if (in_array($event_flexible->status, ['ongoing', 'completed'])) {
                return $this->sendError('Cannot update an ongoing or completed event', [], 400);
            }

            $updated = $event_flexible->update($validatedData);

            if (!$updated) {
                return $this->sendError('Failed to update event', [], 500);
            }

            return $this->sendResponse($event_flexible, 'Event updated successfully');
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Server Error: ' . $e->getMessage(), [], 500);
        }
    }

    public function destroy(Event $event_flexible)
    {
        try {
            if ($event_flexible->sessions()->count() > 0) {
                return $this->sendError('Cannot delete event with existing sessions', [], 409);
            }

            $event_flexible->delete();
            return $this->sendResponse([], 'Event deleted successfully', 200);
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Server Error: ' . $e->getMessage(), [], 500);
        }
    }

    public function publish(Event $event_flexible)
    {
        try {
            if ($event_flexible->status === 'published') {
                return $this->sendError('Event is already published', [], 400);
            }
            if ($event_flexible->status === 'archived') {
                return $this->sendError('Cannot publish an archived event', [], 400);
            }
            if (in_array($event_flexible->status, ['ongoing', 'completed'])) {
                return $this->sendError('Cannot publish an ongoing or completed event', [], 400);
            }

            $event_flexible->status = 'published';
            $saved = $event_flexible->save();

            if (!$saved) {
                return $this->sendError('Failed to publish event', [], 500);
            }

            return $this->sendResponse($event_flexible, 'Event published successfully');
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Server Error: ' . $e->getMessage(), [], 500);
        }
    }
    public function unpublish(Event $event_flexible)
    {
        try {
            if ($event_flexible->status === 'draft') {
                return $this->sendError('Event is already still unpublished', [], 400);
            }
            if ($event_flexible->status === 'archived') {
                return $this->sendError('Event is archived can\'t be unpublished', [], 400);
            }
            if (in_array($event_flexible->status, ['ongoing', 'completed'])) {
                return $this->sendError('Cannot unpublish an ongoing or completed event', [], 400);
            }

            $event_flexible->status = 'draft';
            $saved = $event_flexible->save();

            if (!$saved) {
                return $this->sendError('Failed to unpublish event', [], 500);
            }

            return $this->sendResponse($event_flexible, 'Event returned to draft status successfully');
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Server Error: ' . $e->getMessage(), [], 500);
        }
    }

    public function archive(Event $event_flexible)
    {
        try {
            if ($event_flexible->status === 'archived') {
                return $this->sendError('Event is already archived', [], 400);
            }
            if ($event_flexible->status === 'published') {
                return $this->sendError('Cannot archive a published event', [], 400);
            }
            if ($event_flexible->status === 'ongoing') {
                return $this->sendError('Cannot archive an ongoing event', [], 400);
            }

            $event_flexible->status = 'archived';
            $event_flexible->archived_at = now();
            $saved = $event_flexible->save();

            if (!$saved) {
                return $this->sendError('Failed to archive event', [], 500);
            }

            return $this->sendResponse($event_flexible, 'Event archived successfully');
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Server Error: ' . $e->getMessage(), [], 500);
        }
    }

    public function getBanner(Event $event_flexible)
    {
        try {
            if (!$event_flexible->banner_image) {
                return $this->sendError('Banner image not found', [], 404);
            }

            return $this->sendResponse([
                'banner_image' => $event_flexible->banner_image
            ], 'Banner image retrieved successfully');
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Server Error: ' . $e->getMessage(), [], 500);
        }
    }
    
    public function uploadBanner(Request $request, $event_id)
    {
        try {
            $request->validate([
                'banner' => 'required|image|mimes:jpeg,png,jpg|max:2048'
            ]);
        
            $event = Event::find($event_id);
        
            if (!$event) {
                return $this->sendError('Event not found', [], 404);
            }
        
            if ($request->hasFile('banner')) {
                // Store the new banner
                $path = $request->file('banner')->store('events/banners', 'public');
            
                // Update the event with the new banner path
                $event->banner_image = '/storage/' . $path;
                $event->save();
            
                return $this->sendResponse([
                    'banner_image' => $event->banner_image
                ], 'Banner uploaded successfully');
            }
        
            return $this->sendError('No banner image provided', [], 400);
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token has expired', [], 401);
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token is invalid', [], 401);
        } catch (JWTException $e) {
            return $this->sendError('Token is missing or not provided', [], 401);
        } catch (\Exception $e) {
            return $this->sendError('Failed to upload banner: ' . $e->getMessage(), [], 500);
        }
    }
    
}
