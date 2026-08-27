<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificateManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');

        $certificates = Certificate::with(['user', 'quiz.category'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('certificate_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('quiz', fn ($qq) => $qq->where('title', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15);

        $stats = [
            'total' => Certificate::count(),
            'this_month' => Certificate::whereMonth('created_at', now()->month)->count(),
        ];

        return Inertia::render('Certificates/Admin/Index', [
            'certificates' => $certificates,
            'search' => $search,
            'stats' => $stats,
        ]);
    }

    public function show(Certificate $certificate): Response
    {
        $certificate->load(['user', 'quiz.category', 'attempt']);

        return Inertia::render('Certificates/Admin/Show', [
            'certificate' => $certificate,
        ]);
    }

    public function regenerate(Certificate $certificate): RedirectResponse
    {
        $certificate->update([
            'certificate_number' => Certificate::generateCertificateNumber(),
        ]);

        return back()->with('success', 'Certificate regenerated with new number: ' . $certificate->certificate_number);
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $certificate->delete();

        return back()->with('success', 'Certificate deleted.');
    }
}
