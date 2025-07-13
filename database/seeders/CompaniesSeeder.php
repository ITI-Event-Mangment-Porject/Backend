<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CompaniesSeeder extends Seeder
{
    public function run(): void
    {
        $approver = User::first();

        // Clean old generated logos
        Storage::disk('public')->deleteDirectory('companies');
        Storage::disk('public')->makeDirectory('companies');

        // Create well-known tech companies
        $companies = [
            [
                'name' => 'TechCorp Solutions',
                'image' => 'logo_techcorp.png',
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
                'image' => 'logo_datavision.png',
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
                'image' => 'logo_securenet.png',
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
                'image' => 'logo_creativedesign.png',
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
                'image' => 'logo_digitalboost.png',
                'industry' => 'Marketing',
                'size' => 'startup',
                'location' => 'Beirut, Lebanon',
                'is_approved' => false,
            ],
        ];

        foreach ($companies as $companyData) {
            // Generate fake logo image and copy it
            $filename = $companyData['image'];
            $fakePath = storage_path("app/public/companies/{$filename}");

            $img = imagecreatetruecolor(200, 200);
            $bgColor = imagecolorallocate($img, 220, 220, 220); // light gray
            imagefill($img, 0, 0, $bgColor);

            $textColor = imagecolorallocate($img, 0, 0, 0);
            imagestring($img, 5, 30, 90, 'Company', $textColor);

            imagepng($img, $fakePath);
            imagedestroy($img);

            $companyData['logo_path'] = "companies/{$filename}";
            unset($companyData['image']);

            $company = Company::factory()->make();
            $company->fill($companyData)->save();

        }

        // Create additional random companies
        Company::factory(25)->approved()->create();
        Company::factory(10)->pending()->create();
    }
}
