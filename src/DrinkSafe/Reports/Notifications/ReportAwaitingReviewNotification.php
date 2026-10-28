<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ReportAwaitingReviewNotification
 *
 * Tells the moderators that a new report is in the review queue. The email
 * carries no report text and no venue, so nothing sensitive sits in an inbox.
 */
final class ReportAwaitingReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A Drink Safe report is waiting for review')
            ->line('A new report has been submitted and is waiting for review.')
            ->action('Review reports', route('admin.reports.index'));
    }
}
