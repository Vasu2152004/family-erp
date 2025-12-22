<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class MedicineIntakeReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'family_id',
        'medicine_id',
        'family_member_id',
        'reminder_time',
        'frequency',
        'days_of_week',
        'selected_dates',
        'start_date',
        'end_date',
        'next_run_at',
        'last_sent_at',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            // reminder_time is a TIME column - don't cast it, handle as string
            'start_date' => 'date',
            'end_date' => 'date',
            'days_of_week' => 'array',
            'selected_dates' => 'array',
            'next_run_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    /**
     * Accessor to get reminder_time as Carbon instance for calculations
     */
    public function getReminderTimeAsCarbon(): ?Carbon
    {
        if (!$this->attributes['reminder_time'] ?? null) {
            return null;
        }
        // Parse time string (HH:MM:SS) and set to today's date
        return Carbon::parse('2000-01-01 ' . $this->attributes['reminder_time']);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
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
     * Calculate next run time based on frequency
     */
    public function calculateNextRunAt(): ?Carbon
    {
        if (!$this->attributes['reminder_time'] ?? null) {
            return null;
        }

        // Parse time string (HH:MM:SS) and get just the time portion
        $timeStr = $this->attributes['reminder_time'];
        $time = Carbon::parse('2000-01-01 ' . $timeStr);
        $now = Carbon::now();

        // If start_date is provided and in the future, use it
        if ($this->start_date && Carbon::parse($this->start_date)->isFuture()) {
            return Carbon::parse($this->start_date)->setTimeFromTimeString($time->format('H:i:s'));
        }

        $today = $now->copy()->setTimeFromTimeString($time->format('H:i:s'));

        // Daily frequency
        if ($this->frequency === 'daily') {
            if ($today->isFuture()) {
                return $today;
            }
            return $today->addDay();
        }

        // Weekly frequency
        if ($this->frequency === 'weekly' && !empty($this->days_of_week)) {
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
        }

        // Custom dates frequency
        if ($this->frequency === 'custom' && !empty($this->selected_dates)) {
            $dates = [];
            foreach ($this->selected_dates as $dateStr) {
                try {
                    $date = Carbon::parse($dateStr)->setTimeFromTimeString($time->format('H:i:s'));
                    if ($date->isFuture()) {
                        $dates[] = $date;
                    }
                } catch (\Exception $e) {
                    // Skip invalid dates
                    continue;
                }
            }
            
            if (empty($dates)) {
                return null;
            }
            
            usort($dates, fn($a, $b) => $a->timestamp <=> $b->timestamp);
            return $dates[0];
        }

        return null;
    }
}
