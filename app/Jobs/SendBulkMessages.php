<?php

namespace App\Jobs;

use App\Models\Notifications_and_Messaging\BulkMessage;
use App\Models\Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        // استخراج المستخدمين حسب الدور
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
                \Log::error('Bulk message failed for user ' . $user->id, [
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
        // الإرسال الفعلي (مثلاً إشعار داخلي)
        $user->notify(new \App\Notifications\BulkMessageNotification($this->message));
    }
}
