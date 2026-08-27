<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $messages = $user->contactMessages()
            ->latest()
            ->paginate(12);

        return Inertia::render('Contact/Index', [
            'messages' => $messages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $user = $request->user();

        ContactMessage::create([
            'user_id' => $user->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
        ]);

        return back()->with('success', 'Message sent successfully. We will get back to you soon.');
    }

    public function show(Request $request, ContactMessage $contactMessage): Response
    {
        $user = $request->user();

        // Users can only view their own messages
        abort_unless($contactMessage->user_id === $user->id, 403);

        return Inertia::render('Contact/Show', [
            'message' => $contactMessage,
        ]);
    }
}
