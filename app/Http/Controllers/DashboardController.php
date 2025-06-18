<?php

namespace App\Http\Controllers;
use App\Http\Controllers\API\BaseApiController;
use App\Models\Company\Company;
use App\Models\Event\Event;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;


class DashboardController extends BaseApiController
{
    public function index()
    {
        try {
            $user = Auth::user() ?? 2;
            if (!$user) {

                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $roles = Auth::user()->roles;

            // $roles='company'; test roles
            return match ($roles) {
                'student' => redirect('/api/dashboard/student'),
                'company' => redirect('/api/dashboard/company'),
                'staff' => redirect('/api/dashboard/staff'),
                default => $this->sendResponse('Role not Recognized', 'Role not recognized', 403),
            };
        } catch (\Exception $e) {
            return $this->sendError('Failed to load dashboard', ['error' => $e->getMessage()], 500);
        }
    }

    public function companyDashboard()
    {
        try {
            $user = Auth::user() ?? 1;
            $company = Company::where('approved_by', Auth::user() ?? 1)->with(
                [
                    'jobFairParticipations.event',
                    'interviewRequests.user',
                    'interviewQueues',
                ]
            )->first();
            if (!$company) {
                return $this->sendError('Company Not Found', ['error' => 'Comapany not Found'], 404);

            }
            return $this->sendResponse([
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
                        'student' => $request->user
                            ? $request->user->only(['first_name', 'last_name', 'email', 'phone'])
                            : null,
                        'status' => $request->status,
                        'preferred_slot' => $request->preferred_slot,
                    ];
                }),
                'interview_tracking' => $company->interviewQueues->map(function ($queue) {
                    return [
                        'student' => $queue->student
                            ? $queue->student->only(['first_name', 'last_name'])
                            : null,
                        'notes' => $queue->notes,
                        'rating' => $queue->rating,
                        'follow_up' => $queue->follow_up,
                    ];
                }),
            ], 'Company Dashboard', 200);

        } catch (\Exception $e) {
            return $this->sendError('Failed to load Company Dashboard', ['error' => $e->getMessage()], 500);
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

            return $this->sendResponse(
                [
                    'full_name' => $user->full_name,
                    'assigned_events' => $assignedEvents,
                    'recent_created_events' => $recentCreatedEvents,
                    'feedback_count' => $feedbackCount,
                    'recent_notifications' => $notifications,
                ]
                ,
                'Staff Dashboard',
                200
            );

        } catch (\Exception $e) {
            $this->sendError('Failed to load staff dashboard', ['error' => $e->getMessage()], 500);
        }
    }
    public function studentDashboard()
    {
        try {
            $user = Auth::user() ?? User::find(100);
            if (!$user) {
                return $this->sendError('User Not Found', ['error' => "Can't find User"], 404);

            }

            $eventRegistrations = $user->eventRegistrations()->with('event')->latest()->take(5)->get();
            $interviewRequests = $user->interviewRequests()->latest()->take(5)->get();
            $feedbackCount = $user->feedbackResponses()->count();
            $notifications = $user->notifications()->latest()->take(5)->get();

            return $this->sendResponse([
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
            ], 'Student Dashboard Data', 200);

        } catch (\Exception $e) {
            return $this->sendError('Failed to load Student dashboard', ['error' => $e->getMessage()], 500);

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

            return $this->sendResponse([
                'total_users' => $usersCount,
                'approved_companies_data' => $companies,
                'total_companies' => $companiesCount,
                'approved_companies' => $approvedCompanies,
                'total_events' => $eventsCount,
                'active_events' => $activeEvents,
            ], 'Overview for Admin', 200);

        } catch (\Exception $e) {
            return $this->sendError('Failed to load Admin Dashboard', ['error' => $e->getMessage()], 200);

        }
    }

    public function adminEvents()
    {
        try {
            $events = Event::latest()->take(10)->get();
            return $this->sendResponse($events, 'Latest events', 200);

        } catch (\Exception $e) {
            return $this->sendError('Failed to load events', ['error' => $e->getMessage()], 500);
        }
    }
    public function adminUsers()
    {
        try {
            $usersByRole = Role::withCount('users')->pluck('users_count', 'name');

            return $this->sendResponse($usersByRole, 'All Users Roles', 200);
        } catch (\Exception $e) {
            return $this->sendError('Failed to load User status', ['error' => $e->getMessage()], 500);
        }
    }

    // admin can see pending companies
    public function adminCompanies()
    {
        try {
            $pendingCompanies = Company::where('is_approved', false)->get();
            if (!$pendingCompanies) {

                return $this->sendError('No Pending companies', ['error' => 'No pending companies'], 404);
            }
            return $this->sendResponse($pendingCompanies, 'All Pending Companies', 200);
        } catch (\Exception $e) {
            return $this->sendError('Failed to load pending Companies', ['error' => $e->getMessage()], 500);
        }
    }
    public function adminLiveEvents()
    {
        try {
            $now = now();
            $liveEvents = Event::where('start_time', '<=', $now)->where('end_time', '>=', $now)->get();
            if (!$liveEvents) {
                return $this->sendError('There is No live Events', ['error' => 'There is No live events'], 404);

            }
            return $this->sendResponse($liveEvents, 'live Events', 200);

        } catch (\Exception $e) {
            return $this->sendError('Failed to load live Events', ['error' => $e->getMessage()], 500);
        }
    }

}
