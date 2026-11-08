<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortalInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $url,
        public string $brokerName,
        public string $brokerageName,
    ) {}

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
            ->subject(__('Your client portal at :brokerage', ['brokerage' => $this->brokerageName]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__(':broker invites you to the client portal of :brokerage: your contracts, your documents and your claims, online.', [
                'broker' => $this->brokerName,
                'brokerage' => $this->brokerageName,
            ]))
            ->action(__('Choose my password'), $this->url)
            ->line(__('This link stays valid for :days days.', ['days' => 7]))
            ->salutation('BrokerDesk');
    }
}
