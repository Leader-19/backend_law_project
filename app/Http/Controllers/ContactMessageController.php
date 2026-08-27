<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search', '');

        $query = ContactMessage::with('user');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $messages = $query->latest()->paginate(15);

        $counts = [
            'all' => ContactMessage::count(),
            'open' => ContactMessage::where('status', 'open')->count(),
            'replied' => ContactMessage::where('status', 'replied')->count(),
            'closed' => ContactMessage::where('status', 'closed')->count(),
        ];

        return Inertia::render('Contact/Admin/Index', [
            'messages' => $messages,
            'currentStatus' => $status,
            'counts' => $counts,
            'search' => $search,
        ]);
    }

    public function show(ContactMessage $contactMessage): Response
    {
        $contactMessage->load('user');

        return Inertia::render('Contact/Admin/Show', [
            'message' => $contactMessage,
        ]);
    }

    public function reply(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $validated = $request->validate([
            'admin_reply' => 'required|string|max:5000',
        ]);

        $contactMessage->update([
            'admin_reply' => $validated['admin_reply'],
            'status' => 'replied',
            'replied_at' => now(),
        ]);

        return back()->with('success', 'Reply sent successfully.');
    }

    public function close(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->update(['status' => 'closed']);

        return back()->with('success', 'Message closed.');
    }

    public function reopen(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->update(['status' => 'open']);

        return back()->with('success', 'Message reopened.');
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        return back()->with('success', 'Message deleted.');
    }
}
