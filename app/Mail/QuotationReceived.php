<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quotation request '.$this->quotation->number.' received — '.setting('business.name', 'Tajira'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotations.received',
            with: ['quotation' => $this->quotation->loadMissing('items')],
        );
    }
}
