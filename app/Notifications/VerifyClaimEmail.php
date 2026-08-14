<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyClaimEmail extends Notification
{
    use Queueable;

    public function __construct(public Claim $claim) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('claim.verify', ['token' => $this->claim->verification_token]);

        return (new MailMessage)
            ->subject('Confirmă revendicarea clinicii — '.config('app.name'))
            ->greeting('Salut!')
            ->line("Ai solicitat administrarea clinicii „{$this->claim->clinic->name}” pe ".config('app.name').'.')
            ->line('Confirmă că această adresă de email îți aparține, apoi echipa noastră va verifica solicitarea.')
            ->action('Confirmă adresa de email', $url)
            ->line('Dacă nu ai făcut tu această solicitare, poți ignora acest mesaj.');
    }
}
