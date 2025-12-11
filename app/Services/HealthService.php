<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DoctorVisit;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\MedicalRecord;
use App\Models\MedicineReminder;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class HealthService
{
    public function createMedicalRecord(array $data, Family $family, int $tenantId, int $userId): MedicalRecord
    {
        return DB::transaction(function () use ($data, $family, $tenantId, $userId) {
            $member = $this->assertMemberBelongsToFamily(
                (int) $data['family_member_id'],
                $family->id,
                $tenantId
            );

            return MedicalRecord::create([
                'tenant_id' => $tenantId,
                'family_id' => $family->id,
                'family_member_id' => $member->id,
                'title' => $data['title'],
                'record_type' => $data['record_type'],
                'doctor_name' => $data['doctor_name'] ?? null,
                'recorded_at' => $data['recorded_at'] ?? null,
                'summary' => $data['summary'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    public function updateMedicalRecord(MedicalRecord $record, array $data, int $userId): MedicalRecord
    {
        return DB::transaction(function () use ($record, $data, $userId) {
            $record->update([
                'title' => $data['title'],
                'record_type' => $data['record_type'],
                'doctor_name' => $data['doctor_name'] ?? null,
                'recorded_at' => $data['recorded_at'] ?? null,
                'summary' => $data['summary'] ?? null,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $userId,
            ]);

            return $record->fresh();
        });
    }

    public function createDoctorVisit(array $data, Family $family, int $tenantId, int $userId): DoctorVisit
    {
        return DB::transaction(function () use ($data, $family, $tenantId, $userId) {
            $member = $this->assertMemberBelongsToFamily(
                (int) $data['family_member_id'],
                $family->id,
                $tenantId
            );

            $medicalRecordId = $data['medical_record_id'] ?? null;
            if ($medicalRecordId) {
                $this->assertMedicalRecordMatchesFamily((int) $medicalRecordId, $family->id, $member->id);
            } else {
                $record = MedicalRecord::create([
                    'tenant_id' => $tenantId,
                    'family_id' => $family->id,
                    'family_member_id' => $member->id,
                    'title' => $data['doctor_name'] ? ('Visit - ' . $data['doctor_name']) : 'Doctor Visit',
                    'record_type' => 'general',
                    'doctor_name' => $data['doctor_name'] ?? null,
                    'recorded_at' => $data['visit_date'] ?? now()->toDateString(),
                    'summary' => $data['reason'] ?? null,
                    'primary_condition' => $data['reason'] ?? null,
                    'severity' => null,
                    'symptoms' => null,
                    'diagnosis' => $data['diagnosis'] ?? null,
                    'treatment_plan' => null,
                    'follow_up_at' => $data['follow_up_at'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                $medicalRecordId = $record->id;
            }

            return DoctorVisit::create([
                'tenant_id' => $tenantId,
                'family_id' => $family->id,
                'family_member_id' => $member->id,
                'medical_record_id' => $medicalRecordId,
                'visit_date' => $data['visit_date'],
                'status' => $data['status'],
                'doctor_name' => $data['doctor_name'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'clinic' => $data['clinic'] ?? null,
                'reason' => $data['reason'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    public function updateDoctorVisit(DoctorVisit $visit, array $data, int $userId): DoctorVisit
    {
        return DB::transaction(function () use ($visit, $data, $userId) {
            if (isset($data['medical_record_id']) && $data['medical_record_id']) {
                $this->assertMedicalRecordMatchesFamily((int) $data['medical_record_id'], $visit->family_id, $visit->family_member_id);
            }

            $visit->update([
                'medical_record_id' => $data['medical_record_id'] ?? null,
                'visit_date' => $data['visit_date'],
                'status' => $data['status'],
                'doctor_name' => $data['doctor_name'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'clinic' => $data['clinic'] ?? null,
                'reason' => $data['reason'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $userId,
            ]);

            return $visit->fresh(['medicalRecord', 'prescriptions']);
        });
    }

    public function createPrescription(DoctorVisit $visit, array $data, int $tenantId, int $userId): Prescription
    {
        return DB::transaction(function () use ($visit, $data, $tenantId, $userId) {
            [$filePath, $originalName, $mime, $size] = $this->storeAttachment($data['attachment'] ?? null);

            return Prescription::create([
                'tenant_id' => $tenantId,
                'family_id' => $visit->family_id,
                'family_member_id' => $visit->family_member_id,
                'doctor_visit_id' => $visit->id,
                'medication_name' => $data['medication_name'],
                'dosage' => $data['dosage'] ?? null,
                'frequency' => $data['frequency'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'],
                'instructions' => $data['instructions'] ?? null,
                'file_path' => $filePath,
                'original_name' => $originalName,
                'mime_type' => $mime,
                'file_size' => $size,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    public function updatePrescription(Prescription $prescription, array $data, int $userId): Prescription
    {
        return DB::transaction(function () use ($prescription, $data, $userId) {
            [$filePath, $originalName, $mime, $size] = $this->storeAttachment($data['attachment'] ?? null, $prescription->file_path);

            $prescription->update([
                'medication_name' => $data['medication_name'],
                'dosage' => $data['dosage'] ?? null,
                'frequency' => $data['frequency'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'],
                'instructions' => $data['instructions'] ?? null,
                'file_path' => $filePath ?? $prescription->file_path,
                'original_name' => $originalName ?? $prescription->original_name,
                'mime_type' => $mime ?? $prescription->mime_type,
                'file_size' => $size ?? $prescription->file_size,
                'updated_by' => $userId,
            ]);

            return $prescription->fresh('reminders');
        });
    }

    public function createOrUpdateReminder(Prescription $prescription, array $data, int $tenantId, int $userId, ?MedicineReminder $reminder = null): MedicineReminder
    {
        $nextRunAt = $this->calculateNextRunAt(
            $data['frequency'],
            $data['reminder_time'],
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['days_of_week'] ?? []
        );

        $payload = [
            'tenant_id' => $tenantId,
            'family_id' => $prescription->family_id,
            'family_member_id' => $prescription->family_member_id,
            'prescription_id' => $prescription->id,
            'frequency' => $data['frequency'],
            'reminder_time' => $data['reminder_time'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'days_of_week' => $data['days_of_week'] ?? null,
            'status' => $nextRunAt ? $data['status'] : 'completed',
            'next_run_at' => $nextRunAt,
            'updated_by' => $userId,
        ];

        if ($reminder) {
            $reminder->update($payload);
            return $reminder->fresh();
        }

        $payload['created_by'] = $userId;

        return MedicineReminder::create($payload);
    }

    public function calculateNextRunAt(string $frequency, string $reminderTime, string $startDate, ?string $endDate, array $daysOfWeek = []): ?Carbon
    {
        $start = Carbon::parse($startDate)->setTimeFromTimeString($reminderTime);
        $now = now();

        if ($endDate && $start->greaterThan(Carbon::parse($endDate))) {
            return null;
        }

        if ($frequency === 'once') {
            return $start->isFuture() ? $start : null;
        }

        if ($frequency === 'daily') {
            $candidate = $start->copy();
            while ($candidate->isPast()) {
                $candidate->addDay();
                if ($endDate && $candidate->greaterThan(Carbon::parse($endDate)->endOfDay())) {
                    return null;
                }
            }
            return $candidate;
        }

        // weekly
        $weekdays = $this->normalizeWeekdays($daysOfWeek);
        if (empty($weekdays)) {
            throw ValidationException::withMessages([
                'days_of_week' => ['Select at least one day for weekly reminders.'],
            ]);
        }

        $candidate = $start->copy();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        for ($i = 0; $i < 8; $i++) {
            $dayKey = strtolower($candidate->format('D'));
            if (in_array($dayKey, $weekdays, true) && $candidate->greaterThanOrEqualTo($now)) {
                return $end && $candidate->greaterThan($end) ? null : $candidate;
            }
            $candidate->addDay();
        }

        return null;
    }

    private function storeAttachment(?UploadedFile $file, ?string $existingPath = null): array
    {
        if (!$file) {
            return [null, null, null, null];
        }

        $path = $file->store('prescriptions', ['disk' => 'public']);

        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return [
            $path,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getSize(),
        ];
    }

    private function normalizeWeekdays(array $days): array
    {
        return collect($days)
            ->map(static fn (string $day) => strtolower(substr($day, 0, 3)))
            ->filter(static fn (string $day) => in_array($day, ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], true))
            ->unique()
            ->values()
            ->all();
    }

    private function assertMemberBelongsToFamily(int $memberId, int $familyId, int $tenantId): FamilyMember
    {
        $member = FamilyMember::where('id', $memberId)
            ->where('family_id', $familyId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$member) {
            throw ValidationException::withMessages([
                'family_member_id' => ['Selected member does not belong to this family.'],
            ]);
        }

        return $member;
    }

    private function assertMedicalRecordMatchesFamily(int $recordId, int $familyId, int $memberId): void
    {
        $record = MedicalRecord::where('id', $recordId)
            ->where('family_id', $familyId)
            ->where('family_member_id', $memberId)
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'medical_record_id' => ['Medical record must belong to this member.'],
            ]);
        }
    }
}

