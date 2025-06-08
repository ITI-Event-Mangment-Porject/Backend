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
            $user = Auth::user()??2;
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
    public function adminDashboard()
    {
        try {
            return response()->json(['message' => 'Admin dashboard data']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load admin dashboard', 'error' => $e->getMessage()], 500);
        }
    }

    public function companyDashboard()
    {
        try {
            return response()->json(['message' => 'Company dashboard data']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load company dashboard', 'error' => $e->getMessage()], 500);
        }
    }

    public function staffDashboard()
    {
        try {
            return response()->json(['message' => 'Staff dashboard data']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load staff dashboard', 'error' => $e->getMessage()], 500);
        }
    }
    public function studentDashboard()
    {
        try {
            return response()->json(['message' => 'Student dashboard data']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load Student dashboard', 'error' => $e->getMessage()], 500);
        }
    }

    public function adminOverview()
    {
        try {
            $usersCount = User::count();
            $companiesCount = Company::count();
            $approvedCompanies = Company::where('is_approved', true)->count();
            $eventsCount = Event::count();

            return response()->json([
                'users' => $usersCount,
                'companies' => $companiesCount,
                'approved_companies' => $approvedCompanies,
                'events' => $eventsCount,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to load overview stats', 'error' => $e->getMessage()], 500);
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
