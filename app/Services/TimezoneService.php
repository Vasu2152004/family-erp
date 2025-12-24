<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

class TimezoneService
{
    public const IST_TIMEZONE = 'Asia/Kolkata';
    public const UTC_TIMEZONE = 'UTC';

    /**
     * Convert IST datetime/time to UTC.
     * 
     * @param string|Carbon $dateTime DateTime string or Carbon instance in IST
     * @return Carbon Carbon instance in UTC
     */
    public static function convertIstToUtc($dateTime): Carbon
    {
        if ($dateTime instanceof Carbon) {
            $carbon = $dateTime->copy();
            // If already in IST timezone, convert to UTC
            if ($carbon->timezone->getName() === self::IST_TIMEZONE) {
                return $carbon->utc();
            }
            // Otherwise, assume it's in IST and convert
            return $carbon->setTimezone(self::IST_TIMEZONE)->utc();
        } else {
            // Parse the datetime string as IST timezone, then convert to UTC
            $carbon = Carbon::parse($dateTime, self::IST_TIMEZONE);
            return $carbon->utc();
        }
    }

    /**
     * Convert UTC datetime/time to IST.
     * 
     * @param string|Carbon $dateTime DateTime string or Carbon instance in UTC
     * @return Carbon Carbon instance in IST
     */
    public static function convertUtcToIst($dateTime): Carbon
    {
        if ($dateTime instanceof Carbon) {
            $carbon = $dateTime->copy();
            // Ensure it's in UTC first
            if ($carbon->timezone->getName() !== self::UTC_TIMEZONE) {
                $carbon = $carbon->utc();
            }
        } else {
            // Parse the datetime string as UTC timezone
            $carbon = Carbon::parse($dateTime, self::UTC_TIMEZONE);
        }

        // Convert from UTC to IST
        return $carbon->setTimezone(self::IST_TIMEZONE);
    }

    /**
     * Parse time string as IST and return UTC Carbon instance.
     * This is used for TIME columns (HH:MM or HH:MM:SS) that are input in IST.
     * 
     * @param string $timeString Time string in format HH:MM or HH:MM:SS (IST)
     * @param string|null $date Date string (Y-m-d) or null for today
     * @return Carbon Carbon instance in UTC
     */
    public static function parseIstTime(string $timeString, ?string $date = null): Carbon
    {
        // Normalize time string to HH:MM:SS format
        if (strlen($timeString) === 5) {
            $timeString .= ':00';
        }

        $date = $date ?? Carbon::today(self::IST_TIMEZONE)->format('Y-m-d');
        $dateTimeString = "{$date} {$timeString}";

        // Parse as IST and convert to UTC
        return Carbon::parse($dateTimeString, self::IST_TIMEZONE)->utc();
    }

    /**
     * Get IST time representation from UTC datetime.
     * Returns time string in HH:MM:SS format.
     * 
     * @param Carbon|string $utcDateTime UTC datetime
     * @return string Time string in HH:MM:SS format (IST)
     */
    public static function getIstTimeFromUtc($utcDateTime): string
    {
        if ($utcDateTime instanceof Carbon) {
            $carbon = $utcDateTime->copy();
        } else {
            $carbon = Carbon::parse($utcDateTime);
        }

        return $carbon->utc()->setTimezone(self::IST_TIMEZONE)->format('H:i:s');
    }

    /**
     * Convert IST datetime string to UTC datetime string for storage.
     * Used for full datetime inputs (date + time).
     * 
     * @param string $istDateTime DateTime string in IST (e.g., "2025-01-15 14:30:00")
     * @return string DateTime string in UTC
     */
    public static function convertIstDateTimeToUtcString(string $istDateTime): string
    {
        return self::convertIstToUtc($istDateTime)->format('Y-m-d H:i:s');
    }

    /**
     * Convert IST time string (HH:MM or HH:MM:SS) to UTC time string for storage in TIME column.
     * 
     * @param string $istTime Time string in IST (HH:MM or HH:MM:SS)
     * @return string Time string in UTC (HH:MM:SS)
     */
    public static function convertIstTimeToUtcTimeString(string $istTime): string
    {
        // Normalize to HH:MM:SS
        if (strlen($istTime) === 5) {
            $istTime .= ':00';
        }

        // Use today's date for conversion, then extract just the time
        $utcDateTime = self::parseIstTime($istTime);
        return $utcDateTime->format('H:i:s');
    }

    /**
     * Convert scheduled time from IST to UTC for Laravel scheduler.
     * 
     * @param string $istTime Time in IST format (HH:MM)
     * @return string Time in UTC format (HH:MM)
     */
    public static function convertScheduledTimeIstToUtc(string $istTime): string
    {
        // Normalize to HH:MM:SS
        if (strlen($istTime) === 5) {
            $istTime .= ':00';
        }

        // Use today's date for conversion
        $utcDateTime = self::parseIstTime($istTime);
        return $utcDateTime->format('H:i');
    }
}

