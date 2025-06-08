<?php

namespace Database\Seeders;

use App\Models\Auth\Track;
use Illuminate\Database\Seeder;

class TracksSeeder extends Seeder
{
        public function run()
        {
            $tracks = [
                ['name' => 'Software Development', 'slug' => 'software-development', 'color' => '#3B82F6', 'icon' => 'code'],
                ['name' => 'Data Science', 'slug' => 'data-science', 'color' => '#8B5CF6', 'icon' => 'chart-bar'],
                ['name' => 'Cybersecurity', 'slug' => 'cybersecurity', 'color' => '#EF4444', 'icon' => 'shield'],
                ['name' => 'UI/UX Design', 'slug' => 'ui-ux-design', 'color' => '#F59E0B', 'icon' => 'palette'],
                ['name' => 'Digital Marketing', 'slug' => 'digital-marketing', 'color' => '#10B981', 'icon' => 'megaphone'],
            ];

            foreach ($tracks as $track) {
                Track::updateOrCreate(
                    ['name' => $track['name']],  // unique key
                    [
                        'slug' => $track['slug'],
                        'description' => 'Description for ' . $track['name'],  // or use faker if you want
                        'color' => $track['color'],
                        'icon' => $track['icon'],
                        'is_active' => true,
                        'sort_order' => array_search($track, $tracks) + 1,
                    ]
                );
            }
        }

}