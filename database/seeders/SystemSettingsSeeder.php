<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'setting_key' => 'app_name',
                'setting_value' => 'ITIVENT',
                'setting_type' => 'string',
                'description' => 'Application name',
                'is_public' => true,
            ],
            [
                'setting_key' => 'max_interview_duration',
                'setting_value' => '30',
                'setting_type' => 'integer',
                'description' => 'Maximum interview duration in minutes',
                'is_public' => false,
            ],
            [
                'setting_key' => 'auto_registration_enabled',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'Enable automatic event registration',
                'is_public' => false,
            ],
            [
                'setting_key' => 'notification_settings',
                'setting_value' => json_encode([
                    'email_enabled' => true,
                    'sms_enabled' => false,
                    'push_enabled' => true
                ]),
                'setting_type' => 'json',
                'description' => 'Notification channel settings',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }
    }
}