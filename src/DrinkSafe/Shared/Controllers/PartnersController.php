<?php

declare(strict_types=1);

namespace DrinkSafe\Shared\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Shared\Seo\Seo;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PartnersController
 *
 * Displays the page for venues and partners who are deciding whether to
 * put up Drink Safe material. The download sizes are read from the files on
 * each request, so the link text stays right when a file is rebuilt.
 */
final class PartnersController extends Controller
{
    /**
     * Files linked from the page, in `public/downloads`.
     */
    public const array DOWNLOADS = [
        'drink-safe-partner-pack.zip',
        'drink-safe-poster-a4.pdf',
        'drink-safe-poster-a3.pdf',
        'drink-safe-flyer.pdf',
        'drink-safe-sticker-a.pdf',
        'drink-safe-sticker-b.pdf',
    ];

    /**
     * Display the venues and partners page.
     *
     * @return Response Inertia response
     */
    public function __invoke(): Response
    {
        $seo = Seo::default()
            ->withTitle('For Venues and Partners - Drink Safe')
            ->withDescription('How Drink Safe reviews reports, where to put up our posters and stickers, and free printable downloads for venues, students\' unions and community groups.');

        return Inertia::render('Partners', [
            'seo' => $seo->toArray(),
            'downloadSizes' => collect(self::DOWNLOADS)
                ->mapWithKeys(fn (string $fileName): array => [
                    sprintf('/downloads/%s', $fileName) => Number::fileSize(filesize(public_path(sprintf('downloads/%s', $fileName)))),
                ])
                ->all(),
        ]);
    }
}
