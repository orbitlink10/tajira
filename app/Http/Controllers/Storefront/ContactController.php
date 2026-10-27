<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        $meta = [
            'title' => 'Contact Tajira | Networking equipment in Kenya',
            'description' => 'Contact Tajira about networking equipment, project quotations, delivery or support.',
            'canonical' => route('contact'),
            'robots' => 'index,follow',
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'previous' => null,
            'next' => null,
            'gsc_verification' => setting('seo.gsc_verification'),
        ];

        return view('storefront.contact', compact('meta'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        ContactMessage::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => strip_tags($data['message']),
            'status' => 'new',
            'ip_hash' => hash('sha256', (string) $request->ip().'|'.config('app.key')),
        ]);

        $support = setting('business.support_email') ?: setting('business.email');

        if ($support) {
            try {
                Mail::raw(
                    "New contact message\n\nName: {$data['name']}\nEmail: {$data['email']}\nPhone: ".($data['phone'] ?? '-')."\nSubject: ".($data['subject'] ?? '-')."\n\n{$data['message']}",
                    fn ($message) => $message->to($support)->subject('Tajira contact form: '.($data['subject'] ?? 'General enquiry'))
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('success', 'Thank you. Your message has been received.');
    }
}
