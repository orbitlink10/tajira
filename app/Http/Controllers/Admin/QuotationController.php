<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\AuditLogger;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('quotations.view');

        $quotations = Quotation::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('q')).'%';
                $q->where(fn ($inner) => $inner->where('number', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('company', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.quotations.index', compact('quotations'));
    }

    public function show(Quotation $quotation)
    {
        $this->authorize('quotations.view');
        $quotation->load('items.product');

        return view('admin.quotations.show', compact('quotation'));
    }

    public function update(Request $request, Quotation $quotation, AuditLogger $audit)
    {
        $this->authorize('quotations.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'reviewed', 'quoted', 'accepted', 'declined', 'expired'])],
            'total' => ['nullable', 'numeric', 'min:0'],
            'valid_until' => ['nullable', 'date'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $old = $quotation->toArray();
        $quotation->update([
            'status' => $data['status'],
            'total_minor' => Money::parseToMinor($request->input('total')),
            'valid_until' => $data['valid_until'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);

        $audit->log('quotation.updated', $quotation, $old, $quotation->fresh()->toArray());

        if ($request->boolean('send_email')) {
            $lines = [
                'Quotation '.$quotation->number,
                'Status: '.$quotation->statusLabel(),
                $quotation->total_minor !== null ? 'Total: '.$quotation->formattedTotal() : null,
                $quotation->valid_until ? 'Valid until: '.$quotation->valid_until->format('j F Y') : null,
                '',
                $quotation->admin_notes ?: '',
                '',
                'This is an update to your request. Pricing and availability are confirmed by Tajira.',
            ];

            Mail::raw(implode("\n", array_filter($lines, fn ($line) => $line !== null)), function ($message) use ($quotation) {
                $message->to($quotation->email)->subject('Quotation update '.$quotation->number);
            });
        }

        return back()->with('success', 'Quotation updated.');
    }

    public function pdf(Quotation $quotation)
    {
        $this->authorize('quotations.view');
        $quotation->load('items');

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'business' => [
                'name' => setting('business.name', 'Tajira'),
                'address' => setting('business.address_line'),
                'city' => setting('business.city'),
                'phone' => setting('business.phone'),
                'email' => setting('business.email'),
                'tax_pin' => setting('business.tax_pin'),
                'domain' => setting('business.domain'),
            ],
        ])->setPaper('a4');

        return $pdf->download('Tajira-quotation-'.$quotation->number.'.pdf');
    }
}
