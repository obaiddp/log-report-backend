<?php

namespace App\Notifications;

use App\Models\AssetInspection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class InspectionNeedsSignature extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AssetInspection $inspection)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Inspection awaiting your signature')
            ->body("{$this->inspection->brand} {$this->inspection->model} for {$this->inspection->username}")
            ->data(['url' => "/asset-inspections/{$this->inspection->id}"]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'inspection_id' => $this->inspection->id,
            'title' => 'Inspection awaiting your signature',
        ];
    }
}