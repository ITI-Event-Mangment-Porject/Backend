<?php

namespace App\Http\Controllers\API\Users;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
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

        return response()->json([
            'data' => $users->items(),
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
        ]);
    }

    // Show a specific user
    public function show($id){
        $user = User::with(['track'])
            ->findOrFail($id);
        return response()->json($user);
    }

    // Create a new user
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'portal_user_id' => 'nullable|integer',
            'email' => 'required|email|unique:users',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'track_id' => 'nullable|exists:tracks,id',
            'intake_year' => 'nullable|integer',
            'graduation_year' => 'nullable|integer',
            'is_active' => 'boolean',
            'bio' => 'nullable|string',
            'linkedin_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'portfolio_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create($validator->validated());
        return response()->json($user, 201);
    }

    // Update an existing user
    public function update(Request $request, $id){
        $user = User::findOrFail($id);
         $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string',
            'track_id' => 'nullable|exists:tracks,id',
            'intake_year' => 'nullable|integer',
            'graduation_year' => 'nullable|integer',
            'is_active' => 'boolean',
            'bio' => 'nullable|string',
            'linkedin_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'portfolio_url' => 'nullable|url',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->update($validator->validated());
        return response()->json($user);
    }

    // Delete a user
    public function destroy($id){
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
