<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Contracts;

interface UserTimezoneProviderInterface
{
    /**
     * Retrieve timezone from the given user entity.
     *
     * @param mixed $user
     * @return string|null
     */
    public function getTimezone(mixed $user): ?string;
}
