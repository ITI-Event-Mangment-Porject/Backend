<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Event\Event;
use App\Models\JobFair\JobFairParticipation;
use DB;
use Illuminate\Http\Request;

class JobFairParticipationController extends Controller
{
    //Company submits participation form for a Job Fair.
    public function store(Request $request, $eventId)
    {
        $request->validate([
            'company.name' => 'required|string|max:255',
            'company.logo_path' => 'nullable|string',
            'company.description' => 'nullable|string',
            'company.website' => 'nullable|string',
            'company.industry' => 'nullable|string',
            'company.size' => 'nullable|string',
            'company.location' => 'nullable|string',
            'company.contact_email' => 'required|email',
            'company.contact_phone' => 'nullable|string',
            'company.linkedin_url' => 'nullable|string',
            'special_requirements' => 'nullable|string',
            'need_branding' => 'nullable|boolean',
        ]);

        $companyData = $request->input('company');

        return DB::transaction(function () use ($eventId, $companyData, $request) {
            // Create or get company
            $company = Company::firstOrCreate(
                ['contact_email' => $companyData['contact_email']],
                $companyData
            );

            // Prevent duplicate participation
            $existing = JobFairParticipation::where('event_id', $eventId)
                ->where('company_id', $company->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'This company has already submitted participation for this job fair.',
                    'existing_participation_id' => $existing->id
                ], 409);
            }

            // Create participation
            $participation = JobFairParticipation::create([
                'event_id' => $eventId,
                'company_id' => $company->id,
                'status' => 'pending',
                'special_requirements' => $request->input('special_requirements'),
                'submitted_by' => 3, // Replace later with authenticated user
                'submitted_at' => now(),
                'need_branding' => $request->input('need_branding', false),
            ]);

            return response()->json([
                'message' => 'Participation submitted successfully.',
                'participation' => $participation,
            ], 201);
        });
    }


    //Admin approves or rejects participation.
    public function review(Request $request, $participationId)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string',
        ]);

        $participation = JobFairParticipation::findOrFail($participationId);
        $participation->update([
            'status' => $request->status,
            'review_notes' => $request->review_notes,
            'reviewed_by' => 5, //remember to replace with actual user ID after authentication is implemented
            'reviewed_at' => now(),
        ]);

        // If approved, mark company as approved
        if ($request->status === 'approved') {
            $participation->company->update([
                'is_approved' => true,
                'approved_by' => 3, //remember to replace with actual user ID after authentication is implemented
                'approved_at' => now(),
            ]);

            // Auto-publish event if all selected logic applies
            $event = $participation->event;
            if ($event->type === 'Job Fair' && $event->status === 'draft') {
                $event->update(['status' => 'published']);
            }
        }

        return response()->json([
            'message' => "Participation {$request->status} successfully.",
            'participation' => $participation->fresh(),
        ]);
    }

    //List all participations for a Job Fair (admin view).
    public function index(Request $request, $jobFairId)
    {
        $event = Event::where('type', 'Job Fair')->findOrFail($jobFairId);

        $query = JobFairParticipation::with(['company', 'submittedBy:id,first_name,last_name,email', 'reviewedBy:id,first_name,last_name,email'])
        ->where('event_id', $event->id);

        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
    }

        $participations = $query->get();
            return response()->json($participations);
    }

    //View one participation (admin or company).
    public function show($participationId)
    {
        $participation = JobFairParticipation::with('company', 'submittedBy', 'reviewedBy')
            ->findOrFail($participationId);

        return response()->json($participation);
    }
}
