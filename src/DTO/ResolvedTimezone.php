<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\DTO;

readonly class ResolvedTimezone
{
    public function __construct(
        public string $timezone,
        public string $source,
        public bool $isFallback = false
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'timezone' => $this->timezone,
            'source' => $this->source,
            'is_fallback' => $this->isFallback,
        ];
    }
}
