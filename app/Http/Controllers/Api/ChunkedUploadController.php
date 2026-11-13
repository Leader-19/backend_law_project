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

<<<<<<< HEAD
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
=======
    private const MAX_CHUNKS = 410;
>>>>>>> 31a3896 (add k8s)

    /**
     * Initialize a chunked upload session.
     * Returns an upload_id the client uses for subsequent chunk requests.
     */
    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => 'required|string|max:255',
            'total_size' => ['required', 'integer', 'min:1', 'max:'.self::MAX_UPLOAD_SIZE],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:'.self::MAX_CHUNKS],
        ]);

<<<<<<< HEAD
        $extension = strtolower(pathinfo($validated['filename'], PATHINFO_EXTENSION));
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return response()->json([
                'error' => 'File type not allowed. Allowed types: '.implode(', ', self::ALLOWED_EXTENSIONS).'.',
            ], 422);
=======
        $expectedChunks = (int) ceil($validated['total_size'] / self::MAX_CHUNK_SIZE);
        if ((int) $validated['total_chunks'] !== $expectedChunks) {
            return response()->json(['error' => 'Chunk count does not match the declared file size.'], 422);
>>>>>>> 31a3896 (add k8s)
        }

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

        $request->merge([
            'chunk_index' => $request->input('chunk_index', $request->header('X-Chunk-Index')),
        ]);
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
        $expectedBytes = min(self::MAX_CHUNK_SIZE, (int) $meta['total_size'] - ($chunkIndex * self::MAX_CHUNK_SIZE));
        $input = fopen('php://input', 'rb');
        $output = fopen($chunkFile, 'wb');
        if ($input === false || $output === false) {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) fclose($output);
            return response()->json(['error' => 'Unable to store upload chunk.'], 500);
        }

        $bytesWritten = stream_copy_to_stream($input, $output, self::MAX_CHUNK_SIZE + 1);
        fclose($input);
        fclose($output);

        if ($bytesWritten === false || $bytesWritten === 0) {
            File::delete($chunkFile);
            return response()->json(['error' => 'Empty chunk data.'], 400);
        }
        if ($bytesWritten > self::MAX_CHUNK_SIZE || $bytesWritten !== $expectedBytes) {
            File::delete($chunkFile);
            return response()->json(['error' => 'Chunk exceeds the 5 MB limit.'], 413);
        }

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

        // Validate every expected chunk instead of relying on a count alone.
        $uploadedChunks = $this->getUploadedChunks($chunkDir, $meta['total_chunks']);
        $missingChunks = [];
        for ($i = 0; $i < $meta['total_chunks']; $i++) {
            if (! File::exists($chunkDir.'/chunk_'.str_pad($i, 6, '0', STR_PAD_LEFT))) {
                $missingChunks[] = $i;
            }
        }
        if ($missingChunks !== []) {
            return response()->json([
                'error' => "Incomplete upload. {$uploadedChunks}/{$meta['total_chunks']} chunks received.",
                'uploaded_chunks' => $uploadedChunks,
                'total_chunks' => $meta['total_chunks'],
            ], 400);
        }

<<<<<<< HEAD
        $extension = strtolower(pathinfo($meta['filename'], PATHINFO_EXTENSION));
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            File::deleteDirectory($chunkDir);

            return response()->json(['error' => 'File type not allowed.'], 422);
        }

        // Reassemble the file. Stored on the private disk - category/subscription
        // access checks only mean anything if the raw file isn't also reachable
        // via a public URL, so this must never go under storage/app/public.
=======
        // Validate metadata and permissions before assembling a potentially large file.
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

        // Reassemble to disk in bounded memory.
>>>>>>> 31a3896 (add k8s)
        $filename = \safe_filename($meta['filename']);
        $finalPath = storage_path('app/private/documents/'.$filename);

        // Ensure destination directory exists
        File::makeDirectory(dirname($finalPath), 0755, true, true);

        $handle = fopen($finalPath, 'wb');
        if (! $handle) {
            return response()->json(['error' => 'Failed to create output file.'], 500);
        }

        $actualSize = 0;
        for ($i = 0; $i < $meta['total_chunks']; $i++) {
            $chunkFile = $chunkDir.'/chunk_'.str_pad($i, 6, '0', STR_PAD_LEFT);
            $chunkHandle = fopen($chunkFile, 'rb');
            if ($chunkHandle === false) {
                fclose($handle);
                @unlink($finalPath);
                return response()->json(['error' => 'A stored upload chunk could not be read.'], 500);
            }
            $copied = stream_copy_to_stream($chunkHandle, $handle);
            fclose($chunkHandle);
            if ($copied === false) {
                fclose($handle);
                @unlink($finalPath);
                return response()->json(['error' => 'Failed to assemble the uploaded file.'], 500);
            }
            $actualSize += $copied;
        }
        fclose($handle);

        // Verify total file size
        if ($actualSize !== (int) $meta['total_size']) {
            @unlink($finalPath);

            return response()->json([
                'error' => "File size mismatch. Expected {$meta['total_size']}, got {$actualSize}.",
            ], 400);
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

        File::deleteDirectory($chunkDir);

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
