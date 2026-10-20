<?php

namespace App\Notifications;

use App\Models\AssetInspection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class InspectionReturned extends Notification implements ShouldQueue
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
            ->title('Inspection returned to you')
            ->body($this->inspection->return_reason ?: 'The admin returned this inspection for changes.')
            ->data(['url' => "/asset-inspections/{$this->inspection->id}"]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'inspection_id' => $this->inspection->id,
            'title' => 'Inspection returned to you',
            'reason' => $this->inspection->return_reason,
        ];
    }
}