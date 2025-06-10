<?php

namespace App\Http\Controllers;

use App\Models\Company\Company;
use App\Models\Event\Event;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user() ?? 2;
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $roles = Auth::user()->roles;

            return match ($roles) {
                'admin' => redirect('/api/dashboard/admin'),
                'student' => redirect('/api/dashboard/student'),
                'company' => redirect('/api/dashboard/company'),
                'staff' => redirect('/api/dashboard/staff'),
                default => response()->json(['message' => 'Role not recognized'], 403),
            };
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load dashboard', 'error' => $e->getMessage()], 500);
        }
    }

    public function companyDashboard()
    {
        try {
            $user = Auth::user() ?? 1;
            $company = Company::where('approved_by', Auth::user() ?? 1)->with(
                [
                    'jobFairParticipations.event',
                    'interviewRequests.student',
                    'interviewQueues',
                ]
            )->first();
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            return response()->json([
                'company_profile' => $company->only([
                    'name',
                    'description',
                    'logo_path',
                    'industry',
                    'website',
                    'size',
                    'location'
                ]),
                'job_fair_participation' => $company->jobFairParticipations,
                'interview_requests' => $company->interviewRequests->map(function ($request) {
                    return [
                        'student' => $request->student->only(['first_name', 'last_name', 'email']),
                        'status' => $request->status,
                        'preferred_slot' => $request->preferred_slot,
                    ];
                }),
                'interview_tracking' => $company->interviewQueues->map(function ($queue) {
                    return [
                        'student' => $queue->student->only(['first_name', 'last_name']),
                        'notes' => $queue->notes,
                        'rating' => $queue->rating,
                        'follow_up' => $queue->follow_up,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load company dashboard', 'error' => $e->getMessage()], 500);
        }
    }

    public function staffDashboard()
    {
        try {
            $user = Auth::user() ?? User::find(2);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $assignedEvents = $user->staffAssignments()->with('event')->get();
            $recentCreatedEvents = $user->createdEvents()->latest()->take(5)->get();
            $feedbackCount = $user->feedbackResponses()->count();
            $notifications = $user->notifications()->latest()->take(5)->get();

            return response()->json([
                'message' => 'Staff dashboard data',
                'data' => [
                    'full_name' => $user->full_name,
                    'assigned_events' => $assignedEvents,
                    'recent_created_events' => $recentCreatedEvents,
                    'feedback_count' => $feedbackCount,
                    'recent_notifications' => $notifications,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load staff dashboard', 'error' => $e->getMessage()], 500);
        }
    }
    public function studentDashboard()
    {
        try {
            $user = Auth::user() ?? User::find(100);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $eventRegistrations = $user->eventRegistrations()->with('event')->latest()->take(5)->get();
            $interviewRequests = $user->interviewRequests()->latest()->take(5)->get();
            $feedbackCount = $user->feedbackResponses()->count();
            $notifications = $user->notifications()->latest()->take(5)->get();

            return response()->json([
                'message' => 'Student dashboard data',
                'data' => [
                    'student_data' => [
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'linkedin_url' => $user->linkedin_url,
                        'github_url' => $user->github_url,
                        'graduation_year' => $user->graduation_year,
                        'profile_image' => $user->profile_image
                    ],

                    'recent_event_registrations' => $eventRegistrations,
                    'recent_interview_requests' => $interviewRequests,
                    'feedback_count' => $feedbackCount,
                    'recent_notifications' => $notifications,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load Student dashboard', 'error' => $e->getMessage()], 500);
        }
    }

    public function adminOverview()
    {
        try {
            $usersCount = User::count();
            $companies = Company::where('is_approved', true)->get();
            $companiesCount = Company::count();
            $approvedCompanies = Company::where('is_approved', true)->count();
            $eventsCount = Event::count();
            $activeEvents = Event::where('start_time', '<=', now())
                ->where('end_time', '>=', now())->count();

            return response()->json([
                'message' => 'Admin dashboard data',
                'stats' => [
                    'total_users' => $usersCount,
                    'approved_companies_data' => $companies,
                    'total_companies' => $companiesCount,
                    'approved_companies' => $approvedCompanies,
                    'total_events' => $eventsCount,
                    'active_events' => $activeEvents,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load admin dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function adminEvents()
    {
        try {
            $events = Event::latest()->take(10)->get();
            return response()->json(['message' => 'latest events', 'data' => $events], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load events', 'error' => $e->getMessage()], 500);
        }
    }
    public function adminUsers()
    {
        try {
            $usersByRole = [];
            $users = Auth::user()::with('roles')->get();
            foreach ($users as $user) {
                foreach ($user->roles as $role) {
                    $usersByRole[$role->name] = ($usersByRole[$role->name] ?? 0) + 1;
                }
            }
            return response()->json(['message' => 'return all users', 'data' => $usersByRole], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load user stats', 'error' => $e->getMessage()], 500);
        }
    }
    public function adminCompanies()
    {
        try {
            $pendingCompanies = Company::where('is_approved', false)->get();
            if (!$pendingCompanies) {
                return response()->json(['message' => 'No pending companies'], 404);
            }
            return response()->json(['message' => 'all pending companies', 'data' => $pendingCompanies], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load pending companies', 'error' => $e->getMessage()], 500);
        }
    }
    public function adminLiveEvents()
    {
        try {
            $now = now();
            $liveEvnets = Event::where('start_time', '<=', $now)->where('end_time', '>=', $now)->get();
            if (!$liveEvnets) {
                return response()->json(['message' => 'there is no live events'], 404);
            }
            return response()->json(['message' => 'live events', 'data' => $liveEvnets], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load live events', 'error' => $e->getMessage()], 500);
        }
    }

}
