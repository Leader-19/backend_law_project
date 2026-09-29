<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ChunkedUploadController extends Controller
{
    private const CHUNK_DIR = 'chunks';

    private const MAX_CHUNK_SIZE = 5 * 1024 * 1024; // 5MB per chunk

    private const MAX_UPLOAD_SIZE = 2 * 1024 * 1024 * 1024; // 2GB total

    /**
     * Initialize a chunked upload session.
     * Returns an upload_id the client uses for subsequent chunk requests.
     */
    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => 'required|string|max:255',
            'total_size' => ['required', 'integer', 'min:1', 'max:'.self::MAX_UPLOAD_SIZE],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:4096'],
        ]);

        $uploadId = Str::uuid()->toString();
        $chunkDir = $this->getChunkDir($uploadId);
        File::makeDirectory($chunkDir, 0755, true, true);

        // Store metadata so we can validate the complete upload later
        File::put(
            $chunkDir.'/meta.json',
            json_encode([
                'upload_id' => $uploadId,
                'filename' => $validated['filename'],
                'total_size' => $validated['total_size'],
                'total_chunks' => $validated['total_chunks'],
                'user_id' => $request->user()->id,
                'created_at' => now()->toIso8601String(),
            ])
        );

        return response()->json([
            'upload_id' => $uploadId,
            'chunk_size' => self::MAX_CHUNK_SIZE,
            'total_chunks' => $validated['total_chunks'],
        ]);
    }

    /**
     * Receive a single chunk (binary body) for an upload session.
     */
    public function chunk(Request $request, string $uploadId): JsonResponse
    {
        $chunkDir = $this->getChunkDir($uploadId);

        if (! File::isDirectory($chunkDir)) {
            return response()->json(['error' => 'Upload session not found. Please restart the upload.'], 404);
        }

        $metaPath = $chunkDir.'/meta.json';
        if (! File::exists($metaPath)) {
            return response()->json(['error' => 'Invalid upload session.'], 400);
        }

        $meta = json_decode(File::get($metaPath), true);
        $this->ensureUploadOwner($request, $meta);

        $validated = $request->validate([
            'chunk_index' => 'required|integer|min:0',
        ]);

        $chunkIndex = $validated['chunk_index'];
        if ($chunkIndex >= $meta['total_chunks']) {
            return response()->json(['error' => 'Chunk index out of range.'], 400);
        }

        $chunkFile = $chunkDir.'/chunk_'.str_pad($chunkIndex, 6, '0', STR_PAD_LEFT);

        // If chunk already exists, skip (resume support)
        if (File::exists($chunkFile)) {
            $uploadedChunks = $this->getUploadedChunks($chunkDir, $meta['total_chunks']);

            return response()->json([
                'message' => 'Chunk already uploaded.',
                'uploaded_chunks' => $uploadedChunks,
                'total_chunks' => $meta['total_chunks'],
            ]);
        }

        // The raw binary body is the chunk data.
        // Laravel puts the raw body in php://input when using stream context.
        $content = file_get_contents('php://input');
        if ($content === false || strlen($content) === 0) {
            return response()->json(['error' => 'Empty chunk data.'], 400);
        }
        if (strlen($content) > self::MAX_CHUNK_SIZE) {
            return response()->json(['error' => 'Chunk exceeds the 5 MB limit.'], 413);
        }

        File::put($chunkFile, $content);

        $uploadedChunks = $this->getUploadedChunks($chunkDir, $meta['total_chunks']);

        return response()->json([
            'message' => 'Chunk received.',
            'chunk_index' => $chunkIndex,
            'uploaded_chunks' => $uploadedChunks,
            'total_chunks' => $meta['total_chunks'],
        ]);
    }

    /**
     * Finalize: reassemble chunks into the final file, store it, create the document record.
     */
    public function complete(Request $request, string $uploadId): JsonResponse
    {
        $chunkDir = $this->getChunkDir($uploadId);

        if (! File::isDirectory($chunkDir)) {
            return response()->json(['error' => 'Upload session not found.'], 404);
        }

        $metaPath = $chunkDir.'/meta.json';
        if (! File::exists($metaPath)) {
            return response()->json(['error' => 'Invalid upload session.'], 400);
        }

        $meta = json_decode(File::get($metaPath), true);
        $this->ensureUploadOwner($request, $meta);

        // Validate that all chunks are present
        $uploadedChunks = $this->getUploadedChunks($chunkDir, $meta['total_chunks']);
        if ($uploadedChunks !== $meta['total_chunks']) {
            return response()->json([
                'error' => "Incomplete upload. {$uploadedChunks}/{$meta['total_chunks']} chunks received.",
                'uploaded_chunks' => $uploadedChunks,
                'total_chunks' => $meta['total_chunks'],
            ], 400);
        }

        // Reassemble the file
        $filename = \safe_filename($meta['filename']);
        $finalPath = storage_path('app/public/documents/'.$filename);

        // Ensure destination directory exists
        File::makeDirectory(dirname($finalPath), 0755, true, true);

        $handle = fopen($finalPath, 'wb');
        if (! $handle) {
            return response()->json(['error' => 'Failed to create output file.'], 500);
        }

        for ($i = 0; $i < $meta['total_chunks']; $i++) {
            $chunkFile = $chunkDir.'/chunk_'.str_pad($i, 6, '0', STR_PAD_LEFT);
            $chunkData = File::get($chunkFile);
            fwrite($handle, $chunkData);
        }
        fclose($handle);

        // Verify total file size
        $actualSize = filesize($finalPath);
        if ($actualSize !== (int) $meta['total_size']) {
            @unlink($finalPath);

            return response()->json([
                'error' => "File size mismatch. Expected {$meta['total_size']}, got {$actualSize}.",
            ], 400);
        }

        // Clean up chunk directory
        File::deleteDirectory($chunkDir);

        // Create the document record
        $validated = $request->validate([
            'doc_name' => 'required|string|max:255',
            'doc_title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:500',
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $this->authorize('create', [Document::class, $category]);
        if (! $request->user()->canCreateDocument()) {
            return response()->json(['error' => 'Your subscription does not allow more documents.'], 403);
        }

        $relativePath = 'documents/'.$filename;

        $document = Document::create([
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'],
            'doc_name' => $validated['doc_name'],
            'doc_title' => $validated['doc_title'],
            'doc_upload' => $relativePath,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully!',
            'document' => $document,
        ]);
    }

    /**
     * Get the status of an upload (for resume/check).
     */
    public function status(string $uploadId): JsonResponse
    {
        $chunkDir = $this->getChunkDir($uploadId);

        if (! File::isDirectory($chunkDir)) {
            return response()->json(['error' => 'Upload session not found.'], 404);
        }

        $metaPath = $chunkDir.'/meta.json';
        if (! File::exists($metaPath)) {
            return response()->json(['error' => 'Invalid upload session.'], 400);
        }

        $meta = json_decode(File::get($metaPath), true);
        $this->ensureUploadOwner(request(), $meta);
        $uploadedChunks = $this->getUploadedChunks($chunkDir, $meta['total_chunks']);

        return response()->json([
            'upload_id' => $uploadId,
            'filename' => $meta['filename'],
            'total_size' => $meta['total_size'],
            'uploaded_chunks' => $uploadedChunks,
            'total_chunks' => $meta['total_chunks'],
            'is_complete' => $uploadedChunks >= $meta['total_chunks'],
        ]);
    }

    /**
     * Cancel an upload and clean up chunks.
     */
    public function cancel(string $uploadId): JsonResponse
    {
        $chunkDir = $this->getChunkDir($uploadId);

        if (File::isDirectory($chunkDir)) {
            $metaPath = $chunkDir.'/meta.json';
            if (File::exists($metaPath)) {
                $this->ensureUploadOwner(request(), json_decode(File::get($metaPath), true));
            }
            File::deleteDirectory($chunkDir);
        }

        return response()->json(['message' => 'Upload cancelled.']);
    }

    // ── Helpers ──────────────────────────────────────────────

    private function getChunkDir(string $uploadId): string
    {
        return storage_path('app/'.self::CHUNK_DIR.'/'.$uploadId);
    }

    private function getUploadedChunks(string $chunkDir, int $totalChunks): int
    {
        $count = 0;
        for ($i = 0; $i < $totalChunks; $i++) {
            if (File::exists($chunkDir.'/chunk_'.str_pad($i, 6, '0', STR_PAD_LEFT))) {
                $count++;
            }
        }

        return $count;
    }

    private function ensureUploadOwner(Request $request, array $meta): void
    {
        abort_unless(
            isset($meta['user_id']) && (int) $meta['user_id'] === (int) $request->user()?->id,
            403,
            'You do not have access to this upload session.',
        );
    }
}
