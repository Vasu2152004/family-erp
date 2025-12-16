<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Family;
use App\Models\Task;
use App\Models\FamilyMember;
use App\Services\TaskService;
use App\Services\TaskAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskAnalyticsService $analyticsService
    ) {
    }

    public function index(Request $request, Family $family): View
    {
        $user = $request->user();
        
        // Verify user has access to this family
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('viewAny', Task::class);

        $query = Task::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->with(['familyMember', 'createdBy', 'updatedBy']);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->string('frequency'));
        }

        if ($request->filled('family_member_id')) {
            $query->where('family_member_id', $request->integer('family_member_id'));
        }

        $query->orderBy('created_at', 'desc');

        $tasks = $query->paginate(15)->appends($request->query());

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        // Get analytics data for charts
        $taskStatusData = $this->analyticsService->getTaskStatusDistribution($family->id);

        return view('tasks.index', [
            'family' => $family,
            'tasks' => $tasks,
            'members' => $members,
            'filters' => $request->only(['search', 'status', 'frequency', 'family_member_id']),
            'taskStatusData' => $taskStatusData,
        ]);
    }

    public function create(Request $request, Family $family): View
    {
        $user = $request->user();
        
        // Verify user has access to this family
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('create', Task::class);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('tasks.create', [
            'family' => $family,
            'members' => $members,
        ]);
    }

    public function store(StoreTaskRequest $request, Family $family): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $task = $this->taskService->createTask(
            $request->validated(),
            $family->tenant_id,
            $family->id,
            $request->user()->id
        );

        return redirect()->route('families.tasks.show', ['family' => $family->id, 'task' => $task->id])
            ->with('success', 'Task created successfully.');
    }

    public function show(Request $request, Family $family, Task $task): View
    {
        $user = $request->user();
        
        // Verify user has access to this family
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('view', $task);

        $task->load(['familyMember', 'createdBy', 'updatedBy', 'logs.changedBy']);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('tasks.show', [
            'family' => $family,
            'task' => $task,
            'members' => $members,
        ]);
    }

    public function edit(Request $request, Family $family, Task $task): View
    {
        $user = $request->user();
        
        // Verify user has access to this family
        $hasAccess = $family->roles()->where('user_id', $user->id)->exists()
            || $family->members()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to this family.');
        }

        $this->authorize('update', $task);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        return view('tasks.edit', [
            'family' => $family,
            'task' => $task,
            'members' => $members,
        ]);
    }

    public function update(UpdateTaskRequest $request, Family $family, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $this->taskService->updateTask(
            $task,
            $request->validated(),
            $request->user()->id
        );

        return redirect()->route('families.tasks.show', ['family' => $family->id, 'task' => $task->id])
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Request $request, Family $family, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('families.tasks.index', ['family' => $family->id])
            ->with('success', 'Task deleted successfully.');
    }

    public function updateStatus(Request $request, Family $family, Task $task): RedirectResponse
    {
        // Use a custom policy method for status updates
        if (!$request->user()->can('updateStatus', $task)) {
            abort(403, 'You are not authorized to update the status of this task.');
        }

        $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,done'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->taskService->updateTaskStatus(
                $task,
                $request->string('status')->toString(),
                $request->string('notes')->toString(),
                $request->user()->id
            );

            return redirect()->back()
                ->with('success', 'Task status updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
