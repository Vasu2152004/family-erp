<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VercelBlobService
{
    private const BASE_URL = 'https://blob.vercel-storage.com';

    public function upload(UploadedFile $file, string $pathname): string
    {
        $token = config('services.vercel_blob.token') ?? env('BLOB_READ_WRITE_TOKEN');
        if (empty($token)) {
            throw new \RuntimeException('Vercel Blob token (BLOB_READ_WRITE_TOKEN) is not configured.');
        }

        $url = self::BASE_URL . '/' . ltrim($pathname, '/');
        $content = $file->get();
        $contentType = $file->getMimeType();

        $response = Http::withToken($token)
            ->withHeaders([
                'x-api-version' => '7',
                'x-content-type' => $contentType,
            ])
            ->withBody($content, $contentType)
            ->put($url);

        if (!$response->successful()) {
            $body = $response->json();
            $message = $body['error']['message'] ?? $response->body();
            Log::error('Vercel Blob upload failed', ['status' => $response->status(), 'body' => $body]);
            throw new \RuntimeException('Vercel Blob upload failed: ' . $message);
        }

        $data = $response->json();
        $blobUrl = $data['url'] ?? $data['downloadUrl'] ?? null;
        if (empty($blobUrl)) {
            throw new \RuntimeException('Vercel Blob did not return a URL.');
        }

        return $blobUrl;
    }

    public function delete(string $url): void
    {
        $url = trim($url);
        if ($url === '' || !str_starts_with($url, 'https://')) {
            return;
        }

        $token = config('services.vercel_blob.token') ?? env('BLOB_READ_WRITE_TOKEN');
        if (empty($token)) {
            Log::warning('Vercel Blob token missing, skip delete');
            return;
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders(['x-api-version' => '7'])
                ->post(self::BASE_URL . '/delete', ['urls' => [$url]]);

            if (!$response->successful()) {
                Log::warning('Vercel Blob delete failed', ['status' => $response->status(), 'url' => $url]);
            }
        } catch (\Throwable $e) {
            Log::warning('Vercel Blob delete error', ['message' => $e->getMessage()]);
        }
    }
}
