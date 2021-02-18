<?php
declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Leonis\Notifications\EasySms\Channels\EasySmsChannel;
use Leonis\Notifications\EasySms\Messages\EasySmsMessage;

class VerificationCode extends Notification
{
    use Queueable;

    /**
     * @var string 验证码
     */
    private $code;

    /**
     * Create a new notification instance.
     *
     * @param  string  $code
     * @return void
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via(): array
    {
        return [EasySmsChannel::class];
    }

    /**
     * @return EasySmsMessage
     */
    public function toEasySms(): EasySmsMessage
    {
        return (new EasySmsMessage)
            ->setTemplate(config('aranna.sms.aliyun.captcha_template_code'))
            ->setData(['code' => $this->code]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @return MailMessage
     */
    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            //
        ];
    }
}
