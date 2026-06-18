<?php

namespace App\Services;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EvidenceStorage
{
    public function diskName(): string
    {
        return (string) config('filesystems.evidence_disk', 'public');
    }

    public function exists(?string $path): bool
    {
        return $path ? Storage::disk($this->diskName())->exists($path) : false;
    }

    public function put(string $path, mixed $contents): bool
    {
        return Storage::disk($this->diskName())->put($path, $contents, [
            'visibility' => 'public',
        ]);
    }

    public function putLocalFile(string $path, string $localPath): bool
    {
        $stream = fopen($localPath, 'r');

        try {
            return Storage::disk($this->diskName())->put($path, $stream, [
                'visibility' => 'public',
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function delete(?string $path): void
    {
        if ($path && $this->exists($path)) {
            Storage::disk($this->diskName())->delete($path);
        }
    }

    public function publicUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('filesystems.evidence_public_url'), '/');

        if ($baseUrl !== '') {
            return $baseUrl.'/'.ltrim($path, '/');
        }

        return Storage::disk($this->diskName())->url($path);
    }

    public function fileResponse(string $path)
    {
        if ($this->diskName() !== 'public') {
            return redirect()->away($this->publicUrl($path));
        }

        $fullPath = Storage::disk('public')->path($path);
        $mimeType = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';

        return Response::file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    public function downloadResponse(string $path, string $fileName, array $headers = [])
    {
        $stream = Storage::disk($this->diskName())->readStream($path);

        return Response::streamDownload(function () use ($stream) {
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $fileName, $headers);
    }

    public function makePath(string $directory, int|string|null $ownerId, string $fileName): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $fileName) ?: 'file';
        $segments = array_filter([$directory, $ownerId, time().'_'.Str::random(8).'_'.$safeName]);

        return implode('/', $segments);
    }
}
