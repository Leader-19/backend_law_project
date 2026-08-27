<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CertificateController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $certificates = $user->certificates()
            ->with('quiz.category')
            ->latest()
            ->paginate(12);

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
        ]);
    }

    public function show(Request $request, Certificate $certificate): InertiaResponse
    {
        $user = $request->user();

        // Users can only view their own certificates
        abort_unless($certificate->user_id === $user->id, 403);

        $certificate->load(['quiz.category', 'user']);

        return Inertia::render('Certificates/Show', [
            'certificate' => $certificate,
            'pdfUrl' => $certificate->pdf_path ? asset('storage/' . $certificate->pdf_path) : null,
        ]);
    }

    public function download(Request $request, Certificate $certificate): Response
    {
        $user = $request->user();

        // Users can only download their own certificates
        abort_unless($certificate->user_id === $user->id, 403);

        $certificate->load(['quiz.category', 'user']);

        return $this->generatePdf($certificate);
    }

    public function generatePdf(Certificate $certificate): Response
    {
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

        return $pdf->download('certificate-' . $certificate->certificate_number . '.pdf');
    }

    public function verify(Request $request, string $certificateNumber): \Illuminate\Http\JsonResponse
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with(['user:id,name,email', 'quiz:id,title,category_id', 'quiz.category:id,title'])
            ->first();

        if (! $certificate) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificate not found.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'certificate' => [
                'number' => $certificate->certificate_number,
                'user_name' => $certificate->user->name,
                'quiz_title' => $certificate->quiz->title,
                'category' => $certificate->quiz->category->title,
                'score' => $certificate->score,
                'issued_at' => $certificate->created_at->format('Y-m-d'),
            ],
        ]);
    }
}
