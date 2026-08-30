<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Commands;

use Illuminate\Console\Command;
use Mixudev\LaravelTimezone\Facades\Timezone;

class ClearTimezoneCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timezone:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush internal timezone caches and reset memoized runtime resolution state';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Timezone::clearCache();

        $this->info('Timezone cache and memoized context cleared successfully.');

        return self::SUCCESS;
    }
}
