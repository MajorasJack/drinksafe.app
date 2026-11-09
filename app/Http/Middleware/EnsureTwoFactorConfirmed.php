<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps an account without confirmed two-factor out of the admin area.
 *
 * The column is read on every request, so turning two-factor off in settings
 * locks the admin area again straight away.
 */
final class EnsureTwoFactorConfirmed
{
    private const string MESSAGE = 'Turn on two-factor authentication to use the admin area.';

    /**
     * Send the user to the security settings to set up two-factor, or refuse a JSON request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        abort_if($request->expectsJson(), Response::HTTP_FORBIDDEN, self::MESSAGE);

        Inertia::flash('toast', ['type' => 'warning', 'message' => self::MESSAGE]);

        return redirect()->route('security.edit')->with('status', self::MESSAGE);
    }
}
