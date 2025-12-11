<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Requests\VerifyDocumentPasswordRequest;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyUserRole;
use App\Services\DocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService)
    {
    }

    public function index(Request $request, Family $family): View
    {
        $family = $this->resolveFamilyForUser($family, $request->user());

        $documents = Document::where('tenant_id', $request->user()->tenant_id)
            ->where('family_id', $family->id)
            ->with(['familyMember', 'uploader'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('documents.index', [
            'family' => $family,
            'documents' => $documents,
        ]);
    }

    public function create(Request $request, Family $family): View
    {
        $family = $this->resolveFamilyForUser($family, $request->user());
        Gate::authorize('create', [Document::class, $family->id]);

        $members = FamilyMember::where('family_id', $family->id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->alive()
            ->orderBy('first_name')
            ->get();

        $builtInTypes = collect(Document::TYPES)->map(fn($type) => [
            'value' => $type,
            'label' => str_replace('_', ' ', $type),
            'is_system' => true,
            'supports_expiry' => in_array($type, ['PASSPORT', 'DRIVING_LICENSE', 'INSURANCE']),
        ]);

        $customTypes = DocumentType::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($type) => [
                'value' => $type->slug,
                'label' => $type->name,
                'is_system' => false,
                'supports_expiry' => $type->supports_expiry,
            ]);

        $allTypes = $builtInTypes->merge($customTypes);

        return view('documents.create', [
            'family' => $family,
            'members' => $members,
            'documentTypes' => $allTypes->toArray(),
        ]);
    }

    public function store(StoreDocumentRequest $request, Family $family): RedirectResponse
    {
        $family = $this->resolveFamilyForUser($family, $request->user());

        $document = $this->documentService->store(
            $request->user(),
            $family,
            $request->validated(),
            $request->file('file')
        );

        return redirect()
            ->route('families.documents.index', ['family' => $family->id])
            ->with('success', 'Document uploaded successfully.');
    }

    public function update(UpdateDocumentRequest $request, Family $family, Document $document): RedirectResponse
    {
        $family = $this->resolveFamilyForUser($family, $request->user());
        $this->ensureFamilyContext($family, $document);

        $this->documentService->updateMetadata($document, $request->validated());

        return redirect()
            ->route('families.documents.index', ['family' => $family->id])
            ->with('success', 'Document updated successfully.');
    }

    public function destroy(Request $request, Family $family, Document $document): RedirectResponse
    {
        $family = $this->resolveFamilyForUser($family, $request->user());
        $this->ensureFamilyContext($family, $document);
        $this->authorize('delete', $document);

        $this->documentService->delete($document);

        return redirect()
            ->route('families.documents.index', ['family' => $family->id])
            ->with('success', 'Document removed.');
    }

    public function verifyPassword(VerifyDocumentPasswordRequest $request, Family $family, Document $document)
    {
        $family = $this->resolveFamilyForUser($family, $request->user());
        $this->ensureFamilyContext($family, $document);

        if (!$document->verifyPassword($request->validated('password'))) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        $this->grantSessionAccess($request, $document);

        return response()->json(['message' => 'Password verified.']);
    }

    public function download(Request $request, Family $family, Document $document)
    {
        $family = $this->resolveFamilyForUser($family, $request->user());
        $this->ensureFamilyContext($family, $document);
        $this->authorize('download', $document);

        // Check if password is required and not yet verified
        if ($document->requiresPasswordFor($request->user()) && !$this->hasSessionAccess($request, $document)) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'Password required', 'requires_password' => true], 403);
            }
            
            // For GET requests, redirect back with error
            return redirect()
                ->route('families.documents.index', ['family' => $family->id])
                ->with('error', 'Password required to download this document. Please use the download button to enter the password.');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            return redirect()
                ->route('families.documents.index', ['family' => $family->id])
                ->with('error', 'File not found.');
        }

        return Storage::disk('local')->download($document->file_path, $document->original_name, [
            'Content-Type' => $document->mime_type,
        ]);
    }

    private function ensureFamilyContext(Family $family, Document $document): void
    {
        if ($document->family_id !== $family->id || $document->tenant_id !== auth()->user()?->tenant_id) {
            abort(404);
        }
    }

    private function resolveFamilyForUser(Family $family, $user): Family
    {
        if ($family->tenant_id === $user->tenant_id) {
            return $family;
        }

        $belongs = FamilyUserRole::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->exists()
            || FamilyMember::where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->exists();

        if (!$belongs) {
            abort(404);
        }

        // Align user tenant with the family's tenant to avoid false 404s for linked users
        $user->update(['tenant_id' => $family->tenant_id]);
        $user->refresh();

        return $family;
    }

    private function grantSessionAccess(Request $request, Document $document): void
    {
        $key = $this->sessionKey($document->id, $request->user()->id);
        $request->session()->put($key, true);
        $request->session()->save(); // Ensure session is saved immediately
    }

    private function hasSessionAccess(Request $request, Document $document): bool
    {
        $key = $this->sessionKey($document->id, $request->user()->id);
        return (bool) $request->session()->get($key, false);
    }

    private function sessionKey(int $documentId, int $userId): string
    {
        return "document_access.{$documentId}.{$userId}";
    }
}

