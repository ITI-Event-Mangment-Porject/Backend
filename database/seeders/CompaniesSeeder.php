<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use App\Models\Auth\User;
use Illuminate\Database\Seeder;

class CompaniesSeeder extends Seeder
{
    public function run(): void
    {
        $approver = User::first();

        // Create well-known tech companies
        $companies = [
            [
                'name' => 'TechCorp Solutions',
                'industry' => 'Technology',
                'size' => 'large',
                'location' => 'Dubai, UAE',
                'is_approved' => true,
                'status'=> 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now()->subDays(30),
            ],
            [
                'name' => 'DataVision Analytics',
                'industry' => 'Technology',
                'size' => 'medium',
                'location' => 'Cairo, Egypt',
                'status' => 'approved',
                'is_approved' => true,
                'approved_by' => $approver->id,
                'approved_at' => now()->subDays(25),
            ],
            [
                'name' => 'SecureNet Systems',
                'industry' => 'Cybersecurity',
                'size' => 'medium',
                'location' => 'Riyadh, Saudi Arabia',
                'is_approved' => true,
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now()->subDays(20),
            ],
            [
                'name' => 'CreativeDesign Studio',
                'industry' => 'Design',
                'size' => 'small',
                'location' => 'Amman, Jordan',
                'status' => 'approved',
                'is_approved' => true,
                'approved_by' => $approver->id,
                'approved_at' => now()->subDays(15),
            ],
            [
                'name' => 'DigitalBoost Marketing',
                'industry' => 'Marketing',
                'size' => 'startup',
                'location' => 'Beirut, Lebanon',
                'is_approved' => false,
            ],
        ];

        foreach ($companies as $companyData) {
            Company::factory()->create($companyData);
        }

        // Create additional random companies
        Company::factory(25)->approved()->create();
        Company::factory(10)->pending()->create();
    }
}