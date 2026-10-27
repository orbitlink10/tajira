<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('quotations.view');

        $messages = ContactMessage::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.contact-messages.index', compact('messages'));
    }

    public function show(ContactMessage $contactMessage)
    {
        $this->authorize('quotations.view');

        return view('admin.contact-messages.show', compact('contactMessage'));
    }

    public function update(Request $request, ContactMessage $contactMessage, AuditLogger $audit)
    {
        $this->authorize('quotations.manage');
        $data = $request->validate(['status' => ['required', 'in:new,read,replied,closed']]);
        $old = $contactMessage->toArray();
        $contactMessage->update($data);
        $audit->log('contact-message.updated', $contactMessage, $old, $contactMessage->fresh()->toArray());

        return back()->with('success', 'Message status updated.');
    }
}
