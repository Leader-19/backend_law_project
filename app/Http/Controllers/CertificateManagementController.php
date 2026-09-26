<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CertificateManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $certificates = Certificate::query()
            ->with([
                'user:id,name,email',
                'quiz:id,title,category_id',
                'quiz.category:id,title',
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('certificate_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('quiz', fn ($qq) => $qq->where('title', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = Certificate::query()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 ELSE 0 END) as this_month
            ', [now()->month, now()->year])
            ->first();

        return Inertia::render('Certificates/Admin/Index', [
            'certificates' => $certificates,
            'search' => $search,
            'stats' => [
                'total' => (int) ($stats->total ?? 0),
                'this_month' => (int) ($stats->this_month ?? 0),
            ],
        ]);
    }

    public function show(Certificate $certificate): Response
    {
        $certificate->load([
            'user:id,name,email',
            'quiz:id,title,category_id',
            'quiz.category:id,title',
            'attempt',
        ]);

        return Inertia::render('Certificates/Admin/Show', [
            'certificate' => $certificate,
        ]);
    }

    public function regenerate(Certificate $certificate): RedirectResponse
    {
        // Delete old PDF if exists
        if ($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path)) {
            Storage::disk('public')->delete($certificate->pdf_path);
        }

        $certificate->update([
            'certificate_number' => Certificate::generateCertificateNumber(),
            'pdf_path' => null,
        ]);

        return back()->with(
            'success',
            'Certificate regenerated with new number: '.$certificate->certificate_number
        );
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        if ($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path)) {
            Storage::disk('public')->delete($certificate->pdf_path);
        }

        $certificate->delete();

        return back()->with('success', 'Certificate deleted.');
    }
}
