<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\Controller;
use App\Models\Event\Event;
use DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class JobFairController extends Controller
{
    public function index(Request $request)
    {
        // Logic to retrieve and return a list of job fairs
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
        return response()->json($jobFairs);
    }

    public function show($id)
    {
        $event = Event::with('visibilityTracks.track:id,name')
            ->where('id', $id)
            ->where('type', 'Job Fair')
            ->firstOrFail();

        $response = $event->toArray();
        $response['tracks'] = $event->visibilityTracks->pluck('track.name');

        return response()->json($response);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'banner_image' => 'nullable|string|max:500',
            'registration_deadline' => 'nullable|date',
            'visibility_type' => 'required|in:all,role_based,track_based',
            'visibility_config' => 'nullable|array',
            'slido_qr_code' => 'nullable|string|max:500',
            'slido_embed_url' => 'nullable|string|max:500',
        ]);

        $event = Event::create([
            ...$validated,
            'slug' => Str::slug($validated['title']) . '-' . Str::random(5),
            'type' => 'Job Fair',
            'status' => 'draft', 
            'created_by' => 1 // Assuming the creator ID is 1 for now, you can replace it with the authenticated user's ID later
        ]);


        return response()->json([
            'message' => 'Job Fair created in draft status.',
            'data' => $event,
        ], 201);
    }



    public function update(Request $request, $id)
    {
        $event = Event::where('type', 'Job Fair')->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'banner_image' => 'nullable|string|max:500',
            'registration_deadline' => 'nullable|date',
            'visibility_type' => 'sometimes|in:all,role_based,track_based',
            'visibility_config' => 'nullable|array',
            'slido_qr_code' => 'nullable|string|max:500',
            'slido_embed_url' => 'nullable|string|max:500',
            'status' => 'sometimes|in:draft,published,ongoing,completed,archived',
        ]);

        // If title updated, regenerate slug
        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);
        }

        $event->update($validated);

        return response()->json([
            'message' => 'Job Fair updated successfully.',
            'data' => $event,
        ]);
    }


    public function destroy($id)
    {
        $event = Event::where('type', 'Job Fair')->findOrFail($id);

        $event->update([
            'archived_at' => now(),
            'status' => 'archived',
        ]);

        return response()->json([
            'message' => 'Job Fair archived successfully.',
        ]);
    }


    public function Companies($id)
    {
        // Logic to retrieve companies associated with a specific job fair
        $event = Event::with(['jobFairParticipations.company:id,name'])
            ->where('id', $id)
            ->where('type', 'Job Fair')
            ->firstOrFail();

        $companies = $event->jobFairParticipations->map(function ($p) {
            return [
                'companyId' => $p->company->id,
                'companyName' => $p->company->name,
                'status' => $p->status,
            ];
        });

        return response()->json($companies);
    }

public function statistics($id)
{
    $event = Event::where('id', $id)->where('type', 'Job Fair')->firstOrFail();
    // Get participating companies with their names
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

    return response()->json($stats);
}
}
