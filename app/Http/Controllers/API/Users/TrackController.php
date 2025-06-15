<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\StoreTrackRequest;
use App\Http\Requests\UpdateTrackRequest;
use App\Models\Auth\Track;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TrackController extends BaseApiController
{
    // List all tracks with pagination and filters
    public function index(Request $request)
    {
        $query = Track::query();

        // Search filter (name, description)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        // Active status filter
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (bool) $request->is_active);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        
        // Validate sort parameters
        $allowedSortFields = ['id', 'name', 'slug', 'sort_order', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $perPage = min(max((int) $perPage, 1), 100); // Limit between 1 and 100

        $tracks = $query->paginate($perPage);

        $data = [
            'tracks' => $tracks->items(),
            'pagination' => [
                'current_page' => $tracks->currentPage(),
                'last_page' => $tracks->lastPage(),
                'per_page' => $tracks->perPage(),
                'total' => $tracks->total(),
                'from' => $tracks->firstItem(),
                'to' => $tracks->lastItem(),
                'has_more_pages' => $tracks->hasMorePages(),
            ],
            'filters' => [
                'search' => $request->get('search'),
                'is_active' => $request->get('is_active'),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
            ]
        ];
        
        return $this->sendResponse($data, 'Tracks retrieved successfully');
    }

    // Show a specific track
    public function show($id)
    {
        try {
            // Try to find by ID first
            $track = Track::with(['users'])->find($id);
            
            // If not found by ID, try to find by slug
            if (!$track && !is_numeric($id)) {
                $track = Track::with(['users'])->where('slug', $id)->first();
            }
            
            if (!$track) {
                throw new \Exception('Track not found');
            }
            
            return $this->sendResponse(['track' => $track], 'Track details retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Track not found', ['error' => 'Track with ID or slug ' . $id . ' not found'], 404);
        }
    }    // Create a new track
    public function store(StoreTrackRequest $request)
    {
        // Get validated data
        $validatedData = $request->validated();
        
        // If slug is not provided, generate it from the name
        if (empty($validatedData['slug']) && !empty($validatedData['name'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }
        
        // Create the track with validated data
        $track = Track::create($validatedData);
        
        return $this->sendResponse(['track' => $track], 'Track created successfully', 201);
    }

    // Update an existing track
    public function update(Request $request, $id)
    {
        try {
            // Try to find by ID first
            $track = Track::find($id);
            
            // If not found by ID, try to find by slug
            if (!$track && !is_numeric($id)) {
                $track = Track::where('slug', $id)->first();
            }
            
            if (!$track) {
                throw new \Exception('Track not found');
            }
            
            // Create and configure the UpdateTrackRequest instance
            $updateRequest = new UpdateTrackRequest();
            $updateRequest->setTrackModel($track);
            
            // Validate the request using rules from UpdateTrackRequest
            $validator = validator()->make($request->all(), $updateRequest->rules(), $updateRequest->messages());
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
            }
            
            // Get validated data
            $validatedData = $validator->validated();
            
            // Check if there's actually any data to update
            if (empty($validatedData)) {
                return $this->sendError('No data to update', ['error' => 'No valid data was provided for update'], 400);
            }
            
            // Update the track with validated data
            $track->fill($validatedData);
            $changes = $track->getDirty();
            
            if (empty($changes)) {
                return $this->sendResponse([
                    'track' => $track,
                    'message' => 'No changes detected in track data',
                ], 'No changes to update');
            }
            
            $track->save();
            
            // Refresh the track model to get the updated data
            $track->refresh();
            
            return $this->sendResponse(['track' => $track], 'Track updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Track update failed', ['error' => $e->getMessage()], 404);
        }
    }

    // Delete a track
    public function destroy($id)
    {
        try {
            // Try to find by ID first
            $track = Track::find($id);
            
            // If not found by ID, try to find by slug
            if (!$track && !is_numeric($id)) {
                $track = Track::where('slug', $id)->first();
            }
            
            if (!$track) {
                throw new \Exception('Track not found');
            }
            
            // Check if there are users associated with this track
            $usersCount = $track->users()->count();
            if ($usersCount > 0) {
                return $this->sendError(
                    'Track deletion failed', 
                    ['error' => 'Cannot delete this track because it has ' . $usersCount . ' users associated with it. Reassign users to another track first.'], 
                    400
                );
            }
            
            $track->delete();
            return $this->sendResponse([], 'Track deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Track deletion failed', ['error' => $e->getMessage()], 404);
        }
    }
}
