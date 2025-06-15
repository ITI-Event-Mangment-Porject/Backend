<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use App\Models\Event\Event;
use App\Http\Requests\Events\StoreJobFairRequest;
use App\Http\Requests\Events\UpdateJobFairRequest;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class JobFairController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Event::query()->where('type', 'Job Fair');
        if ($request->has('status')){
            $query->where('status', $request->input('status'));
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

    public function show($jobFairId)
    {
        $event = Event::with('visibilityTracks.track:id,name')
            ->where('id', $jobFairId)
            ->where('type', 'Job Fair')
            ->first();

        if (!$event) {
            return $this->sendError('Job Fair not found.');
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
                'slug' => Str::slug($validated['title']) . '-' . Str::random(5),
                'type' => 'Job Fair',
                'status' => 'draft',
                'created_by' => $validated['created_by'] ?? auth()->id() ?? 1
            ]);

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
        $event = Event::where('id', $jobfairid)->where('type', 'Job Fair')->first();

        if (!$event) {
            return $this->sendError('Job Fair not found.');
        }

        $companies = $event->jobFairParticipations()
            ->with('company:id,name')
            ->get()
            ->pluck('company.name')
            ->unique()
            ->values()
            ->toArray();

        $stats = [
            'total_participations' => $event->jobFairParticipations()->count(),
            'total_job_profiles' => $event->jobFairParticipations()->withCount('jobProfiles')->get()->sum('job_profiles_count'),
            'total_interviews' => $event->interviewRequests()->count(),
            'participating_companies' => $companies,
        ];

        return $this->sendResponse($stats, 'Statistics retrieved successfully.');
    }
}
