<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    /**
     * Display listing of customer/contractor contact inquiries.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = ContactMessage::with('user');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $messages = $query->latest()->paginate(15)->withQueryString();

        return view('admin.contact-messages.index', compact('messages', 'status', 'search'));
    }

    /**
     * Show message details.
     */
    public function show(int $id): View
    {
        $message = ContactMessage::with('user')->findOrFail($id);

        return view('admin.contact-messages.show', compact('message'));
    }

    /**
     * Update inquiry status.
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $message = ContactMessage::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,resolved'],
        ]);

        $message->update(['status' => $validated['status']]);

        return back()->with('success', "Message marked as {$validated['status']}.");
    }

    /**
     * Delete message.
     */
    public function destroy(int $id): RedirectResponse
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return redirect()->route('admin.contact-messages.index')
            ->with('success', 'Message removed successfully.');
    }
}
