<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\View\Components;

use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\Support\DateNormalizer;

class LocalTime extends Component
{
    public string $isoUtc;
    public string $serverFormatted;
    public string $format;
    public ?string $timezone;

    /**
     * Create a new component instance.
     *
     * @param mixed $date
     * @param string $format
     * @param string|null $timezone
     */
    public function __construct(
        mixed $date = null,
        string $format = 'datetime',
        ?string $timezone = null
    ) {
        $this->format = $format;
        $this->timezone = $timezone;

        $carbonUtc = DateNormalizer::convert($date, 'UTC');
        $this->isoUtc = $carbonUtc->toIso8601String();

        $targetTz = $timezone ?? Timezone::current();
        $this->serverFormatted = Timezone::format($carbonUtc, $format, $targetTz);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('timezone::components.local-time');
    }
}
