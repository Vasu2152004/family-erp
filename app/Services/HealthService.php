<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\DoctorVisit;
use App\Models\Prescription;
use App\Models\MedicineReminder;
use App\Models\Family;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HealthService
{
    /**
     * Create a medical record.
     */
    public function createMedicalRecord(array $data, int $tenantId, int $familyId, int $userId): MedicalRecord
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $userId) {
            return MedicalRecord::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'family_member_id' => $data['family_member_id'],
                'title' => $data['title'],
                'record_type' => $data['record_type'],
                'category' => $data['category'] ?? null,
                'doctor_name' => $data['doctor_name'] ?? null,
                'primary_condition' => $data['primary_condition'] ?? null,
                'severity' => $data['severity'] ?? null,
                'symptoms' => $data['symptoms'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'treatment_plan' => $data['treatment_plan'] ?? null,
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'recorded_at' => $data['recorded_at'] ?? null,
                'summary' => $data['summary'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Update a medical record.
     */
    public function updateMedicalRecord(MedicalRecord $record, array $data, int $userId): MedicalRecord
    {
        return DB::transaction(function () use ($record, $data, $userId) {
            $record->update([
                'family_member_id' => $data['family_member_id'] ?? $record->family_member_id,
                'title' => $data['title'] ?? $record->title,
                'record_type' => $data['record_type'] ?? $record->record_type,
                'category' => $data['category'] ?? $record->category,
                'doctor_name' => $data['doctor_name'] ?? $record->doctor_name,
                'primary_condition' => $data['primary_condition'] ?? $record->primary_condition,
                'severity' => $data['severity'] ?? $record->severity,
                'symptoms' => $data['symptoms'] ?? $record->symptoms,
                'diagnosis' => $data['diagnosis'] ?? $record->diagnosis,
                'treatment_plan' => $data['treatment_plan'] ?? $record->treatment_plan,
                'follow_up_at' => $data['follow_up_at'] ?? $record->follow_up_at,
                'recorded_at' => $data['recorded_at'] ?? $record->recorded_at,
                'summary' => $data['summary'] ?? $record->summary,
                'notes' => $data['notes'] ?? $record->notes,
                'updated_by' => $userId,
            ]);

            return $record->fresh();
        });
    }

    /**
     * Create a doctor visit.
     */
    public function createDoctorVisit(array $data, int $tenantId, int $familyId, int $userId): DoctorVisit
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $userId) {
            // If no medical_record_id provided and this is a new visit, create a medical record
            $medicalRecordId = $data['medical_record_id'] ?? null;
            
            if (!$medicalRecordId && isset($data['family_member_id'])) {
                $medicalRecord = $this->createMedicalRecord([
                    'family_member_id' => $data['family_member_id'],
                    'title' => 'Doctor Visit - ' . ($data['doctor_name'] ?? 'Unknown Doctor'),
                    'record_type' => 'general',
                    'category' => 'Doctor Visit',
                ], $tenantId, $familyId, $userId);
                $medicalRecordId = $medicalRecord->id;
            }

            return DoctorVisit::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'family_member_id' => $data['family_member_id'],
                'medical_record_id' => $medicalRecordId,
                'visit_date' => $data['visit_date'],
                'visit_time' => $data['visit_time'] ?? null,
                'status' => $data['status'] ?? 'scheduled',
                'doctor_name' => $data['doctor_name'] ?? null,
                'clinic_name' => $data['clinic_name'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'visit_type' => $data['visit_type'],
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'examination_findings' => $data['examination_findings'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'treatment_given' => $data['treatment_given'] ?? null,
                'notes' => $data['notes'] ?? null,
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'next_visit_date' => $data['next_visit_date'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Update a doctor visit.
     */
    public function updateDoctorVisit(DoctorVisit $visit, array $data, int $userId): DoctorVisit
    {
        return DB::transaction(function () use ($visit, $data, $userId) {
            $visit->update([
                'family_member_id' => $data['family_member_id'] ?? $visit->family_member_id,
                'medical_record_id' => $data['medical_record_id'] ?? $visit->medical_record_id,
                'visit_date' => $data['visit_date'] ?? $visit->visit_date,
                'visit_time' => $data['visit_time'] ?? $visit->visit_time,
                'status' => $data['status'] ?? $visit->status,
                'doctor_name' => $data['doctor_name'] ?? $visit->doctor_name,
                'clinic_name' => $data['clinic_name'] ?? $visit->clinic_name,
                'specialization' => $data['specialization'] ?? $visit->specialization,
                'visit_type' => $data['visit_type'] ?? $visit->visit_type,
                'chief_complaint' => $data['chief_complaint'] ?? $visit->chief_complaint,
                'examination_findings' => $data['examination_findings'] ?? $visit->examination_findings,
                'diagnosis' => $data['diagnosis'] ?? $visit->diagnosis,
                'treatment_given' => $data['treatment_given'] ?? $visit->treatment_given,
                'notes' => $data['notes'] ?? $visit->notes,
                'follow_up_at' => $data['follow_up_at'] ?? $visit->follow_up_at,
                'next_visit_date' => $data['next_visit_date'] ?? $visit->next_visit_date,
                'updated_by' => $userId,
            ]);

            return $visit->fresh();
        });
    }

    /**
     * Create a prescription.
     */
    public function createPrescription(array $data, int $tenantId, int $familyId, DoctorVisit $visit, int $userId, ?UploadedFile $file = null): Prescription
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $visit, $userId, $file) {
            $attachmentData = $file ? $this->storeAttachment($file, $tenantId, $familyId) : [];

            return Prescription::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'family_member_id' => $visit->family_member_id,
                'doctor_visit_id' => $visit->id,
                'medication_name' => $data['medication_name'],
                'dosage' => $data['dosage'] ?? null,
                'frequency' => $data['frequency'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? 'active',
                'instructions' => $data['instructions'] ?? null,
                'file_path' => $attachmentData['file_path'] ?? null,
                'original_name' => $attachmentData['original_name'] ?? null,
                'mime_type' => $attachmentData['mime_type'] ?? null,
                'file_size' => $attachmentData['file_size'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Update a prescription.
     */
    public function updatePrescription(Prescription $prescription, array $data, int $userId, ?UploadedFile $file = null): Prescription
    {
        return DB::transaction(function () use ($prescription, $data, $userId, $file) {
            // Handle file upload if provided
            if ($file) {
                // Delete old file if exists
                if ($prescription->file_path) {
                    Storage::disk('local')->delete($prescription->file_path);
                }
                $attachmentData = $this->storeAttachment($file, $prescription->tenant_id, $prescription->family_id);
            } else {
                $attachmentData = [];
            }

            $prescription->update([
                'medication_name' => $data['medication_name'] ?? $prescription->medication_name,
                'dosage' => $data['dosage'] ?? $prescription->dosage,
                'frequency' => $data['frequency'] ?? $prescription->frequency,
                'start_date' => $data['start_date'] ?? $prescription->start_date,
                'end_date' => $data['end_date'] ?? $prescription->end_date,
                'status' => $data['status'] ?? $prescription->status,
                'instructions' => $data['instructions'] ?? $prescription->instructions,
                'file_path' => $attachmentData['file_path'] ?? $prescription->file_path,
                'original_name' => $attachmentData['original_name'] ?? $prescription->original_name,
                'mime_type' => $attachmentData['mime_type'] ?? $prescription->mime_type,
                'file_size' => $attachmentData['file_size'] ?? $prescription->file_size,
                'updated_by' => $userId,
            ]);

            return $prescription->fresh();
        });
    }

    /**
     * Create a medicine reminder.
     */
    public function createMedicineReminder(array $data, int $tenantId, int $familyId, Prescription $prescription, int $userId): MedicineReminder
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $prescription, $userId) {
            $reminderTime = $data['reminder_time'] ?? null;
            $nextRunAt = $reminderTime ? $this->calculateNextRunAt($reminderTime, $data['days_of_week'] ?? null, $data['start_date'] ?? null) : null;

            return MedicineReminder::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'family_member_id' => $prescription->family_member_id,
                'prescription_id' => $prescription->id,
                'frequency' => $data['frequency'],
                'reminder_time' => $reminderTime,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'days_of_week' => $data['days_of_week'] ?? null,
                'next_run_at' => $nextRunAt,
                'status' => $data['status'] ?? 'active',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Update a medicine reminder.
     */
    public function updateMedicineReminder(MedicineReminder $reminder, array $data, int $userId): MedicineReminder
    {
        return DB::transaction(function () use ($reminder, $data, $userId) {
            $reminderTime = $data['reminder_time'] ?? ($reminder->reminder_time ? $reminder->reminder_time->format('H:i') : null);
            $daysOfWeek = $data['days_of_week'] ?? $reminder->days_of_week;
            $startDate = $data['start_date'] ?? ($reminder->start_date ? $reminder->start_date->format('Y-m-d') : null);
            $nextRunAt = $reminderTime ? $this->calculateNextRunAt($reminderTime, $daysOfWeek, $startDate) : null;

            $reminder->update([
                'frequency' => $data['frequency'] ?? $reminder->frequency,
                'reminder_time' => $reminderTime,
                'start_date' => $data['start_date'] ?? $reminder->start_date,
                'end_date' => $data['end_date'] ?? $reminder->end_date,
                'days_of_week' => $daysOfWeek,
                'next_run_at' => $nextRunAt,
                'status' => $data['status'] ?? $reminder->status,
                'updated_by' => $userId,
            ]);

            return $reminder->fresh();
        });
    }

    /**
     * Store prescription attachment file.
     */
    private function storeAttachment(UploadedFile $file, int $tenantId, int $familyId): array
    {
        $ext = $file->getClientOriginalExtension();
        $hashed = Str::uuid()->toString();
        $directory = "prescriptions/tenant-{$tenantId}/family-{$familyId}";
        $path = "{$directory}/{$hashed}.{$ext}";

        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

        return [
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    /**
     * Calculate next run time for medicine reminder.
     */
    private function calculateNextRunAt(?string $reminderTime, ?array $daysOfWeek, ?string $startDate = null): ?Carbon
    {
        if (!$reminderTime) {
            return null;
        }

        $time = Carbon::parse($reminderTime);
        $now = Carbon::now();
        
        // If start_date is provided and in the future, use it
        if ($startDate) {
            $start = Carbon::parse($startDate);
            if ($start->isFuture()) {
                return $start->setTimeFromTimeString($time->format('H:i:s'));
            }
        }

        $today = $now->copy()->setTimeFromTimeString($time->format('H:i:s'));

        // If no days specified, it's daily
        if (empty($daysOfWeek)) {
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

        $targetDays = array_map(fn($day) => $dayMap[strtolower($day)], $daysOfWeek);
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
