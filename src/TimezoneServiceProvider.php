<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Mixudev\LaravelTimezone\Commands\ClearTimezoneCacheCommand;
use Mixudev\LaravelTimezone\Commands\DetectTimezoneCommand;
use Mixudev\LaravelTimezone\Commands\InstallTimezonePackage;
use Mixudev\LaravelTimezone\Middleware\DetectTimezone;
use Mixudev\LaravelTimezone\View\Components\LocalTime;

class TimezoneServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/timezone.php',
            'timezone'
        );

        // Bind TimezoneManager as a scoped service (Request-safe for Octane and PHP-FPM)
        $this->app->scoped(TimezoneManager::class, function (Container $app) {
            /** @var array<string, mixed> $config */
            $config = $app->make('config')->get('timezone', []);

            return new TimezoneManager(
                config: $config,
                container: $app
            );
        });

        $this->app->alias(TimezoneManager::class, 'timezone');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerBlade();
        $this->registerMiddleware();
        $this->registerCommands();
    }

    /**
     * Register publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/timezone.php' => $this->app->configPath('timezone.php'),
            ], 'timezone-config');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => $this->app->resourcePath('views/vendor/timezone'),
            ], 'timezone-views');

            // Publish JavaScript assets
            $this->publishes([
                __DIR__ . '/../resources/js/laravel-timezone.js' => $this->app->publicPath('vendor/timezone/laravel-timezone.js'),
            ], 'timezone-assets');
        }

        // Load package views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'timezone');
    }

    /**
     * Register Blade directives and components.
     */
    protected function registerBlade(): void
    {
        // Register <x-local-time /> component
        Blade::component('local-time', LocalTime::class);

        // Directive: @localtime($date, $format = 'datetime')
        Blade::directive('localtime', function ($expression) {
            $expression = trim($expression);
            if ($expression === '') {
                return '<?php echo \Mixudev\LaravelTimezone\Facades\Timezone::format(now()); ?>';
            }

            return "<?php echo \Mixudev\LaravelTimezone\Facades\Timezone::format({$expression}); ?>";
        });

        // Directive: @timezone
        Blade::directive('timezone', function () {
            return '<?php echo \Mixudev\LaravelTimezone\Facades\Timezone::current(); ?>';
        });
    }

    /**
     * Register middleware alias.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('timezone', DetectTimezone::class);
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallTimezonePackage::class,
                DetectTimezoneCommand::class,
                ClearTimezoneCacheCommand::class,
            ]);
        }
    }
}
