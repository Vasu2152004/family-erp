<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\Notification as NotificationModel;
use Illuminate\Notifications\Notification;

class DatabaseWithMetaChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $data = method_exists($notification, 'toDatabase')
            ? $notification->toDatabase($notifiable)
            : (method_exists($notification, 'toArray') ? $notification->toArray($notifiable) : []);

        $data = is_array($data) ? $data : [];

        $payload = [
            'type' => get_class($notification),
            'data' => $data,
            'read_at' => null,
            'user_id' => $notifiable->id ?? null,
            'tenant_id' => $notifiable->tenant_id ?? null,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
        ];

        NotificationModel::create($payload);
    }
}


