<?php

declare(strict_types=1);

use DrinkSafe\Reports\Models\Report;
use DrinkSafe\Reports\Services\ReportModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new ReportModerationService;
});

describe('ReportModerationService', function (): void {
    describe('validateDescription', function (): void {
        it('returns false for email addresses', function (): void {
            // Arrange
            $description = sprintf('Contact me at %s for details', fake()->email());

            // Act
            $result = $this->service->validateDescription($description);

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns false for UK phone numbers with +44 prefix', function (): void {
            // Arrange
            $description = 'Call me on +44 7700 900123';

            // Act
            $result = $this->service->validateDescription($description);

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns false for UK phone numbers with 0 prefix', function (): void {
            // Arrange
            $description = 'Call me on 0207 123 4567';

            // Act
            $result = $this->service->validateDescription($description);

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns false for general phone numbers', function (): void {
            // Arrange
            $description = 'Call me at 555-123-4567';

            // Act
            $result = $this->service->validateDescription($description);

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns true for clean descriptions', function (): void {
            // Arrange
            $description = 'I witnessed an incident at this venue last night. Staff were unhelpful.';

            // Act
            $result = $this->service->validateDescription($description);

            // Assert
            expect($result)->toBeTrue();
        });
    });

    describe('sanitizeDescription', function (): void {
        it('masks email addresses with [EMAIL REMOVED]', function (): void {
            // Arrange
            $email = fake()->email();
            $description = sprintf('Contact me at %s for more info', $email);

            // Act
            $sanitised = $this->service->sanitizeDescription($description);

            // Assert
            expect($sanitised)->not->toContain($email);
            expect($sanitised)->toContain('[EMAIL REMOVED]');
        });

        it('masks UK phone numbers with [PHONE REMOVED]', function (): void {
            // Arrange
            $description = 'Call me on +44 7700 900123 or 0207 123 4567';

            // Act
            $sanitised = $this->service->sanitizeDescription($description);

            // Assert
            expect($sanitised)->not->toContain('+44 7700 900123');
            expect($sanitised)->not->toContain('0207 123 4567');
            expect($sanitised)->toContain('[PHONE REMOVED]');
        });

        it('masks general phone numbers with [PHONE REMOVED]', function (): void {
            // Arrange
            $description = 'My number is 555-123-4567';

            // Act
            $sanitised = $this->service->sanitizeDescription($description);

            // Assert
            expect($sanitised)->not->toContain('555-123-4567');
            expect($sanitised)->toContain('[PHONE REMOVED]');
        });

        it('preserves clean text', function (): void {
            // Arrange
            $description = 'This is a clean description without any PII.';

            // Act
            $sanitised = $this->service->sanitizeDescription($description);

            // Assert
            expect($sanitised)->toBe($description);
        });

        it('handles multiple PII instances', function (): void {
            // Arrange
            $email = fake()->email();
            $description = sprintf(
                'Contact %s or call 555-123-4567 or +44 7700 900123',
                $email
            );

            // Act
            $sanitised = $this->service->sanitizeDescription($description);

            // Assert
            expect($sanitised)->not->toContain($email);
            expect($sanitised)->not->toContain('555-123-4567');
            expect($sanitised)->not->toContain('+44 7700 900123');
            expect($sanitised)->toContain('[EMAIL REMOVED]');
            expect($sanitised)->toContain('[PHONE REMOVED]');
        });
    });

    describe('flagSuspiciousReport', function (): void {
        it('logs suspicious report for manual review', function (): void {
            // Arrange
            $report = Report::factory()->make();

            Log::shouldReceive('warning')
                ->once()
                ->with(sprintf('Report %s flagged as suspicious for manual review', $report->uuid));

            // Act
            $this->service->flagSuspiciousReport($report);

            // Assert
        });
    });
});
