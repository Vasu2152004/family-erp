<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskLogController extends Controller
{
    public function index(Request $request, Family $family, Task $task): View
    {
        $this->authorize('view', $task);

        $logs = TaskLog::where('task_id', $task->id)
            ->where('tenant_id', $family->tenant_id)
            ->where('family_id', $family->id)
            ->with('changedBy')
            ->latestFirst()
            ->paginate(20);

        return view('tasks.logs.index', [
            'family' => $family,
            'task' => $task,
            'logs' => $logs,
        ]);
    }
}
