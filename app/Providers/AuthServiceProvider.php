<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Notifications_and_Messaging\Notification;
use App\Models\Notifications_and_Messaging\BulkMessage;
use App\Policies\NotificationPolicy;
use App\Policies\BulkMessagePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Notification::class => NotificationPolicy::class,
        BulkMessage::class => BulkMessagePolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}

