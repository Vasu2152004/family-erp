<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasFamilyContext;
use App\Models\CalendarEvent;
use App\Models\Family;
use App\Services\TimezoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    use HasFamilyContext;

    public function index(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            return redirect()->route('dashboard')->with('info', 'Please select a family to view events.');
        }

        $this->authorize('viewAny', [CalendarEvent::class, $family]);

        $query = CalendarEvent::forFamily($family->id)->orderBy('start_at', 'asc');

        if ($request->filled('from')) {
            $query->whereDate('start_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('start_at', '<=', $request->date('to'));
        }

        $events = $query->paginate(10);

        return view('calendar.index', compact('family', 'events'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            return redirect()->route('dashboard')->with('info', 'Please select a family to add events.');
        }

        $this->authorize('create', [CalendarEvent::class, $family]);

        return view('calendar.create', compact('family'));
    }

    public function store(Request $request): RedirectResponse
    {
        $family = $this->getActiveFamily($request->input('family_id'));
        if (!$family) {
            return redirect()->route('dashboard')->with('error', 'Please select a family.');
        }

        $this->authorize('create', [CalendarEvent::class, $family]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'reminder_before_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'], // up to 7 days
        ]);

        // Convert IST datetime inputs to UTC for storage
        $data = $validated;
        $data['start_at'] = TimezoneService::convertIstDateTimeToUtcString($validated['start_at']);
        if (!empty($validated['end_at'])) {
            $data['end_at'] = TimezoneService::convertIstDateTimeToUtcString($validated['end_at']);
        }

        CalendarEvent::create(array_merge($data, [
            'tenant_id' => $family->tenant_id,
            'family_id' => $family->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'reminder_sent_at' => null,
        ]));

        return redirect()->route('families.calendar.index', ['family' => $family->id])
            ->with('success', 'Event created successfully.');
    }

    public function edit(Request $request, Family $family, CalendarEvent $event): View|RedirectResponse
    {
        $this->authorize('update', $event);

        return view('calendar.edit', compact('family', 'event'));
    }

    public function update(Request $request, Family $family, CalendarEvent $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'reminder_before_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
        ]);

        // Convert IST datetime inputs to UTC for storage
        $data = $validated;
        $data['start_at'] = TimezoneService::convertIstDateTimeToUtcString($validated['start_at']);
        if (!empty($validated['end_at'])) {
            $data['end_at'] = TimezoneService::convertIstDateTimeToUtcString($validated['end_at']);
        }

        $event->update(array_merge($data, [
            'updated_by' => Auth::id(),
            // reset reminder status on change
            'reminder_sent_at' => null,
        ]));

        return redirect()->route('families.calendar.index', ['family' => $family->id])
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Request $request, Family $family, CalendarEvent $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('families.calendar.index', ['family' => $family->id])
            ->with('success', 'Event deleted successfully.');
    }
}

