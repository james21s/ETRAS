<?php

namespace App\Services;

use App\Models\Paper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Wraps Laravel's Storage facade for manuscripts (private disk, uuid file names). */
class PaperFileStorage
{
    public function disk(): string
    {
        return config('standardspress.disk', 'local');
    }

    /** @return array{file_path: string, file_original_name: string, file_size: int, file_mime: string} */
    public function store(UploadedFile $file): array
    {
        $path = $file->storeAs(
            'papers/'.now()->format('Y/m'),
            Str::uuid()->toString().'.pdf',
            ['disk' => $this->disk()],
        );

        return [
            'file_path' => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_size' => (int) $file->getSize(),
            'file_mime' => 'application/pdf',
        ];
    }

    public function delete(?string $path): void
    {
        if (! $path || str_starts_with($path, Paper::SEED_DIRECTORY)) {
            return; // never remove the shared seed placeholder
        }

        Storage::disk($this->disk())->delete($path);
    }

    public function exists(?string $path): bool
    {
        return $path && Storage::disk($this->disk())->exists($path);
    }

    public function download(Paper $paper): StreamedResponse
    {
        abort_unless($this->exists($paper->file_path), 404, 'The manuscript file is not available.');

        return Storage::disk($this->disk())->download(
            $paper->file_path,
            $paper->downloadName(),
            ['Content-Type' => 'application/pdf'],
        );
    }
}
