<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $certificates = $user->certificates()
            ->with('quiz.category')
            ->latest()
            ->paginate(12);

        return response()->json([
            'status' => 'success',
            'data' => $certificates->items(),
            'pagination' => [
                'current_page' => $certificates->currentPage(),
                'last_page' => $certificates->lastPage(),
                'per_page' => $certificates->perPage(),
                'total' => $certificates->total(),
            ],
        ]);
    }

    public function show(Request $request, Certificate $certificate): JsonResponse
    {
        $user = $request->user();

        // Users can only view their own certificates
        abort_unless($certificate->user_id === $user->id, 403);

        $certificate->load(['quiz.category', 'user']);

        return response()->json([
            'status' => 'success',
            'certificate' => $certificate,
            'pdf_url' => $certificate->pdf_path ? asset('storage/' . $certificate->pdf_path) : null,
        ]);
    }

    public function download(Request $request, Certificate $certificate): JsonResponse
    {
        $user = $request->user();

        // Users can only download their own certificates
        abort_unless($certificate->user_id === $user->id, 403);

        $certificate->load(['quiz.category', 'user']);

        $pdf = app(PDF::class);
        $pdf->loadView('certificates.certificate', [
            'userName' => $certificate->user->name,
            'quizTitle' => $certificate->quiz->title,
            'categoryName' => $certificate->quiz->category->title,
            'score' => $certificate->score,
            'certificateNumber' => $certificate->certificate_number,
            'issuedDate' => $certificate->created_at->format('F d, Y'),
        ]);

        // Save PDF to storage for future use
        $filename = 'certificates/' . $certificate->certificate_number . '.pdf';
        $storagePath = storage_path('app/public/' . $filename);

        $directory = dirname($storagePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdf->save($storagePath);

        // Update certificate with pdf_path if not set
        if (! $certificate->pdf_path) {
            $certificate->update(['pdf_path' => $filename]);
        }

        return response()->json([
            'status' => 'success',
            'download_url' => asset('storage/' . $filename),
        ]);
    }
}