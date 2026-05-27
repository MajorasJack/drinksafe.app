<?php

declare(strict_types=1);

namespace DrinkSafe\Reports\Services;

use DrinkSafe\Reports\Models\Report;
use Illuminate\Support\Facades\Log;

/**
 * ReportModerationService
 *
 * Handles validation and sanitisation of report content to prevent
 * personally identifiable information (PII) from being stored.
 * Detects and removes email addresses and phone numbers.
 */
final class ReportModerationService
{
    /**
     * Validate description for PII content.
     *
     * Checks for common PII patterns including email addresses,
     * UK phone numbers, and general international phone numbers.
     *
     * @param  string  $description  The description to validate
     * @return bool True if description is clean, false if PII detected
     */
    public function validateDescription(string $description): bool
    {
        // Check for email addresses
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $description)) {
            return false;
        }

        // Check for UK phone numbers (+44 or 0 prefix)
        if (preg_match('/(\+44|0)\s?\d{3,4}\s?\d{3,4}\s?\d{3,4}/', $description)) {
            return false;
        }

        // Check for general international phone numbers
        if (preg_match('/\d{3}[-.\s]?\d{3}[-.\s]?\d{4}/', $description)) {
            return false;
        }

        return true;
    }

    /**
     * Sanitise description by removing or masking detected PII.
     *
     * Replaces email addresses with [EMAIL REMOVED] and phone numbers
     * with [PHONE REMOVED] to protect user privacy.
     *
     * @param  string  $description  The description to sanitise
     * @return string Sanitised description with PII masked
     */
    public function sanitizeDescription(string $description): string
    {
        // Replace email addresses
        $description = preg_replace(
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            '[EMAIL REMOVED]',
            $description
        );

        // Replace UK phone numbers
        $description = preg_replace(
            '/(\+44|0)\s?\d{3,4}\s?\d{3,4}\s?\d{3,4}/',
            '[PHONE REMOVED]',
            $description
        );

        // Replace general phone numbers
        $description = preg_replace(
            '/\d{3}[-.\s]?\d{3}[-.\s]?\d{4}/',
            '[PHONE REMOVED]',
            $description
        );

        return $description;
    }

    /**
     * Flag a report as suspicious for manual review.
     *
     * Future implementation: This will flag reports for manual moderation
     * based on content analysis or automated detection patterns.
     * Currently logs the report for monitoring purposes.
     *
     * @param  Report  $report  The report to flag
     */
    public function flagSuspiciousReport(Report $report): void
    {
        // Future implementation: Flag for manual moderation
        Log::warning(sprintf(
            'Report %s flagged as suspicious for manual review',
            $report->uuid
        ));
    }
}
