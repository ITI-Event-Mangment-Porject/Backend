<?php

namespace Database\Seeders;

use App\Models\NotificationsAndMessaging\Notification;
use App\Models\NotificationsAndMessaging\BulkMessage;
use App\Models\Auth\User;
use Illuminate\Database\Seeder;

class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $sender = User::first();

        // Create individual notifications
        foreach ($users->random(30) as $user) {
            // Create various types of notifications
            Notification::factory(rand(3, 8))->create([
                'user_id' => $user->id,
            ]);

            // Create some unread notifications
            Notification::factory(rand(1, 3))->unread()->create([
                'user_id' => $user->id,
            ]);
        }

        // Create bulk messages
        $bulkMessages = [
            [
                'title' => 'Welcome to ITI Events Platform',
                'message' => 'Welcome to the new ITI Events platform! Here you can register for events, apply for job fair interviews, and stay updated with the latest ITI activities.',
                'target_criteria' => json_encode(['roles' => ['student']]),
                'status' => 'completed',
                'total_recipients' => 150,
                'sent_count' => 150,
                'sent_at' => now()->subDays(30),
            ],
            [
                'title' => 'Upcoming Job Fair - Register Now!',
                'message' => 'Don\'t miss our upcoming Annual Tech Job Fair! Registration is now open. Visit the events page to register and apply for interviews.',
                'target_criteria' => json_encode(['tracks' => [1, 2, 3, 4, 5]]),
                'status' => 'completed',
                'total_recipients' => 120,
                'sent_count' => 118,
                'failed_count' => 2,
                'sent_at' => now()->subDays(10),
            ],
            [
                'title' => 'System Maintenance Notification',
                'message' => 'The platform will undergo scheduled maintenance this weekend. Please save your work and expect brief interruptions.',
                'target_criteria' => json_encode(['roles' => ['all']]),
                'status' => 'sending',
                'total_recipients' => 200,
                'sent_count' => 150,
                'scheduled_at' => now()->addHours(2),
            ],
        ];

        foreach ($bulkMessages as $messageData) {
            BulkMessage::create(array_merge($messageData, [
                'sent_by' => $sender->id,
            ]));
        }
    }
}
