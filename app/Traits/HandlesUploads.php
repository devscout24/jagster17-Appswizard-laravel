<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesUploads
{
    /**
     * Store an uploaded file on the public disk and return its relative path.
     */
    protected function storeUpload(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * Delete a previously stored upload. External URLs are left untouched.
     */
    protected function deleteUpload(?string $path): void
    {
        if ($path && ! Str::startsWith($path, ['http://', 'https://', 'assets/'])) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Resolve a stored path (or an already absolute URL) to a public URL.
     */
    protected function uploadUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // Seeded demo media lives in public/assets rather than on the storage disk.
        return Str::startsWith($path, 'assets/')
            ? asset($path)
            : Storage::disk('public')->url($path);
    }
}
