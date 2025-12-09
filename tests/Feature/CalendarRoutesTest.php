<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CalendarRoutesTest extends TestCase
{
    public function test_calendar_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('families.calendar.index'));
        $this->assertTrue(Route::has('families.calendar.create'));
        $this->assertTrue(Route::has('families.calendar.store'));
        $this->assertTrue(Route::has('families.calendar.edit'));
        $this->assertTrue(Route::has('families.calendar.update'));
        $this->assertTrue(Route::has('families.calendar.destroy'));
    }
}

