<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Pest\Expectation;

describe('Partner pack', function (): void {
    it('contains exactly the nine pack files', function (): void {
        $zip = new ZipArchive;
        $zip->open(public_path('downloads/drink-safe-partner-pack.zip'));

        $entries = collect(range(0, $zip->numFiles - 1))
            ->map(fn (int $index): string => $zip->getNameIndex($index))
            ->all();

        expect($entries)->toBe([
            'README.txt',
            'drink-safe-about.pdf',
            'drink-safe-poster-a4.pdf',
            'drink-safe-poster-a3.pdf',
            'drink-safe-flyer.pdf',
            'drink-safe-sticker-a.pdf',
            'drink-safe-sticker-b.pdf',
            'drink-safe-reel.mp4',
            'share-copy.txt',
        ]);
    });

    it('names every other packed file in the README', function (): void {
        $zip = new ZipArchive;
        $zip->open(public_path('downloads/drink-safe-partner-pack.zip'));

        $readme = $zip->getFromName('README.txt');

        $entries = collect(range(0, $zip->numFiles - 1))
            ->map(fn (int $index): string => $zip->getNameIndex($index))
            ->reject(fn (string $entry): bool => $entry === 'README.txt');

        expect($entries)->each(fn (Expectation $entry): Expectation => expect($readme)->toContain($entry->value));
    });

    it('holds the same PDF as the one it was built from', function (string $fileName, string $sourcePath): void {
        $zip = new ZipArchive;
        $zip->open(public_path('downloads/drink-safe-partner-pack.zip'));

        $packed = $zip->getFromName($fileName);

        expect($packed)->toBe(file_get_contents(base_path($sourcePath)));
    })->with([
        ['drink-safe-poster-a4.pdf', 'public/downloads/drink-safe-poster-a4.pdf'],
        ['drink-safe-poster-a3.pdf', 'public/downloads/drink-safe-poster-a3.pdf'],
        ['drink-safe-flyer.pdf', 'public/downloads/drink-safe-flyer.pdf'],
        ['drink-safe-sticker-a.pdf', 'public/downloads/drink-safe-sticker-a.pdf'],
        ['drink-safe-sticker-b.pdf', 'public/downloads/drink-safe-sticker-b.pdf'],
        ['drink-safe-about.pdf', 'print/output/about.pdf'],
    ]);

    it('uses the same words in the About Drink Safe PDF as on the site', function (string $sourceFile, string $sentence): void {
        $about = Str::squish(file_get_contents(base_path('print/artwork/about.html')));

        $site = Str::squish(file_get_contents(resource_path(sprintf('js/%s', $sourceFile))));

        expect($about)->toContain($sentence)
            ->and($site)->toContain($sentence);
    })->with([
        ['pages/Partners.vue', 'Drink Safe is a website where people can share and read anonymous reports of drink spiking at venues.'],
        ['pages/Partners.vue', 'Drink Safe will always be free to use, with no ads on the site and no paywalls. Nobody needs an account or has to give their name to read or submit a report.'],
        ['pages/Partners.vue', 'It is not a substitute for reporting crimes to the police.'],
        ['pages/Partners.vue', "A report is one person's account of what happened to them, or what they saw, at a venue. It is not a finding against the venue or its staff."],
        ['pages/Partners.vue', 'Every report is reviewed by a person before it is published. Information provided may not be verified or accurate. This is what happens to a report:'],
        ['pages/Partners.vue', 'Someone submits a report without an account and without giving their name.'],
        ['pages/Partners.vue', 'If our check finds an email address or phone number in the description, the report is turned away and the person is asked to take it out.'],
        ['pages/Partners.vue', 'The report is saved as waiting for review. It does not appear anywhere on the site yet.'],
        ['pages/Partners.vue', 'A person reads it and either publishes it or rejects it.'],
        ['pages/Partners.vue', 'If the report names a venue that is not on Drink Safe yet, the venue stays hidden as well until its first report is published.'],
        ['pages/Partners.vue', 'A published report can still be taken down later.'],
        ['pages/Partners.vue', "with a link to your venue's page on Drink Safe and the date shown on the report. We'll look at it and remove anything that"],
        ['pages/Partners.vue', 'For questions about putting material up, or about a report on the site, email us at:'],
        ['pages/Privacy.vue', "If you scan a QR code from one of our partners, we add one to that partner's total for the day. That's all we count: nothing about you, your device or where you came from is stored."],
        ['components/layout/AppFooter.vue', 'Drink Safe is an informational platform only. Information provided may not be verified or accurate. This does not replace reporting to authorities. If you have experienced a crime, please contact the police directly.'],
    ]);
});
