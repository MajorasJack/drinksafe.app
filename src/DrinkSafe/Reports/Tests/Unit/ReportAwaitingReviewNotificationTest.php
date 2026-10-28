<?php

declare(strict_types=1);

use DrinkSafe\Reports\Notifications\ReportAwaitingReviewNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;

uses(TestCase::class);

describe('ReportAwaitingReviewNotification', function (): void {
    it('links to the review queue', function (): void {
        $mail = (new ReportAwaitingReviewNotification)->toMail(new AnonymousNotifiable);

        expect($mail->actionUrl)->toBe(route('admin.reports.index'));
    });

    it('says only that a report is waiting', function (): void {
        $mail = (new ReportAwaitingReviewNotification)->toMail(new AnonymousNotifiable);

        expect($mail->introLines)->toBe(['A new report has been submitted and is waiting for review.'])
            ->and($mail->outroLines)->toBe([]);
    });

    it('is sent during the request rather than queued', function (): void {
        expect(new ReportAwaitingReviewNotification)->not->toBeInstanceOf(ShouldQueue::class);
    });
});
