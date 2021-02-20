<?php
declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Leonis\Notifications\EasySms\Channels\EasySmsChannel;
use Leonis\Notifications\EasySms\Messages\EasySmsMessage;

class NewOrderSmsNotify extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        return [EasySmsChannel::class];
    }

    /**
     * @param  mixed  $notifiable
     * @return EasySmsMessage|null
     */
    public function toEasySms($notifiable): ?EasySmsMessage
    {
        if ('production' !== app()->environment()) {
            return null;
        }

        return (new EasySmsMessage)
            ->setTemplate(config('aranna.sms.aliyun.captcha_template_code'))
            ->setData(['code' => $notifiable->nickname ?? '']);
    }
}
