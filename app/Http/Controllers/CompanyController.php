<?php

namespace App\Http\Controllers;


use App\Http\Controllers\API\AuthController;
use Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use Illuminate\Validation\ValidationException;
use Storage;
use Symfony\Contracts\Service\Attribute\Required;
class CompanyController extends Controller
{
    //create new company
    public function store(Request $request)
    {
        try {
            // Validation rules
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'website' => 'nullable|url',
                'industry' => 'nullable|string|max:255',
                'size' => 'nullable|in:startup,small,medium,large,enterprise',
                'location' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|unique:companies,contact_email',
                'contact_phone' => [
                    'required',
                    'regex:/^(\+20|0020|20)?(01[0-9]{9}|0[2-9][0-9]{7,8})$/'
                ],
                'linkedin_url' => 'nullable|url'
            ], [
                'name.required' => 'Please Enter Name of Your Company',
                'description.required' => 'Please Enter Company Description',
                'contact_phone.required' => 'Please Enter Your Contact Phone Number',
                'contact_phone.regex' => 'Please Enter a Valid Egyptian Phone Number',
                'website.url' => 'Please Enter a Valid Website URL',
                'contact_email.email' => 'Please Enter a Valid Email Address',
                'contact_email.unique' => 'This Email Already Exist',
                'linkedin_url.url' => 'Please Enter a Valid LinkedIn URL',
                'size.in' => 'Company size must be one of: startup, small, medium, large, enterprise'
            ]);

            $data['is_approved'] = false;
            $company = Company::create($data);

            return response()->json($company, 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // get all companies
    public function index(Request $request)
    {
        try {
            // using qruey so that i can filter the response
            $query = Company::query();
            if ($request->has('is_approved')) {
                $query->where('is_approved', $request->boolean('is_approved'));
            }

            if ($request->has('industry')) {
                $query->where('industry', $request->industry);
            }

            if ($request->has('size')) {
                $query->where('size', $request->size);
            }
            $companies = $query->latest()->get();

            return response()->json($companies);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve companies',
                'error' => $e->getMessage()
            ], 500);
        }


    }
    //get company by id
    public function show($id)
    {
        try {
            $company = Company::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Company retrieved successfully',
                'data' => $company
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve company',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //update company
    public function update(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'website' => 'nullable|url',
                'industry' => 'nullable|string|max:255',
                'size' => 'nullable|in:startup,small,medium,large,enterprise',
                'location' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|unique:companies,contact_email',
                'contact_phone' => [
                    'required',
                    'regex:/^(\+20|0020|20)?(01[0-9]{9}|0[2-9][0-9]{7,8})$/'
                ],
                'linkedin_url' => 'nullable|url'
            ], [
                'name.required' => 'Please Enter Name of Your Company',
                'description.required' => 'Please Enter Company Description',
                'contact_phone.required' => 'Please Enter Your Contact Phone Number',
                'contact_phone.regex' => 'Please Enter a Valid Egyptian Phone Number',
                'website.url' => 'Please Enter a Valid Website URL',
                'contact_email.email' => 'Please Enter a Valid Email Address',
                'contact_email.unique' => 'This Email Already Exist',
                'linkedin_url.url' => 'Please Enter a Valid LinkedIn URL',
                'size.in' => 'Company size must be one of: startup, small, medium, large, enterprise'
            ]);
            $company->update($data);
            return response()->json([
                'success' => 'true',
                'message' => 'company updated successfully',
                'data' => $company->fresh()
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company',
                'error' => $e->getMessage()
            ], 500);
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
            $company->approved_by = Auth::user()??2;
            $company->approved_at = now();
            $company->save();
            return response()->json(['message' => 'company approved successfully', 'data' => $company], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Company not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Approval failed', 'error' => $e->getMessage()], 500);
        }

    }
    public function reject($id)
    {
        try {
            $company = Company::findOrFail($id);
            if (!$company) {
                return response()->json(['message' => 'company is Not Found'], 404);
            }

            $company->is_approved = false;
            $company->approved_by = Auth::id()??2;
            $company->approved_at = now();
            $company->save();

            return response()->json(['message' => 'Company rejected successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Company not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Rejection failed', 'error' => $e->getMessage()], 500);
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
            return response()->json(['message' => 'Logo uploaded successfully', 'logo_path' => $path]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Company not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to upload logo', 'error' => $e->getMessage()], 500);
        }
    }

}
