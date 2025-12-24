<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Models\TaskLog;
use App\Services\TimezoneService;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Create a task.
     */
    public function createTask(array $data, int $tenantId, int $familyId, int $userId): Task
    {
        return DB::transaction(function () use ($data, $tenantId, $familyId, $userId) {
            // Convert recurrence_time from IST to UTC if provided
            $recurrenceTime = null;
            if (!empty($data['recurrence_time'])) {
                // Input is in IST (datetime or time), convert to UTC
                if (is_string($data['recurrence_time']) && strlen($data['recurrence_time']) <= 8) {
                    // Time only (HH:MM or HH:MM:SS), convert to UTC time
                    $utcTimeString = TimezoneService::convertIstTimeToUtcTimeString($data['recurrence_time']);
                    $recurrenceTime = \Carbon\Carbon::today()->setTimeFromTimeString($utcTimeString);
                } else {
                    // Full datetime, convert IST to UTC
                    $recurrenceTime = TimezoneService::convertIstToUtc($data['recurrence_time']);
                }
            }

            $task = Task::create([
                'tenant_id' => $tenantId,
                'family_id' => $familyId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'frequency' => $data['frequency'],
                'family_member_id' => $data['family_member_id'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'due_date' => $data['due_date'] ?? null,
                'recurrence_day' => $data['recurrence_day'] ?? null,
                'recurrence_time' => $recurrenceTime,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Create initial log entry for task creation
            $this->createTaskLog($task, null, $task->status, 'Task created', $userId);

            return $task;
        });
    }

    /**
     * Update a task.
     */
    public function updateTask(Task $task, array $data, int $userId): Task
    {
        return DB::transaction(function () use ($task, $data, $userId) {
            $oldStatus = $task->status;
            $oldAssignedTo = $task->family_member_id;

            // Convert recurrence_time from IST to UTC if provided
            $recurrenceTime = $task->recurrence_time;
            if (isset($data['recurrence_time'])) {
                if (empty($data['recurrence_time'])) {
                    $recurrenceTime = null;
                } else {
                    // Input is in IST, convert to UTC
                    if (is_string($data['recurrence_time']) && strlen($data['recurrence_time']) <= 8) {
                        // Time only (HH:MM or HH:MM:SS), convert to UTC time
                        $utcTimeString = TimezoneService::convertIstTimeToUtcTimeString($data['recurrence_time']);
                        $recurrenceTime = \Carbon\Carbon::today()->setTimeFromTimeString($utcTimeString);
                    } else {
                        // Full datetime, convert IST to UTC
                        $recurrenceTime = TimezoneService::convertIstToUtc($data['recurrence_time']);
                    }
                }
            }

            $task->update([
                'title' => $data['title'] ?? $task->title,
                'description' => $data['description'] ?? $task->description,
                'frequency' => $data['frequency'] ?? $task->frequency,
                'family_member_id' => $data['family_member_id'] ?? $task->family_member_id,
                'status' => $data['status'] ?? $task->status,
                'due_date' => $data['due_date'] ?? $task->due_date,
                'recurrence_day' => $data['recurrence_day'] ?? $task->recurrence_day,
                'recurrence_time' => $recurrenceTime,
                'updated_by' => $userId,
            ]);

            $task->refresh();

            // Log status change if status was updated
            if (isset($data['status']) && $data['status'] !== $oldStatus) {
                $this->createTaskLog($task, $oldStatus, $data['status'], 'Status updated', $userId);
            }

            // Log assignment change if assigned member was updated
            if (isset($data['family_member_id']) && $data['family_member_id'] !== $oldAssignedTo) {
                $notes = $oldAssignedTo 
                    ? 'Task reassigned' 
                    : 'Task assigned';
                $this->createTaskLog($task, $task->status, $task->status, $notes, $userId);
            }

            return $task->fresh();
        });
    }

    /**
     * Update task status with automatic logging.
     */
    public function updateTaskStatus(Task $task, string $newStatus, ?string $notes, int $userId): Task
    {
        return DB::transaction(function () use ($task, $newStatus, $notes, $userId) {
            if (!$task->canTransitionTo($newStatus)) {
                throw new \InvalidArgumentException("Invalid status transition from {$task->status} to {$newStatus}");
            }

            $oldStatus = $task->status;
            
            $task->update([
                'status' => $newStatus,
                'updated_by' => $userId,
            ]);

            // Create log entry for status change
            $this->createTaskLog($task, $oldStatus, $newStatus, $notes, $userId);

            return $task->fresh();
        });
    }

    /**
     * Assign task to a family member.
     */
    public function assignTask(Task $task, ?int $familyMemberId, int $userId): Task
    {
        return DB::transaction(function () use ($task, $familyMemberId, $userId) {
            $oldAssignedTo = $task->family_member_id;
            
            $task->update([
                'family_member_id' => $familyMemberId,
                'updated_by' => $userId,
            ]);

            // Log assignment change
            if ($oldAssignedTo !== $familyMemberId) {
                $notes = $familyMemberId 
                    ? ($oldAssignedTo ? 'Task reassigned' : 'Task assigned')
                    : 'Task unassigned';
                $this->createTaskLog($task, $task->status, $task->status, $notes, $userId);
            }

            return $task->fresh();
        });
    }

    /**
     * Create a task log entry.
     */
    private function createTaskLog(
        Task $task,
        ?string $statusFrom,
        string $statusTo,
        ?string $notes,
        int $userId
    ): TaskLog {
        return TaskLog::create([
            'tenant_id' => $task->tenant_id,
            'family_id' => $task->family_id,
            'task_id' => $task->id,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'notes' => $notes,
            'changed_by' => $userId,
            'created_at' => now(),
        ]);
    }
}










