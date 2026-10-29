<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ReportAwaitingReviewNotification
 *
 * Tells the moderators that a new report is in the review queue. The email
 * carries no report text and no venue, so nothing sensitive sits in an inbox.
 * It sends during the request, so it doesn't depend on a queue worker running.
 */
final class ReportAwaitingReviewNotification extends Notification
{
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
