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
        $certificates = $request->user()
            ->certificates()
            ->with([
                'quiz:id,title,category_id',
                'quiz.category:id,title',
            ])
            ->latest()
            ->paginate(12);

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
        ]);
    }

    public function show(Request $request, Certificate $certificate): InertiaResponse
    {
        abort_unless($certificate->user_id === $request->user()->id, 403);

        $certificate->load([
            'quiz:id,title,category_id',
            'quiz.category:id,title',
            'user:id,name,email',
        ]);

        return Inertia::render('Certificates/Show', [
            'certificate' => $certificate,
            'pdfUrl' => $certificate->pdf_path
                ? asset('storage/' . $certificate->pdf_path)
                : null,
        ]);
    }

    public function download(Request $request, Certificate $certificate): Response
    {
        abort_unless($certificate->user_id === $request->user()->id, 403);

        $certificate->loadMissing(['quiz.category', 'user']);

        // Serve existing PDF if available
        if ($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path)) {
            return response()->file(
                Storage::disk('public')->path($certificate->pdf_path),
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="certificate-' . $certificate->certificate_number . '.pdf"',
                ]
            );
        }

        return $this->generatePdf($certificate);
    }

    public function generatePdf(Certificate $certificate): Response
    {
        $certificate->loadMissing(['quiz.category', 'user']);

        $pdf = app(PDF::class);
        $pdf->loadView('certificates.certificate', [
            'userName' => $certificate->user->name,
            'quizTitle' => $certificate->quiz->title,
            'categoryName' => $certificate->quiz->category->title ?? 'N/A',
            'score' => $certificate->score,
            'certificateNumber' => $certificate->certificate_number,
            'issuedDate' => $certificate->created_at->format('F d, Y'),
        ]);

        $filename = 'certificates/' . $certificate->certificate_number . '.pdf';

        if (! Storage::disk('public')->exists($filename)) {
            Storage::disk('public')->put($filename, $pdf->output());
        }

        if (! $certificate->pdf_path) {
            $certificate->update(['pdf_path' => $filename]);
        }

        return $pdf->download('certificate-' . $certificate->certificate_number . '.pdf');
    }

    public function verify(Request $request, string $certificateNumber): \Illuminate\Http\JsonResponse
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with([
                'user:id,name,email',
                'quiz:id,title,category_id',
                'quiz.category:id,title',
            ])
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
                'category' => $certificate->quiz->category->title ?? null,
                'score' => $certificate->score,
                'issued_at' => $certificate->created_at->format('Y-m-d'),
            ],
        ]);
    }
}
