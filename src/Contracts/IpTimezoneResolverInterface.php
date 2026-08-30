<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Contracts;

interface IpTimezoneResolverInterface
{
    /**
     * Resolve timezone from an IP address.
     *
     * @param string|null $ipAddress
     * @return string|null
     */
    public function resolveIp(?string $ipAddress): ?string;
}
