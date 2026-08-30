<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Support;

use Carbon\CarbonInterface;
use Mixudev\LaravelTimezone\Contracts\TimezoneFormatterInterface;

class TimezoneFormatter implements TimezoneFormatterInterface
{
    /**
     * Preset formats.
     *
     * @var array<string, string>
     */
    protected array $presets;

    /**
     * @param array<string, string> $presets
     */
    public function __construct(array $presets = [])
    {
        $this->presets = array_merge([
            'datetime' => 'Y-m-d H:i:s',
            'date' => 'Y-m-d',
            'time' => 'H:i:s',
            'human' => 'M j, Y g:i A',
        ], $presets);
    }

    /**
     * Set or override a preset format.
     */
    public function setPreset(string $name, string $format): self
    {
        $this->presets[$name] = $format;
        return $this;
    }

    /**
     * Format a carbon/datetime instance in the specified timezone and format.
     */
    public function format(CarbonInterface $date, string $format, string $timezone): string
    {
        // Ensure date is in the specified timezone
        $localized = $date->getTimezone()->getName() === $timezone
            ? $date
            : $date->setTimezone($timezone);

        $lowerFormat = strtolower($format);

        if ($lowerFormat === 'relative') {
            return $localized->diffForHumans();
        }

        if ($lowerFormat === 'iso' || $lowerFormat === 'iso8601') {
            return $localized->toIso8601String();
        }

        if ($lowerFormat === 'timestamp') {
            return (string) $localized->getTimestamp();
        }

        // Match custom or configured preset
        $pattern = $this->presets[$lowerFormat] ?? $format;

        return $localized->format($pattern);
    }
}
