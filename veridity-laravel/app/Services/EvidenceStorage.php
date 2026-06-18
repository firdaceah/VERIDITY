<?php

namespace App\Services;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
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
        if ($this->usesSupabaseRest()) {
            return filled($path);
        }

        return $path ? Storage::disk($this->diskName())->exists($path) : false;
    }

    public function put(string $path, mixed $contents): bool
    {
        if ($this->usesSupabaseRest()) {
            return Http::withHeaders($this->supabaseHeaders([
                'Content-Type' => 'application/octet-stream',
                'x-upsert' => 'true',
            ]))->withBody($contents, 'application/octet-stream')
                ->post($this->supabaseObjectUrl($path))
                ->successful();
        }

        return Storage::disk($this->diskName())->put($path, $contents, [
            'visibility' => 'public',
        ]);
    }

    public function putLocalFile(string $path, string $localPath): bool
    {
        if ($this->usesSupabaseRest()) {
            $mimeType = mime_content_type($localPath) ?: 'application/octet-stream';

            return Http::withHeaders($this->supabaseHeaders([
                'Content-Type' => $mimeType,
                'x-upsert' => 'true',
            ]))->withBody(file_get_contents($localPath), $mimeType)
                ->post($this->supabaseObjectUrl($path))
                ->successful();
        }

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
        if ($this->usesSupabaseRest()) {
            if ($path) {
                Http::withHeaders($this->supabaseHeaders())->delete($this->supabaseObjectUrl($path));
            }

            return;
        }

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

        if ($this->usesSupabaseRest()) {
            return $this->supabasePublicObjectUrl($path);
        }

        return Storage::disk($this->diskName())->url($path);
    }

    public function fileResponse(string $path)
    {
        if ($this->usesSupabaseRest() || $this->diskName() !== 'public') {
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
        if ($this->usesSupabaseRest()) {
            $response = Http::withHeaders($this->supabaseHeaders())->get($this->supabaseObjectUrl($path));

            return Response::streamDownload(function () use ($response) {
                echo $response->body();
            }, $fileName, $headers);
        }

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

    private function usesSupabaseRest(): bool
    {
        return $this->diskName() === 'supabase'
            && (string) config('filesystems.supabase_service_key') !== ''
            && (string) config('filesystems.supabase_project_url') !== '';
    }

    private function supabaseHeaders(array $extra = []): array
    {
        $key = (string) config('filesystems.supabase_service_key');

        return array_merge([
            'Authorization' => 'Bearer '.$key,
            'apikey' => $key,
        ], $extra);
    }

    private function supabaseObjectUrl(string $path): string
    {
        $projectUrl = rtrim((string) config('filesystems.supabase_project_url'), '/');
        $bucket = trim((string) config('filesystems.supabase_bucket'), '/');

        return $projectUrl.'/storage/v1/object/'.$bucket.'/'.ltrim($path, '/');
    }

    private function supabasePublicObjectUrl(string $path): string
    {
        $projectUrl = rtrim((string) config('filesystems.supabase_project_url'), '/');
        $bucket = trim((string) config('filesystems.supabase_bucket'), '/');

        return $projectUrl.'/storage/v1/object/public/'.$bucket.'/'.ltrim($path, '/');
    }
}
