<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
    public function show($id){
        try {
            $user = User::with(['track'])->findOrFail($id);
            return $this->sendResponse(['user' => $user], 'User details retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('User not found', ['error' => 'User with ID ' . $id . ' not found'], 404);
        }
    }

    // Create a new user
    public function store(StoreUserRequest $request){
        $user = User::create($request->validated());
        return $this->sendResponse(['user' => $user], 'User created successfully', 201);
    }

    // Update an existing user
    public function update(Request $request, $id){
        try {
            $user = User::findOrFail($id);
            
            // Create and configure the UpdateUserRequest instance
            $updateRequest = new UpdateUserRequest();
            $updateRequest->setUserModel($user);
            
            // Validate the request using rules from UpdateUserRequest
            $rules = $updateRequest->rules();
            $messages = $updateRequest->messages();
            
            // Debug rules
            if ($request->has('debug')) {
                return $this->sendResponse([
                    'rules' => $rules,
                    'messages' => $messages,
                    'request_data' => $request->all()
                ], 'Debug information');
            }
            
            $validator = validator()->make($request->all(), $rules, $messages);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors()->toArray(), 422);
            }
            
            // Get validated data - this is the likely issue
            $validatedData = [];
            
            // Try alternative methods to get validated data
            try {
                // Method 1: Using validate method
                $validatedData = $validator->validate();
            } catch (\Exception $ve) {
                try {
                    // Method 2: Using validated method
                    $validatedData = $validator->validated();
                } catch (\Exception $ve2) {
                    // Method 3: Manually extract valid data
                    foreach ($rules as $field => $rule) {
                        if ($request->has($field)) {
                            $validatedData[$field] = $request->input($field);
                        }
                    }
                }
            }
            
            // Debugging - log validated data
            // \Log::info('Validated data for user update:', $validatedData);
            
            // Check if there's actually any data to update
            if (empty($validatedData)) {
                return $this->sendError('No data to update', ['error' => 'No valid data was provided for update'], 400);
            }
            
            // Update the user with validated data
            $user->fill($validatedData);
            $changes = $user->getDirty();
            
            if (empty($changes)) {
                return $this->sendResponse([
                    'user' => $user,
                    'message' => 'No changes detected in user data',
                    'validated_data' => $validatedData
                ], 'No changes to update');
            }
            
            $user->save();
            
            // Refresh the user model to get the updated data
            $user->refresh();
            
            return $this->sendResponse(['user' => $user], 'User updated successfully');
        } catch (\Exception $e) {
            // Log the exception for debugging
            // \Log::error('Error updating user: ' . $e->getMessage(), ['exception' => $e]);
            return $this->sendError('User update failed', ['error' => $e->getMessage()], 404);
        }
    }

    // Delete a user
    public function destroy($id){
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return $this->sendResponse([], 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('User deletion failed', ['error' => 'User with ID ' . $id . ' not found'], 404);
        }
    }
}
