<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Contract $contract,
        public int $thresholdDays,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function databaseType(User $notifiable): string
    {
        return 'contract_expiring';
    }

    public function toMail(User $notifiable): MailMessage
    {
        $contract = $this->contract;
        $deadline = $contract->noticeDeadline();

        // Sent in the broker's own language: see User::preferredLocale().
        return (new MailMessage)
            ->subject(__('Expiry within :days days: :policy', ['days' => $this->thresholdDays, 'policy' => $contract->policy_number]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Contract :policy of :client expires on :date.', [
                'policy' => $contract->policy_number,
                'client' => $contract->client->displayName(),
                'date' => $contract->expires_on->format('d/m/Y'),
            ]))
            ->line($deadline->isPast()
                ? __('The three-month notice period passed on :date: the contract will renew tacitly.', ['date' => $deadline->format('d/m/Y')])
                : __('The contract can still be cancelled until :date, three months before it expires.', ['date' => $deadline->format('d/m/Y')]))
            ->salutation('BrokerDesk');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        $contract = $this->contract;

        return [
            'contract_id' => $contract->id,
            'policy_number' => $contract->policy_number,
            'client_name' => $contract->client->displayName(),
            'expires_on' => $contract->expires_on->toDateString(),
            'notice_deadline' => $contract->noticeDeadline()->toDateString(),
            'threshold_days' => $this->thresholdDays,
        ];
    }
}
