<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Facades;

use Carbon\CarbonInterface;
use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Mixudev\LaravelTimezone\Contracts\IpTimezoneResolverInterface;
use Mixudev\LaravelTimezone\Contracts\TimezoneFormatterInterface;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Contracts\UserTimezoneProviderInterface;
use Mixudev\LaravelTimezone\DTO\ResolvedTimezone;
use Mixudev\LaravelTimezone\TimezoneManager;

/**
 * @method static string current()
 * @method static string resolve(?Request $request = null)
 * @method static ResolvedTimezone getResolvedInfo(?Request $request = null)
 * @method static string|null getResolvedSource()
 * @method static CarbonInterface convert(mixed $date, ?string $to = null, ?string $from = null)
 * @method static CarbonInterface for(mixed $date, ?string $timezone = null)
 * @method static string format(mixed $date, string $format = 'datetime', ?string $timezone = null)
 * @method static mixed in(string $timezone, Closure $callback)
 * @method static TimezoneManager setExplicit(?string $timezone)
 * @method static TimezoneManager useUserProvider(Closure|UserTimezoneProviderInterface $provider)
 * @method static TimezoneManager useIpResolver(Closure|IpTimezoneResolverInterface|string $resolver)
 * @method static bool isValid(?string $timezone)
 * @method static array<int, string> list()
 * @method static TimezoneManager addResolver(TimezoneResolverInterface $resolver, ?int $index = null)
 * @method static TimezoneManager setResolvers(array<int, TimezoneResolverInterface> $resolvers)
 * @method static array<int, TimezoneResolverInterface> getResolvers()
 * @method static TimezoneFormatterInterface getFormatter()
 * @method static TimezoneManager setFormatter(TimezoneFormatterInterface $formatter)
 * @method static void flush()
 * @method static void clearCache()
 *
 * @see \Mixudev\LaravelTimezone\TimezoneManager
 */
class Timezone extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return TimezoneManager::class;
    }
}
