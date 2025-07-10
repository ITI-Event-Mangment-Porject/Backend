<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseApiController
{
    // List all users with pagination and filters
    public function index(Request $request)
    {
        $query = User::with(['track']);

        // Search filter (name, email)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Track filter
        if ($request->has('track_id') && !empty($request->track_id)) {
            $query->where('track_id', $request->track_id);
        }

        // Active status filter
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (bool) $request->is_active);
        }

        // Intake year filter
        if ($request->has('intake_year') && !empty($request->intake_year)) {
            $query->where('intake_year', $request->intake_year);
        }

        // Graduation year filter
        if ($request->has('graduation_year') && !empty($request->graduation_year)) {
            $query->where('graduation_year', $request->graduation_year);
        }

        // Date range filters
        if ($request->has('created_from') && !empty($request->created_from)) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->has('created_to') && !empty($request->created_to)) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort parameters
        $allowedSortFields = ['id', 'first_name', 'last_name', 'email', 'created_at', 'updated_at', 'intake_year', 'graduation_year'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $perPage = min(max((int) $perPage, 1), 100); // Limit between 1 and 100

        $users = $query->paginate($perPage);

        $data = [
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'has_more_pages' => $users->hasMorePages(),
            ],
            'filters' => [
                'search' => $request->get('search'),
                'track_id' => $request->get('track_id'),
                'is_active' => $request->get('is_active'),
                'intake_year' => $request->get('intake_year'),
                'graduation_year' => $request->get('graduation_year'),
                'role' => $request->get('role'),
                'created_from' => $request->get('created_from'),
                'created_to' => $request->get('created_to'),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
            ]
        ];

        return $this->sendResponse($data, 'Users retrieved successfully');
    }

    // Show a specific user
    public function show($id)
    {
        try {
            $user = User::with(['track'])->findOrFail($id);
            return $this->sendResponse(['user' => $user], 'User details retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('User not found', ['error' => 'User with ID ' . $id . ' not found'], 404);
        }
    }

    // Create a new user
    public function store(Request $request)
    {
        try {
            // Create a StoreUserRequest instance for validation rules
            $storeRequest = new StoreUserRequest();
            
            // Validate directly with the validator
            $validator = validator()->make($request->all(), $storeRequest->rules(), $storeRequest->messages());

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
            }

            // Get validated data
            $validatedData = $validator->validated();
            
            // Handle file uploads
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile-images', 'public');
                $validatedData['profile_image'] = '/storage/' . $path;
            }

            if ($request->hasFile('cv_path')) {
                $cvPath = $request->file('cv_path')->store('cvs', 'public');
                $validatedData['cv_path'] = '/storage/' . $cvPath;
            }
            
            // Create user with all validated data including file paths
            $user = User::create($validatedData);
            
            return $this->sendResponse(['user' => $user], 'User created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage(), ['exception' => $e]);
            return $this->sendError('User creation failed', ['error' => $e->getMessage()], 500);
        }
    }
public function update(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);

        // Debug: Log everything about the request
        Log::info('=== UPDATE REQUEST DEBUG ===');
        Log::info('Method: ' . $request->method());
        Log::info('Content-Type: ' . $request->header('Content-Type'));
        Log::info('Request All: ', $request->all());
        Log::info('Request Input: ', $request->input());
        Log::info('Request Files: ', $request->file());
        Log::info('Has profile_image: ' . ($request->hasFile('profile_image') ? 'true' : 'false'));
        Log::info('Has cv_path: ' . ($request->hasFile('cv_path') ? 'true' : 'false'));
        Log::info('$_POST: ', $_POST);
        Log::info('$_FILES: ', $_FILES);
        Log::info('=== END DEBUG ===');

        // For PATCH requests with multipart/form-data, we need to get all input data
        // including both regular fields and file fields
        $allInputData = array_merge($request->all(), $request->file());
        
        // Create and configure the UpdateUserRequest instance
        $updateRequest = new UpdateUserRequest();
        $updateRequest->setUserModel($user);

        // Validate using the merged data
        $validator = validator()->make($allInputData, $updateRequest->rules(), $updateRequest->messages());

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
        }

        // Start with empty validated data
        $validatedData = [];
        
        // Handle text fields - check both input() and has() methods
        $textFields = ['first_name', 'last_name', 'email', 'phone', 'bio', 'linkedin_url', 'github_url', 'portfolio_url', 'track_id', 'intake_year', 'graduation_year', 'is_active'];
        
        foreach ($textFields as $field) {
            // Check if field exists in request (handles both POST and PATCH)
            if ($request->has($field) || $request->filled($field)) {
                $value = $request->input($field);
                // Only add non-null values
                if ($value !== null) {
                    $validatedData[$field] = $value;
                }
            }
        }
        
        Log::info('Text fields added: ', $validatedData);
        
        // Handle file uploads - more robust file checking
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            if ($file && $file->isValid()) {
                Log::info('Processing profile_image upload');
                // Delete old profile image if exists
                if ($user->profile_image) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $user->profile_image));
                }
                $path = $file->store('profile-images', 'public');
                $validatedData['profile_image'] = '/storage/' . $path;
                Log::info('Profile image uploaded to: ' . $validatedData['profile_image']);
            }
        }

        if ($request->hasFile('cv_path')) {
            $file = $request->file('cv_path');
            if ($file && $file->isValid()) {
                Log::info('Processing cv_path upload');
                // Delete old CV if exists
                if ($user->cv_path) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $user->cv_path));
                }
                $cvPath = $file->store('cvs', 'public');
                $validatedData['cv_path'] = '/storage/' . $cvPath;
                Log::info('CV uploaded to: ' . $validatedData['cv_path']);
            }
        }
        
        Log::info('Final validated data: ', $validatedData);

        // Check if there's actually any data to update
        if (empty($validatedData)) {
            Log::warning('No validated data found');
            Log::info('Available request data: ', [
                'all' => $request->all(),
                'input' => $request->input(),
                'files' => $request->file(),
                'post' => $_POST,
                'get' => $_GET
            ]);
            return $this->sendError('No data to update', ['error' => 'No valid data was provided for update'], 400);
        }

        // Update the user with validated data
        $user->update($validatedData);

        // Refresh the user model to get the updated data
        $user->refresh();

        return $this->sendResponse(['user' => $user], 'User updated successfully');
    } catch (\Exception $e) {
        // Log the exception for debugging
        Log::error('Error updating user: ' . $e->getMessage(), ['exception' => $e]);
        return $this->sendError('User update failed', ['error' => $e->getMessage()], 500);
    }
}
    // Delete a user
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Delete associated files when deleting user
            if ($user->profile_image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->profile_image));
            }
            if ($user->cv_path) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->cv_path));
            }
            
            $user->delete();
            return $this->sendResponse([], 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('User deletion failed', ['error' => 'User with ID ' . $id . ' not found'], 404);
        }
    }

    /**
     * Upload profile image for a user
     * POST /users/{id}/profile-image
     */
    public function uploadProfileImage(Request $request, $id)
    {
        try {
            $request->validate([
                'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $user = User::findOrFail($id);

            if ($request->hasFile('profile_image')) {
                // Delete old profile image if exists
                if ($user->profile_image) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $user->profile_image));
                }

                // Store the new profile image
                $path = $request->file('profile_image')->store('profile-images', 'public');

                // Update the user with the new profile image path
                $user->profile_image = '/storage/' . $path;
                $user->save();

                return $this->sendResponse([
                    'profile_image' => $user->profile_image
                ], 'Profile image uploaded successfully');
            }

            return $this->sendError('No profile image provided', [], 400);
        } catch (\Exception $e) {
            Log::error('Error uploading profile image: ' . $e->getMessage(), ['exception' => $e]);
            return $this->sendError('Failed to upload profile image: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Upload CV for a user
     * POST /users/{id}/cv
     */
    public function uploadCV(Request $request, $id)
    {
        try {
            $request->validate([
                'cv_path' => 'required|file|mimes:pdf,doc,docx|max:5120'
            ]);

            $user = User::findOrFail($id);

            if ($request->hasFile('cv_path')) {
                // Delete old CV if exists
                if ($user->cv_path) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $user->cv_path));
                }

                // Store the new CV
                $path = $request->file('cv_path')->store('cvs', 'public');

                // Update the user with the new CV path
                $user->cv_path = '/storage/' . $path;
                $user->save();

                return $this->sendResponse([
                    'cv_path' => $user->cv_path
                ], 'CV uploaded successfully');
            }

            return $this->sendError('No CV file provided', [], 400);
        } catch (\Exception $e) {
            Log::error('Error uploading CV: ' . $e->getMessage(), ['exception' => $e]);
            return $this->sendError('Failed to upload CV: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get profile image for a user
     * GET /users/{id}/profile-image
     */
    public function getProfileImage($id)
    {
        try {
            $user = User::findOrFail($id);
            
            if (!$user->profile_image) {
                return $this->sendError('No profile image found', [], 404);
            }

            return $this->sendResponse([
                'profile_image' => $user->profile_image
            ], 'Profile image retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('User not found', ['error' => 'User with ID ' . $id . ' not found'], 404);
        }
    }

    /**
     * Get CV for a user
     * GET /users/{id}/cv
     */
    public function getCV($id)
    {
        try {
            $user = User::findOrFail($id);
            
            if (!$user->cv_path) {
                return $this->sendError('No CV found', [], 404);
            }

            return $this->sendResponse([
                'cv_path' => $user->cv_path
            ], 'CV retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('User not found', ['error' => 'User with ID ' . $id . ' not found'], 404);
        }
    }
}
