<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Notes\StoreNoteRequest;
use App\Http\Requests\Notes\UpdateNoteRequest;
use App\Models\Family;
use App\Models\Note;
use App\Models\FamilyMember;
use App\Services\NoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteController extends Controller
{
    public function __construct(private readonly NoteService $noteService)
    {
    }

    public function index(Request $request, Family $family): View
    {
        $this->authorize('viewAny', Note::class);

        $query = Note::where('family_id', $family->id)
            ->where('tenant_id', $family->tenant_id)
            ->with(['creator']);

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->string('visibility'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $notes = $query->orderBy('updated_at', 'desc')->paginate(15)->appends($request->query());

        return view('notes.index', [
            'family' => $family,
            'notes' => $notes,
            'filters' => $request->only(['visibility', 'search']),
        ]);
    }

    public function create(Family $family): View
    {
        $this->authorize('create', Note::class);

        return view('notes.create', [
            'family' => $family,
        ]);
    }

    public function store(StoreNoteRequest $request, Family $family): RedirectResponse
    {
        $this->authorize('create', Note::class);

        $note = $this->noteService->create(
            $request->validated(),
            $family->tenant_id,
            $family->id,
            $request->user()->id
        );

        return redirect()->route('families.notes.show', ['family' => $family->id, 'note' => $note->id])
            ->with('success', 'Note created successfully.');
    }

    public function show(Request $request, Family $family, Note $note): View
    {
        $this->authorize('view', $note);

        $isUnlocked = $this->noteService->isUnlocked($note);

        return view('notes.show', [
            'family' => $family,
            'note' => $note->load(['creator', 'updater']),
            'isUnlocked' => $isUnlocked,
        ]);
    }

    public function edit(Family $family, Note $note): View
    {
        $this->authorize('update', $note);

        return view('notes.edit', [
            'family' => $family,
            'note' => $note,
        ]);
    }

    public function update(UpdateNoteRequest $request, Family $family, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $this->noteService->update($note, $request->validated(), $request->user()->id);

        return redirect()->route('families.notes.show', ['family' => $family->id, 'note' => $note->id])
            ->with('success', 'Note updated successfully.');
    }

    public function destroy(Family $family, Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $this->noteService->delete($note);

        return redirect()->route('families.notes.index', ['family' => $family->id])
            ->with('success', 'Note deleted successfully.');
    }

    public function unlock(Request $request, Family $family, Note $note): RedirectResponse
    {
        $this->authorize('view', $note);

        $request->validate([
            'pin' => ['required', 'string'],
        ]);

        if ($this->noteService->unlock($note, $request->string('pin')->toString())) {
            return redirect()->route('families.notes.show', ['family' => $family->id, 'note' => $note->id])
                ->with('success', 'Note unlocked successfully.');
        }

        return redirect()->route('families.notes.show', ['family' => $family->id, 'note' => $note->id])
            ->with('error', 'Invalid PIN. Please try again.');
    }
}

