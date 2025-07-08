<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

use Auth;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use Illuminate\Validation\ValidationException;
use Storage;
use App\Http\Controllers\API\BaseApiController;
class CompanyController extends BaseApiController
{
    //create new company
    public function store(StoreCompanyRequest $request)
    {
        try {
            $data = $request->validated();
            $data['is_approved'] = false;
            $data['status'] = 'pending';
            $company = Company::create($data);
            return $this->sendResponse($company, 'Company created successfully', 201);
        } catch (Exception $e) {
            return $this->sendError('Failed to create company', ['error' => $e->getMessage()], 500);
        }
    }

    // get all companies
    public function index(Request $request)
    {
        try {
            $query = QueryBuilder::for(Company::class)
                ->withCount([
                    'jobFairParticipations',
                    'interviewRequests',
                    'interviewQueues as filled_interviews_count',
                ])
                ->allowedFilters([
                    AllowedFilter::exact('is_approved'),
                    AllowedFilter::exact('industry'),
                    AllowedFilter::exact('size'),
                    AllowedFilter::partial('name'),
                    AllowedFilter::partial('location'),
                ])
                ->defaultSort('-created_at');
            $companies = $query->paginate($request->get('per_page', 15));
            $companies->getCollection()->transform(function ($company) {
                $company->available_interviews = $company->interview_requests_count - $company->filled_interviews_count;
                return $company;
            });

            $totalCount = Company::count();
            $approvedCount = Company::where('is_approved', true)->count();

            return $this->sendResponse([
                'companies' => $companies,
                'total_count' => $totalCount,
                'approved_count' => $approvedCount
            ], 'Get all Companies', 200);

        } catch (Exception $e) {
            return $this->sendError('Failed to retrieve companies', ['error' => $e->getMessage()], 500);
        }
    }
    //get company by id
    public function show($id)
    {
        try {
            $company = Company::findOrFail($id);
            return $this->sendResponse($company, 'company retrieved successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('company not found', ['error' => $e->getMessage()], 404);

        } catch (Exception $e) {
            return $this->sendError('Failed to retrieve company', ['error' => $e->getMessage()]);
        }
    }
    //update company
    public function update(StoreCompanyRequest $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            $data = $request->except(['email']);

            $company->update($data);
            return $this->sendResponse($company->fresh(), 'Company updated successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Company Not Found', ['error' => "Can't find Model of Company"], 404);
        } catch (Exception $e) {
            return $this->sendError('Failed to update company', ['error' => $e->getMessage()], 500);
        }
    }
    public function approve($id)
    {
        try {
            $company = Company::findOrFail($id);
            if (!$company) {
                return response()->json(['message' => 'company is Not Found'], 404);
            }
            $company->is_approved = true;
            $company->status = 'approved';
            $company->reason = null;
            $company->approved_by = Auth::user() ?? 2;
            $company->approved_at = now();
            $company->save();
            return $this->sendResponse($company, 'Company approved successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Company not found', ['error' => 'Company Model not found'], 404);
        } catch (Exception $e) {
            return $this->sendError('Approval failed', ['error' => $e->getMessage()], 500);
        }

    }
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        try {
            $company = Company::findOrFail($id);

            $company->is_approved = false;
            $company->status = 'rejected';
            $company->reason = $request->reason;
            $company->approved_by = Auth::id() ?? 2;
            $company->approved_at = now();
            $company->save();

            return $this->sendResponse($company, 'Company rejected successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Company Not Found', ['error' => 'Company Model Not Found'], 404);
        } catch (Exception $e) {
            return $this->sendError('Reject Failed', ['error' => $e->getMessage()], 500);
        }
    }

    public function uploadLogo(Request $request, $id)
    {
        try {
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
            $company = Company::findOrFail($id);
            if (!$company) {
                return response()->json(['message' => 'company is Not Found'], 404);
            }
            $user = Auth::user();
            $path = $request->file('logo')->store('logos', 'public');

            // delete old logo if exists
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $company->logo_path = $path;
            $company->save();
            return $this->sendResponse($path, 'Logo uploaded successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Company Not Found', ['error' => 'Company Model Not Found'], 404);
        } catch (Exception $e) {
            return $this->sendError('Failed to upload logo', ['error' => $e->getMessage()], 500);
        }
    }

}
