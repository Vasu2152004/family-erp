<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentTypeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'supports_expiry' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $slug = Str::slug($request->name);
        $existing = DocumentType::where('tenant_id', $request->user()->tenant_id)
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            return response()->json(['errors' => ['name' => ['This document type already exists.']]], 422);
        }

        $documentType = DocumentType::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'supports_expiry' => $request->boolean('supports_expiry', false),
            'is_system' => false,
            'sort_order' => DocumentType::where('tenant_id', $request->user()->tenant_id)->max('sort_order') + 1,
        ]);

        return response()->json([
            'message' => 'Document type created successfully.',
            'document_type' => $documentType,
        ]);
    }

    public function destroy(Request $request, DocumentType $documentType): JsonResponse
    {
        if ($documentType->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($documentType->is_system) {
            return response()->json(['message' => 'System document types cannot be deleted.'], 422);
        }

        $documentType->delete();

        return response()->json(['message' => 'Document type deleted successfully.']);
    }
}
