<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\TimezoneManager;
use Symfony\Component\HttpFoundation\Response;

class DetectTimezone
{
    public function __construct(
        protected TimezoneManager $timezoneManager
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve timezone context for the current request
        $this->timezoneManager->resolve($request);

        return $next($request);
    }
}
