<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Vercel's container filesystem is not durable between requests/instances,
 * so product images can't live in public/uploads the way they do on cPanel.
 * This pushes them to Vercel Blob instead, over its plain REST API — no
 * official PHP SDK exists, but the API is just authenticated HTTP, so a
 * direct call works fine from Laravel's HTTP client.
 *
 * Docs: https://vercel.com/docs/vercel-blob
 * Requires the BLOB_READ_WRITE_TOKEN env var (Vercel dashboard → Storage →
 * Blob → Connect to Project sets this automatically).
 */
class VercelBlobStorage
{
    protected string $token;

    public function __construct()
    {
        $token = config('services.vercel_blob.token');
        if (! $token) {
            throw new RuntimeException('BLOB_READ_WRITE_TOKEN is not set — add a Vercel Blob store to this project.');
        }
        $this->token = $token;
    }

    /**
     * Upload a file and return its public URL.
     */
    public function upload(UploadedFile $file, string $folder = 'products'): string
    {
        $pathname = trim($folder, '/') . '/' . Str::random(20) . '.' . $file->getClientOriginalExtension();

        $response = Http::withToken($this->token)
            ->withHeaders([
                'x-api-version' => '7',
                'x-content-type' => $file->getMimeType(),
                'x-add-random-suffix' => '0', // we already randomize the filename above
            ])
            ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
            ->put("https://blob.vercel-storage.com/{$pathname}");

        if ($response->failed()) {
            throw new RuntimeException('Vercel Blob upload failed: ' . $response->body());
        }

        $url = $response->json('url');
        if (! $url) {
            throw new RuntimeException('Vercel Blob upload succeeded but returned no URL: ' . $response->body());
        }

        return $url;
    }

    /**
     * Delete a previously uploaded blob by its full public URL.
     * Safe to call even if the URL is already gone.
     */
    public function delete(string $url): void
    {
        Http::withToken($this->token)
            ->withHeaders(['x-api-version' => '7'])
            ->delete('https://blob.vercel-storage.com/delete', ['urls' => [$url]]);
    }
}
