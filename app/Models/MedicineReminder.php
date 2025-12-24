<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Services\TimezoneService;

class MedicineReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'family_member_id',
        'prescription_id',
        'frequency',
        'reminder_time',
        'start_date',
        'end_date',
        'days_of_week',
        'next_run_at',
        'last_sent_at',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'reminder_time' => 'datetime:H:i',
            'start_date' => 'date',
            'end_date' => 'date',
            'days_of_week' => 'array',
            'next_run_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Calculate next run time based on frequency and reminder time.
     * reminder_time is stored in UTC (as datetime, but we use the time portion).
     */
    public function calculateNextRunAt(): ?Carbon
    {
        if (!$this->reminder_time) {
            return null;
        }

        // Extract UTC time from reminder_time (stored as datetime in UTC)
        $time = $this->reminder_time;
        $now = Carbon::now(); // UTC

        // If start_date is provided and in the future, use it
        if ($this->start_date && Carbon::parse($this->start_date)->isFuture()) {
            return Carbon::parse($this->start_date)->setTimeFromTimeString($time->format('H:i:s'));
        }

        $today = $now->copy()->setTimeFromTimeString($time->format('H:i:s'));

        // If no days specified, it's daily
        if (empty($this->days_of_week)) {
            if ($today->isFuture()) {
                return $today;
            }
            return $today->addDay();
        }

        // Find next matching day
        $dayMap = [
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY,
        ];

        $targetDays = array_map(fn($day) => $dayMap[strtolower($day)] ?? null, $this->days_of_week);
        $targetDays = array_filter($targetDays);

        if (empty($targetDays)) {
            return null;
        }

        $currentDay = $now->dayOfWeek;

        // Check if today is a target day and time hasn't passed
        if (in_array($currentDay, $targetDays) && $today->isFuture()) {
            return $today;
        }

        // Find next target day
        $nextDay = $now->copy();
        for ($i = 1; $i <= 7; $i++) {
            $nextDay->addDay();
            if (in_array($nextDay->dayOfWeek, $targetDays)) {
                return $nextDay->setTimeFromTimeString($time->format('H:i:s'));
            }
        }

        return null;
    }
}
