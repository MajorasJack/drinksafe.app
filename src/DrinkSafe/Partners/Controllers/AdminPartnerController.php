<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Controllers;

use App\Http\Controllers\Controller;
use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Partners\Requests\AdminPartnerRangeRequest;
use DrinkSafe\Partners\Requests\AdminUpdatePartnerLinkRequest;
use DrinkSafe\Partners\Services\PartnerScanReport;
use DrinkSafe\Shared\Seo\Seo;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AdminPartnerController
 *
 * The admin's partner links: scan totals over a date range, and editing a
 * link's label, destination and whether it's switched on. Links are added
 * from the command line, and there is no delete. Access is limited to admins
 * by the admin route group.
 */
final class AdminPartnerController extends Controller
{
    /**
     * Create a new AdminPartnerController instance.
     */
    public function __construct(private readonly PartnerScanReport $partnerScanReport) {}

    /**
     * Show every partner link with its total scans over the chosen range.
     *
     * @param  AdminPartnerRangeRequest  $request  The validated date range
     * @return Response Inertia response for the partners page
     */
    public function index(AdminPartnerRangeRequest $request): Response
    {
        ['from' => $from, 'to' => $to] = $request->range();

        return Inertia::render('admin/Partners', [
            'seo' => Seo::default()->withTitle('Partners - Drink Safe')->noindex()->toArray(),
            'partners' => $this->partnerScanReport->totals(from: $from, to: $to)->all(),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }

    /**
     * Save the admin's edit of a partner link.
     *
     * @param  AdminUpdatePartnerLinkRequest  $request  The validated changes
     * @param  string  $code  The partner link's code
     * @return RedirectResponse Back to the page the admin came from
     */
    public function update(AdminUpdatePartnerLinkRequest $request, string $code): RedirectResponse
    {
        PartnerLink::query()->where('code', $code)->firstOrFail()->update($request->validated());

        return redirect()->to(url()->previous(route('admin.partners.index')))->with('success', 'Partner link saved.');
    }
}
