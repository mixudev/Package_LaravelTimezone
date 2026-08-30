<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\Tests\TestCase;

class BladeDirectivesAndComponentTest extends TestCase
{
    public function test_localtime_directive_renders_formatted_date(): void
    {
        Timezone::setExplicit('Asia/Jakarta');

        $date = CarbonImmutable::create(2026, 8, 30, 10, 0, 0, 'UTC');

        $rendered = Blade::render('@localtime($date, "datetime")', ['date' => $date]);
        $this->assertSame('2026-08-30 17:00:00', trim($rendered));

        $renderedDate = Blade::render('@localtime($date, "date")', ['date' => $date]);
        $this->assertSame('2026-08-30', trim($renderedDate));
    }

    public function test_timezone_directive_renders_current_identifier(): void
    {
        Timezone::setExplicit('Asia/Tokyo');

        $rendered = Blade::render('@timezone');
        $this->assertSame('Asia/Tokyo', trim($rendered));
    }

    public function test_local_time_component_renders_semantic_html_with_fallback(): void
    {
        Timezone::setExplicit('Asia/Jakarta');

        $date = CarbonImmutable::create(2026, 8, 30, 10, 0, 0, 'UTC');

        $view = $this->blade('<x-local-time :date="$date" format="datetime" class="font-bold" />', ['date' => $date]);

        $view->assertSee('data-local-time', false);
        $view->assertSee('datetime="2026-08-30T10:00:00+00:00"', false);
        $view->assertSee('data-format="datetime"', false);
        $view->assertSee('2026-08-30 17:00:00');
        $view->assertSee('class="local-time font-bold"', false);
    }
}
