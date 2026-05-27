<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceProduction Middleware
 *
 * Enforces security settings required for production environments.
 * Prevents common misconfigurations that could expose sensitive data.
 */
final class EnforceProduction
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (App::environment('production')) {
            $this->enforceDebugMode();
            $this->enforceHttps($request);
        }

        return $next($request);
    }

    /**
     * Ensure debug mode is disabled in production.
     *
     * @throws RuntimeException If debug mode is enabled in production
     */
    private function enforceDebugMode(): void
    {
        if (config('app.debug')) {
            throw new RuntimeException(
                'Application debug mode must be disabled in production environment. '.
                'Set APP_DEBUG=false in your .env file.'
            );
        }
    }

    /**
     * Enforce HTTPS connections in production.
     *
     * Redirects HTTP requests to HTTPS if not already secure.
     */
    private function enforceHttps(Request $request): void
    {
        if (! $request->secure() && ! $this->isLocalhost($request)) {
            // Force HTTPS URL generation
            URL::forceScheme('https');

            // Note: Actual redirect should be handled by web server (nginx/apache)
            // or load balancer for performance reasons. This forces URL generation only.
        }
    }

    /**
     * Check if request is from localhost (development/testing).
     */
    private function isLocalhost(Request $request): bool
    {
        $host = $request->getHost();

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
