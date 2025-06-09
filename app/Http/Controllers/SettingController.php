<?php

namespace App\Http\Controllers;

use App\Models\System_Config\SystemSetting;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        try {
            $settings = SystemSetting::all();
            return response()->json(['message' => 'all settings', 'data' => $settings], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system settings.'
            ], 500);
        }

    }
    public function update(Request $request, $key)
    {
        try {
            $vaildated = $request->validate([
                'setting_value' => 'required',
                'setting_type' => 'nullable|in:string,boolean,integer,json',
            ]);
            // remove auth when you test cause i didn't handle middleware and auth yet
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            $setting = SystemSetting::set(
                $key,
                $vaildated['setting_value'],
                $vaildated['setting_type']
            );
            return response()->json([
                'message' => 'setting Updated successfully',
                'data' => $setting
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                 ], 422);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to update setting.'
            ], 500);
        }
    }
    public function getPublic()
    {
        try {
            $settings = SystemSetting::where('is_public', true)->get();

            return response()->json([
                'message' => 'public settings',
                'data' => $settings
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve public settings.'
            ], 500);
        }

    }
}
