<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\GenericNotification;

class NotificationService
{
    public function sendNotification(User $user, array $data): void
    {
        $user->notify(new GenericNotification($data));
    }
}
