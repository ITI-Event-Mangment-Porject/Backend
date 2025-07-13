<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\NotificationsAndMessaging\Notification;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Notification $notification)
    {
        return $user->id === $notification->user_id;
    }

    public function delete(User $user, Notification $notification)
    {
        return $user->id === $notification->user_id;
    }
}
