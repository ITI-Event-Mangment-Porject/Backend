<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\NotificationsAndMessaging\BulkMessage;

use Illuminate\Auth\Access\HandlesAuthorization;

class BulkMessagePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasRole('admin');
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function send(User $user, BulkMessage $message)
    {
        return $user->hasRole('admin') && $user->id === $message->sent_by;
    }

    public function view(User $user, BulkMessage $message)
{
    return $user->hasRole('admin');
}

}
