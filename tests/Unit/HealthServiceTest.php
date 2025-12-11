<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\HealthService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class HealthServiceTest extends TestCase
{
    private HealthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HealthService();
    }

    public function test_daily_reminder_rolls_to_next_day_when_time_passed(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-11 10:00:00'));

        $next = $this->service->calculateNextRunAt('daily', '08:00', '2025-12-11', null);

        $this->assertTrue($next->isSameDay(Carbon::parse('2025-12-12')));
        $this->assertSame('08:00:00', $next->format('H:i:s'));
    }

    public function test_weekly_reminder_picks_next_available_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-10 09:00:00')); // Wednesday

        $next = $this->service->calculateNextRunAt('weekly', '07:30', '2025-12-09', null, ['thu', 'sat']);

        $this->assertTrue($next->isSameDay(Carbon::parse('2025-12-11'))); // Thursday
        $this->assertSame('07:30:00', $next->format('H:i:s'));
    }

    public function test_once_reminder_returns_null_when_past(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-11 12:00:00'));

        $next = $this->service->calculateNextRunAt('once', '08:00', '2025-12-10', null);

        $this->assertNull($next);
    }
}

