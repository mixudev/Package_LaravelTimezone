<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Unit;

use Mixudev\LaravelTimezone\Support\TimezoneValidator;
use PHPUnit\Framework\TestCase;

class TimezoneValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TimezoneValidator::flushCache();
    }

    public function test_validates_standard_iana_timezones(): void
    {
        $this->assertTrue(TimezoneValidator::isValid('Asia/Jakarta'));
        $this->assertTrue(TimezoneValidator::isValid('America/New_York'));
        $this->assertTrue(TimezoneValidator::isValid('Europe/London'));
        $this->assertTrue(TimezoneValidator::isValid('Asia/Tokyo'));
        $this->assertTrue(TimezoneValidator::isValid('UTC'));
        $this->assertTrue(TimezoneValidator::isValid('Australia/Sydney'));
    }

    public function test_case_insensitive_validation(): void
    {
        $this->assertTrue(TimezoneValidator::isValid('asia/jakarta'));
        $this->assertTrue(TimezoneValidator::isValid('AMERICA/NEW_YORK'));
        $this->assertTrue(TimezoneValidator::isValid('utc'));
    }

    public function test_rejects_invalid_timezones_and_injections(): void
    {
        $this->assertFalse(TimezoneValidator::isValid(null));
        $this->assertFalse(TimezoneValidator::isValid(''));
        $this->assertFalse(TimezoneValidator::isValid('Invalid/Timezone'));
        $this->assertFalse(TimezoneValidator::isValid('Asia/NotRealCity'));
        $this->assertFalse(TimezoneValidator::isValid('<script>alert(1)</script>'));
        $this->assertFalse(TimezoneValidator::isValid('Asia/Jakarta; DROP TABLE users;'));
        $this->assertFalse(TimezoneValidator::isValid(str_repeat('A', 150)));
    }

    public function test_normalizes_valid_timezones(): void
    {
        $this->assertSame('Asia/Jakarta', TimezoneValidator::normalize('Asia/Jakarta'));
        $this->assertSame('UTC', TimezoneValidator::normalize('UTC'));
        $this->assertSame('UTC', TimezoneValidator::normalize('utc'));
        $this->assertNull(TimezoneValidator::normalize('invalid/zone'));
    }

    public function test_lists_all_iana_identifiers(): void
    {
        $all = TimezoneValidator::all();
        $this->assertIsArray($all);
        $this->assertContains('Asia/Jakarta', $all);
        $this->assertContains('UTC', $all);
    }
}
