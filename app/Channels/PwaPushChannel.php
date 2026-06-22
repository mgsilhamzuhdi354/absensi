<?php

namespace App\Channels;

use App\Services\PwaPushService;

class PwaPushChannel
{
    private $pushService;

    public function __construct(PwaPushService $pushService)
    {
        $this->pushService = $pushService;
    }

    public function send($notifiable, $notification): void
    {
        if (!method_exists($notification, 'toArray')) {
            return;
        }

        $this->pushService->sendToUser($notifiable, $notification->toArray($notifiable));
    }
}
