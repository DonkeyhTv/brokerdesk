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

        return (new MailMessage)
            ->subject("Échéance dans moins de {$this->thresholdDays} jours : {$contract->policy_number}")
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("Le contrat {$contract->policy_number} de {$contract->client->displayName()} arrive à échéance le {$contract->expires_on->format('d/m/Y')}.")
            ->line($deadline->isPast()
                ? "Le délai de préavis de trois mois est dépassé depuis le {$deadline->format('d/m/Y')} : le contrat sera reconduit tacitement."
                : "Le contrat peut encore être résilié jusqu'au {$deadline->format('d/m/Y')}, trois mois avant son échéance.")
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
