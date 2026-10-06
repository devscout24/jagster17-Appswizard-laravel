<?php

namespace App\Http\Controllers\Api\Contractor;

use App\Http\Controllers\Controller;
use App\Models\BusinessDocument;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * License & insurance documents (reviewed by admin), logo and cover photo.
 */
class BusinessDocumentController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $documents = BusinessDocument::where('business_id', auth('api')->id())->latest()->get();

        // One summary line per type, as shown on the Business Profile "Documents" card
        $summary = collect(BusinessDocument::TYPES)->except('other')->map(function ($label, $type) use ($documents) {
            $latest = $documents->firstWhere('type', $type);

            return [
                'type' => $type,
                'label' => $label,
                'status' => $latest?->status,
                'status_label' => match ($latest?->status) {
                    'verified' => 'Uploaded · Verified',
                    'pending' => $type === 'license_insurance' ? 'Uploaded · Pending review' : 'Uploaded',
                    'rejected' => 'Rejected · Please re-upload',
                    default => 'Not uploaded',
                },
            ];
        })->values();

        return $this->success([
            'summary' => $summary,
            'documents' => $documents->map(fn ($document) => $this->present($document)),
        ], 'Documents retrieved successfully.');
    }

    /**
     * Upload a document (multipart: type, file).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(BusinessDocument::TYPES))],
            'file' => [
                'required', 'file', 'max:10240',
                $request->input('type') === 'license_insurance' || $request->input('type') === 'other'
                    ? 'mimes:pdf,jpg,jpeg,png'
                    : 'mimes:jpg,jpeg,png',
            ],
        ]);

        $user = auth('api')->user();
        $file = $request->file('file');
        $path = $file->store("documents/{$user->id}", 'public');

        // Logos and cover photos don't need review
        $document = BusinessDocument::create([
            'business_id' => $user->id,
            'type' => $validated['type'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'status' => in_array($validated['type'], ['logo', 'cover_photo'], true) ? 'verified' : 'pending',
        ]);

        if ($validated['type'] === 'logo') {
            $user->businessProfile?->update(['logo' => $path]);
        } elseif ($validated['type'] === 'cover_photo') {
            $user->businessProfile?->update(['cover_photo' => $path]);
        } elseif ($validated['type'] === 'license_insurance') {
            // A new license needs re-verification
            $user->businessProfile?->update(['is_license_verified' => false]);
        }

        return $this->success($this->present($document), 'Document uploaded.', 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $document = BusinessDocument::where('business_id', auth('api')->id())->findOrFail($id);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return $this->success(null, 'Document removed.');
    }

    private function present(BusinessDocument $document): array
    {
        return [
            'id' => $document->id,
            'type' => $document->type,
            'type_label' => BusinessDocument::TYPES[$document->type] ?? $document->type,
            'original_name' => $document->original_name,
            'file_url' => $document->file_url,
            'status' => $document->status,
            'admin_notes' => $document->admin_notes,
            'uploaded_at' => $document->created_at->toIso8601String(),
        ];
    }
}
