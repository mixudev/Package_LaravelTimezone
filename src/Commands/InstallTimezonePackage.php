<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Commands;

use Illuminate\Console\Command;

class InstallTimezonePackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timezone:install
                            {--config : Publish only the configuration file}
                            {--views : Publish only the Blade view components}
                            {--assets : Publish only the JavaScript client asset}
                            {--force : Overwrite existing published files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and publish mixudev/laravel-timezone resources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Laravel Timezone package...');

        $publishAll = !$this->option('config') && !$this->option('views') && !$this->option('assets');
        $force = (bool) $this->option('force');

        if ($publishAll || $this->option('config')) {
            $this->call('vendor:publish', [
                '--tag' => 'timezone-config',
                '--force' => $force,
            ]);
            $this->info('Published configuration [config/timezone.php].');
        }

        if ($publishAll || $this->option('views')) {
            $this->call('vendor:publish', [
                '--tag' => 'timezone-views',
                '--force' => $force,
            ]);
            $this->info('Published Blade views [resources/views/vendor/timezone].');
        }

        if ($publishAll || $this->option('assets')) {
            $this->call('vendor:publish', [
                '--tag' => 'timezone-assets',
                '--force' => $force,
            ]);
            $this->info('Published JavaScript assets [public/vendor/timezone/laravel-timezone.js].');
        }

        $this->newLine();
        $this->info('Laravel Timezone package installed successfully!');

        return self::SUCCESS;
    }
}
