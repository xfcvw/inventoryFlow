<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('InventoryFlow workspace invitation')
            ->greeting('You were invited to ' . $this->invitation->workspace->name)
            ->line('Role: ' . $this->invitation->role)
            ->action('Accept invitation', route('invitation.show', $this->invitation->token))
            ->line('This invitation expires in 7 days.');
    }
}
