<?php

namespace Database\Seeders;

use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MediaFilesSeeder extends Seeder
{
    public function run(): void
    {
        $uploader = User::first();
        
        $mediaFiles = [
            [
                'filename' => 'event-banner-1.jpg',
                'original_name' => 'Annual Tech Job Fair Banner.jpg',
                'file_path' => '/uploads/events/banners/event-banner-1.jpg',
                'file_size' => 1024000,
                'mime_type' => 'image/jpeg',
                'file_type' => 'image',
                'uploaded_by' => $uploader->id,
                'related_type' => 'events',
                'related_id' => 1,
                'is_public' => true,
            ],
            [
                'filename' => 'company-logo-1.png',
                'original_name' => 'TechCorp Logo.png',
                'file_path' => '/uploads/companies/logos/company-logo-1.png',
                'file_size' => 256000,
                'mime_type' => 'image/png',
                'file_type' => 'image',
                'uploaded_by' => $uploader->id,
                'related_type' => 'companies',
                'related_id' => 1,
                'is_public' => true,
            ],
            [
                'filename' => 'user-cv-1.pdf',
                'original_name' => 'John Doe CV.pdf',
                'file_path' => '/uploads/users/cvs/user-cv-1.pdf',
                'file_size' => 512000,
                'mime_type' => 'application/pdf',
                'file_type' => 'document',
                'uploaded_by' => $uploader->id,
                'related_type' => 'users',
                'related_id' => 3,
                'is_public' => false,
            ],
        ];

        foreach ($mediaFiles as $file) {
            DB::table('media_files')->insert(array_merge($file, [
                'uploaded_at' => now(),
            ]));
        }
    }
}
                