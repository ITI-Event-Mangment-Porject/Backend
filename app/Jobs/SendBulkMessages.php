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

class SendBulkMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;

    public function __construct(BulkMessage $message)
    {
        $this->message = $message;
    }

    public function handle()
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
