<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use App\Models\Auth\User;
use App\Models\Company\Company;
use App\Models\Event\Event;
use App\Http\Requests\Events\StoreJobFairRequest;
use App\Http\Requests\Events\UpdateJobFairRequest;
use App\Models\JobFair\JobFairParticipation;
use App\Notifications\NewJobFairCreated;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class JobFairController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Event::query()->where('type', 'Job Fair');

        // Only admin/staff can see all, others see only published
        if (!($user->hasRole('admin') || $user->hasRole('staff'))) {
            $query->where('status', 'published');
        } else {
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }
        }

        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->input('end_date'));
        }
        $jobFairs = $query->select('id','title','start_date','end_date','status','location')->get();
        return $this->sendResponse($jobFairs, 'Job fairs retrieved successfully.');
    }

    public function show($jobfairid)
    {
        $user = auth()->user();
        $event = Event::with('visibilityTracks.track:id,name')
            ->where('id', $jobfairid)
            ->where('type', 'Job Fair')
            ->first();

        if (!$event) {
            return $this->sendError('Job Fair not found.');
        }

        // Only admin/staff can see non-published job fairs
        if ($event->status !== 'published' && !($user->hasRole('admin') || $user->hasRole('staff'))) {
            return $this->sendError('You are not authorized to view this job fair.', [], 403);
        }

        $response = $event->toArray();
        $response['tracks'] = $event->visibilityTracks->pluck('track.name');

        return $this->sendResponse($response, 'Job Fair retrieved successfully.');
    }

    public function store(StoreJobFairRequest $request)
    {
        try {
            $validated = $request->validated();

            $event = Event::create([
                ...$validated,
                'slug' => \Str::slug($validated['title']) . '-' . \Str::random(5),
                'type' => 'Job Fair',
                'status' => 'draft',
                'created_by' => $validated['created_by'] ?? auth()->id()
            ]);

            // Notify all companies
            $companies = Company::all();
            foreach ($companies as $company) {
                if ($company->contact_email) {
                    $company->notify(new NewJobFairCreated($event));
                }
            }

            // Notify all users with company_representative role
            $companyReps = User::whereHas('roles', function ($q) {
                $q->where('name', 'company_representative');
            })->get();

            foreach ($companyReps as $user) {
                $user->notify(new NewJobFairCreated($event));
            }

            return $this->sendResponse($event, 'Job Fair created in draft status.', 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to create Job Fair.', [$e->getMessage()], 500);
        }
    }

    public function update(UpdateJobFairRequest $request, $jobfairid)
    {
        $event = Event::where('type', 'Job Fair')->find($jobfairid);

        if (!$event) {
            return $this->sendError('Job Fair not found.');
        }

        try {
            $validated = $request->validated();

            if (isset($validated['title'])) {
                $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
            }

            $event->update($validated);

            return $this->sendResponse($event, 'Job Fair updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to update Job Fair.', [$e->getMessage()], 500);
        }
    }

    public function destroy($jobfairid)
    {
        $event = Event::where('type', 'Job Fair')->find($jobfairid);

        if (!$event) {
            return $this->sendError('Job Fair not found.');
        }

        try {
            $event->update([
                'archived_at' => now(),
                'status' => 'archived',
            ]);

            return $this->sendResponse(null, 'Job Fair archived successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to archive Job Fair.', [$e->getMessage()], 500);
        }
    }

    public function Companies($jobfairid)
    {
        $event = Event::with(['jobFairParticipations.company:id,name'])
            ->where('id', $jobfairid)
            ->where('type', 'Job Fair')
            ->first();

        if (!$event) {
            return $this->sendError('Job Fair not found.');
        }

        $companies = $event->jobFairParticipations->map(function ($p) {
            return [
                'companyId' => $p->company->id,
                'companyName' => $p->company->name,
                'status' => $p->status,
            ];
        });

        return $this->sendResponse($companies, 'Companies retrieved successfully.');
    }

    public function statistics($jobfairid)
    {
        $event = Event::with([
            'jobFairParticipations.company',
            'jobFairParticipations.jobProfiles',
            'interviewRequests'
        ])
        ->where('id', $jobfairid)
        ->where('type', 'Job Fair')
        ->first();

        if (!$event) {
            return $this->sendError('Job Fair not found.');
        }

        // All companies
        $companies = $event->jobFairParticipations
            ->pluck('company.name')
            ->unique()
            ->values()
            ->toArray();

        // Approved companies
        $approvedCompanies = $event->jobFairParticipations
            ->where('status', 'approved')
            ->pluck('company.name')
            ->unique()
            ->values()
            ->toArray();

        // Total participations
        $totalParticipations = $event->jobFairParticipations->count();

        // Total job profiles
        $totalJobProfiles = $event->jobFairParticipations
            ->flatMap(function ($participation) {
                return $participation->jobProfiles;
            })
            ->count();

        // Total interviews
        $totalInterviews = $event->interviewRequests->count();

        // Approved interview requests
        $approvedInterviews = $event->interviewRequests
            ->where('status', 'approved')
            ->count();

        $stats = [
            'total_participations' => $totalParticipations,
            'total_job_profiles' => $totalJobProfiles,
            'total_interviews_requests' => $totalInterviews,
            'approved_interviews' => $approvedInterviews,
            'participating_companies' => $companies,
            'approved_companies' => $approvedCompanies,
        ];

        return $this->sendResponse($stats, 'Statistics retrieved successfully.');
    }
}
