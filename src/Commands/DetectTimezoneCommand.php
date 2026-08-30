<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Commands;

use Illuminate\Console\Command;
use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;

class DetectTimezoneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timezone:detect
                            {--tz= : Test if a specific timezone identifier is valid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display diagnostic information about the resolved timezone';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $testTz = $this->option('tz');

        if (is_string($testTz) && $testTz !== '') {
            $isValid = TimezoneValidator::isValid($testTz);
            $normalized = TimezoneValidator::normalize($testTz);

            $this->info("Checking timezone identifier: [{$testTz}]");
            $this->line("Valid IANA timezone: " . ($isValid ? '<fg=green>Yes</>' : '<fg=red>No</>'));
            if ($isValid) {
                $this->line("Canonical name: <fg=cyan>{$normalized}</>");
                $this->line("Current local time: <fg=yellow>" . Timezone::format(now(), 'datetime', $normalized) . "</>");
            }
            return self::SUCCESS;
        }

        $info = Timezone::getResolvedInfo();
        $currentTime = Timezone::format(now(), 'datetime');
        $ianaCount = count(TimezoneValidator::all());

        $this->components->info('Laravel Timezone Diagnostic');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Resolved Timezone', "<fg=green>{$info->timezone}</>"],
                ['Resolution Source', "<fg=cyan>{$info->source}</>"],
                ['Is Fallback Active', $info->isFallback ? '<fg=yellow>Yes</>' : '<fg=green>No</>'],
                ['Application Default (app.timezone)', (string) config('app.timezone', 'UTC')],
                ['Package Default (timezone.default)', (string) config('timezone.default', 'UTC')],
                ['System Time (UTC)', gmdate('Y-m-d H:i:s T')],
                ['Formatted Local Time', "<fg=yellow>{$currentTime}</>"],
                ['Total Available IANA Timezones', (string) $ianaCount],
            ]
        );

        return self::SUCCESS;
    }
}
