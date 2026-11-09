<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use DrinkSafe\Shared\Seo\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Admin sidebar items, by route name.
     *
     * Each one is shared only once its route exists, so a page can be added to
     * this list before it is built.
     *
     * @var array<string, string>
     */
    private const array ADMIN_NAV = [
        'admin.dashboard' => 'Dashboard',
        'admin.reports.index' => 'Review queue',
        'admin.reports.search' => 'Reports',
        'admin.venues.index' => 'Venues',
        'admin.partners.index' => 'Partners',
        'admin.coverage' => 'Coverage',
    ];

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error' => fn (): ?string => $request->session()->get('error'),
                'warning' => fn (): ?string => $request->session()->get('warning'),
            ],
            'auth' => [
                'user' => fn (): ?array => $request->user()
                    ? $request->user()->only(['id', 'name', 'email', 'is_admin'])
                    : null,
            ],
            'admin' => [
                'nav' => fn (): array => $request->user()?->is_admin ? $this->adminNav() : [],
            ],
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
            'seo' => fn (): array => Seo::default()->toArray(),
            'turnstile' => [
                'siteKey' => config('services.turnstile.key'),
            ],
            'carto' => [
                'apiKey' => config('services.carto.key'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Build the admin sidebar items whose routes exist.
     *
     * @return list<array{name: string, title: string, href: string}>
     */
    private function adminNav(): array
    {
        return collect(self::ADMIN_NAV)
            ->filter(fn (string $title, string $routeName): bool => Route::has($routeName))
            ->map(fn (string $title, string $routeName): array => ['name' => $routeName, 'title' => $title, 'href' => route($routeName, [], false)])
            ->values()
            ->all();
    }
}
