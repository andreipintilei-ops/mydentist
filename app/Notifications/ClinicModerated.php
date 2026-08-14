<?php

namespace App\Notifications;

use App\Models\Clinic;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClinicModerated extends Notification
{
    use Queueable;

    public function __construct(
        public Clinic $clinic,
        public bool $approved,
        public ?string $reason = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject(
            ($this->approved ? 'Clinica ta a fost publicată' : 'Actualizare privind clinica ta')
                .' — '.config('app.name')
        );

        if ($this->approved) {
            return $message
                ->line("Clinica „{$this->clinic->name}” a fost verificată și este acum publică.")
                ->action('Vezi clinica', route('clinic.show', $this->clinic));
        }

        return $message
            ->line("Clinica „{$this->clinic->name}” nu a putut fi publicată deocamdată.")
            ->when($this->reason, fn (MailMessage $m) => $m->line("Motiv: {$this->reason}"))
            ->line('Poți actualiza datele și retrimite clinica spre verificare.');
    }
}
