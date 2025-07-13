<?php

namespace App\Jobs;

use App\Models\NotificationsAndMessaging\BulkMessage;
use App\Models\Auth\User;
use App\Models\NotificationsAndMessaging\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\FirestoreService; // Import the service

class SendBulkMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\NotificationsAndMessaging\BulkMessage  $message
     * @return void
     */
    public function __construct(BulkMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @param  \App\Services\FirestoreService $firebase
     * @return void
     */
    public function handle(FirestoreService $firebase)
    {
        $criteria = $this->message->target_criteria;

        $users = User::when(isset($criteria['roles']), function ($query) use ($criteria) {
            return $query->whereHas('roles', function ($q) use ($criteria) {
                $q->whereIn('name', $criteria['roles']);
            });
        })->get();

        $this->message->update([
            'total_recipients' => $users->count()
        ]);

        foreach ($users as $user) {
            try {
                $this->sendToUser($user);
                $this->message->increment('sent_count');
            } catch (\Exception $e) {
                $this->message->increment('failed_count');
                Log::error('Bulk message failed for user ' . $user->id, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->message->update([
            'status' => 'completed',
            'sent_at' => now(),
        ]);

        // Send Firebase notification after all individual notifications are processed
        try {
            $firebase->sendToAllUsers([
                'title' => 'Bulk Message Sent: ' . $this->message->title,
                'body' => $this->message->message, // Use the actual message content
                'type' => 'bulk_message',
                'id' => $this->message->id, // Pass message ID for potential deep linking
            ]);
            Log::info('Firebase notification sent for bulk message: ' . $this->message->id);
        } catch (\Exception $e) {
            Log::error('Failed to send Firebase notification for bulk message: ' . $this->message->id, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendToUser(User $user)
    {
        Notification::create([
            'user_id'      => $user->id,
            'title'        => $this->message->title,
            'message'      => $this->message->message,
            'type'         => 'bulk_message',
            'related_id'   => $this->message->id,
            'related_type' => BulkMessage::class,
            'is_read'      => false,
            'sent_via'     => ['database'],
        ]);
    }
}
