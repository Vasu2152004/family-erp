<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;

class TaskAnalyticsService
{
    /**
     * Get task status distribution for a family.
     */
    public function getTaskStatusDistribution(int $familyId): array
    {
        $tasks = Task::where('family_id', $familyId)->get();

        $statusCounts = [
            'pending' => 0,
            'in_progress' => 0,
            'done' => 0,
        ];

        foreach ($tasks as $task) {
            $status = $task->status;
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        return [
            [
                'status' => 'pending',
                'label' => 'Pending',
                'count' => $statusCounts['pending'],
            ],
            [
                'status' => 'in_progress',
                'label' => 'In Progress',
                'count' => $statusCounts['in_progress'],
            ],
            [
                'status' => 'done',
                'label' => 'Done',
                'count' => $statusCounts['done'],
            ],
        ];
    }
}
