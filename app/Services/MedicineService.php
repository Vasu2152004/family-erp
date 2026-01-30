<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Medicine;
use App\Models\MedicineExpiryReminder;
use App\Models\MedicineIntakeReminder;
use App\Services\TimezoneService;
use Carbon\CarbonImmutable;
use App\Support\BlobPathNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MedicineService
{
    /**
     * Create a medicine with prescription PDF.
     */
    public function createMedicine(array $data, int $tenantId, int $familyId, int $userId, ?UploadedFile $file = null): Medicine
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $userId, $file) {
            $attachmentData = $file ? $this->storePrescriptionFile($file, $tenantId, $familyId) : [];

            $medicine = Medicine::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'family_member_id' => $data['family_member_id'] ?? null,
                'prescription_id' => $data['prescription_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'manufacturer' => $data['manufacturer'] ?? null,
                'batch_number' => $data['batch_number'] ?? null,
                'quantity' => $data['quantity'],
                'unit' => $data['unit'] ?? 'units',
                'min_stock_level' => $data['min_stock_level'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? null,
                'purchase_price' => $data['purchase_price'] ?? null,
                'prescription_file_path' => $attachmentData['file_path'] ?? null,
                'prescription_original_name' => $attachmentData['original_name'] ?? null,
                'prescription_mime_type' => $attachmentData['mime_type'] ?? null,
                'prescription_file_size' => $attachmentData['file_size'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Sync expiry reminders if expiry_date is set
            if ($medicine->expiry_date) {
                $this->syncExpiryReminders($medicine);
            }

            return $medicine;
        });
    }

    /**
     * Update a medicine.
     */
    public function updateMedicine(Medicine $medicine, array $data, int $userId, ?UploadedFile $file = null): Medicine
    {
        return DB::transaction(function () use ($medicine, $data, $userId, $file) {
            $attachmentData = [];
            if ($file) {
                // Delete old file if exists
                $path = BlobPathNormalizer::normalize($medicine->prescription_file_path);
                if ($path !== null && $path !== '') {
                    try {
                        Storage::disk('vercel_blob')->delete($path);
                    } catch (\Throwable $e) {
                        Log::warning('Blob delete failed in updateMedicine', ['medicine_id' => $medicine->id, 'message' => $e->getMessage()]);
                    }
                }
                $attachmentData = $this->storePrescriptionFile($file, $medicine->tenant_id, $medicine->family_id);
            }

            $medicine->update([
                'family_member_id' => $data['family_member_id'] ?? $medicine->family_member_id,
                'prescription_id' => $data['prescription_id'] ?? $medicine->prescription_id,
                'name' => $data['name'] ?? $medicine->name,
                'description' => $data['description'] ?? $medicine->description,
                'manufacturer' => $data['manufacturer'] ?? $medicine->manufacturer,
                'batch_number' => $data['batch_number'] ?? $medicine->batch_number,
                'quantity' => $data['quantity'] ?? $medicine->quantity,
                'unit' => $data['unit'] ?? $medicine->unit,
                'min_stock_level' => $data['min_stock_level'] ?? $medicine->min_stock_level,
                'expiry_date' => $data['expiry_date'] ?? $medicine->expiry_date,
                'purchase_date' => $data['purchase_date'] ?? $medicine->purchase_date,
                'purchase_price' => $data['purchase_price'] ?? $medicine->purchase_price,
                'prescription_file_path' => $attachmentData['file_path'] ?? $medicine->prescription_file_path,
                'prescription_original_name' => $attachmentData['original_name'] ?? $medicine->prescription_original_name,
                'prescription_mime_type' => $attachmentData['mime_type'] ?? $medicine->prescription_mime_type,
                'prescription_file_size' => $attachmentData['file_size'] ?? $medicine->prescription_file_size,
                'updated_by' => $userId,
            ]);

            // Sync expiry reminders if expiry_date changed
            if (isset($data['expiry_date'])) {
                if ($medicine->expiry_date) {
                    $this->syncExpiryReminders($medicine);
                } else {
                    $medicine->expiryReminders()->delete();
                }
            }

            return $medicine->fresh();
        });
    }

    /**
     * Delete a medicine.
     */
    public function deleteMedicine(Medicine $medicine): void
    {
        DB::transaction(function () use ($medicine) {
            // Delete prescription file if exists
            $path = BlobPathNormalizer::normalize($medicine->prescription_file_path);
            if ($path !== null && $path !== '') {
                try {
                    Storage::disk('vercel_blob')->delete($path);
                } catch (\Throwable $e) {
                    Log::warning('Blob delete failed in deleteMedicine', ['medicine_id' => $medicine->id, 'message' => $e->getMessage()]);
                }
            }

            // Delete all reminders
            $medicine->expiryReminders()->delete();
            $medicine->intakeReminders()->delete();

            $medicine->delete();
        });
    }

    /**
     * Sync expiry reminders for medicine (30 days, 7 days, expiry date).
     */
    public function syncExpiryReminders(Medicine $medicine): void
    {
        if (!$medicine->expiry_date) {
            $medicine->expiryReminders()->delete();
            return;
        }

        $expiry = CarbonImmutable::parse($medicine->expiry_date);
        $dates = collect([
            $expiry->subDays(30),
            $expiry->subDays(7),
            $expiry,
        ])->filter(fn (CarbonImmutable $date) => $date->isToday() || $date->isFuture())
            ->map(fn (CarbonImmutable $date) => $date->toDateString());

        foreach ($dates as $date) {
            MedicineExpiryReminder::updateOrCreate(
                [
                    'medicine_id' => $medicine->id,
                    'remind_at' => $date,
                ],
                [
                    'tenant_id' => $medicine->tenant_id,
                    'family_id' => $medicine->family_id,
                ]
            );
        }

        $medicine->expiryReminders()->whereNotIn('remind_at', $dates)->delete();
    }

    /**
     * Create an intake reminder.
     */
    public function createIntakeReminder(array $data, int $tenantId, int $familyId, Medicine $medicine, int $userId): MedicineIntakeReminder
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $medicine, $userId) {
            // Convert reminder_time from IST to UTC for storage
            // reminder_time is a TIME column - input is in IST (HH:MM or HH:MM:SS)
            $reminderTime = isset($data['reminder_time']) 
                ? TimezoneService::convertIstTimeToUtcTimeString($data['reminder_time'])
                : null;

            $reminder = MedicineIntakeReminder::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'medicine_id' => $medicine->id,
                'family_member_id' => $data['family_member_id'] ?? $medicine->family_member_id,
                'reminder_time' => $reminderTime,
                'frequency' => $data['frequency'],
                'days_of_week' => $data['days_of_week'] ?? null,
                'selected_dates' => $data['selected_dates'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Calculate and set next_run_at
            $nextRunAt = $reminder->calculateNextRunAt();
            $reminder->update(['next_run_at' => $nextRunAt]);

            return $reminder->fresh();
        });
    }

    /**
     * Update an intake reminder.
     */
    public function updateIntakeReminder(MedicineIntakeReminder $reminder, array $data, int $userId): MedicineIntakeReminder
    {
        return DB::transaction(function () use ($reminder, $data, $userId) {
            // Convert reminder_time from IST to UTC if provided
            $updateData = $data;
            if (isset($data['reminder_time'])) {
                $updateData['reminder_time'] = TimezoneService::convertIstTimeToUtcTimeString($data['reminder_time']);
            }

            $reminder->update([
                'family_member_id' => $updateData['family_member_id'] ?? $reminder->family_member_id,
                'reminder_time' => $updateData['reminder_time'] ?? $reminder->reminder_time,
                'frequency' => $updateData['frequency'] ?? $reminder->frequency,
                'days_of_week' => $updateData['days_of_week'] ?? $reminder->days_of_week,
                'selected_dates' => $updateData['selected_dates'] ?? $reminder->selected_dates,
                'start_date' => $updateData['start_date'] ?? $reminder->start_date,
                'end_date' => $updateData['end_date'] ?? $reminder->end_date,
                'status' => $updateData['status'] ?? $reminder->status,
                'updated_by' => $userId,
            ]);

            // Recalculate next_run_at if relevant fields changed
            if (isset($data['reminder_time']) || isset($data['frequency']) || isset($data['days_of_week']) || isset($data['selected_dates']) || isset($data['start_date'])) {
                $nextRunAt = $reminder->calculateNextRunAt();
                $reminder->update(['next_run_at' => $nextRunAt]);
            }

            return $reminder->fresh();
        });
    }

    /**
     * Delete an intake reminder.
     */
    public function deleteIntakeReminder(MedicineIntakeReminder $reminder): void
    {
        $reminder->delete();
    }

    /**
     * Store prescription file (reusing health module pattern).
     */
    private function storePrescriptionFile(UploadedFile $file, int $tenantId, int $familyId): array
    {
        $ext = $file->getClientOriginalExtension();
        $hashed = Str::uuid()->toString();
        $directory = "medicines/tenant-{$tenantId}/family-{$familyId}";
        $path = "{$directory}/{$hashed}.{$ext}";

        Storage::disk('vercel_blob')->putFileAs(dirname($path), $file, basename($path));

        return [
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ];
    }
}



