<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\StoreSettingRequest;
use App\Models\SystemConfig\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class SettingController extends BaseApiController
{
    public function index()
    {
        try {
            $settings = QueryBuilder::for(SystemSetting::class)
                ->allowedFilters([
                    AllowedFilter::partial('setting_key'),
                    AllowedFilter::exact('setting_type'),
                    AllowedFilter::exact('is_public'),
                ])
                ->get();
            return $this->sendResponse($settings, 'All system settings retrieved successfully.');
        } catch (Exception $e) {
            \Log::error('Settings index failed: ' . $e->getMessage());
            return $this->sendError('Failed to retrieve system settings.', [], 500);
        }
    }

    public function update(StoreSettingRequest $request, $key)
    {
        try {
            $validated =$request->validated();
            // Temporarily disable auth check if not yet handled
            // if (!auth()->check()) {
            //     return $this->sendError('Unauthorized', [], 403);
            // }

            $setting = SystemSetting::set(
                $key,
                $validated['setting_value'],
                $validated['setting_type']
            );

            return $this->sendResponse($setting, 'Setting updated successfully.');

        } catch (ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), 422);
        } catch (Exception $e) {
            \Log::error('Setting update failed: ' . $e->getMessage());
            return $this->sendError('Failed to update setting.', ['error' => $e->getMessage()], 500);
        }
    }

    public function getPublic()
    {
        try {
            $settings = QueryBuilder::for(SystemSetting::class)
            ->allowedFilters([
                AllowedFilter::partial('setting_key'),
                AllowedFilter::exact('setting_type'),
            ])
            ->where('is_public', true)
            ->get();
            return $this->sendResponse($settings, 'Public system settings retrieved successfully.');
        } catch (Exception $e) {
            \Log::error('Get public settings failed: ' . $e->getMessage());
            return $this->sendError('Failed to retrieve public settings.', ['error' => $e->getMessage()], 500);
        }
    }
}
