<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPortalPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $url) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Choose a new password'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Someone asked to change the password of your client portal. If it was you, choose a new one:'))
            ->action(__('Choose a new password'), $this->url)
            ->line(__('This link stays valid for :minutes minutes. If you did not ask for it, ignore this mail: your password does not change.', [
                'minutes' => config('auth.passwords.users.expire'),
            ]))
            ->salutation('BrokerDesk');
    }
}
