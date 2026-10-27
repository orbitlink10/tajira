<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment confirmed for order '.$this->order->number.' — '.setting('business.name', 'Tajira'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.payment-received',
            with: ['order' => $this->order],
        );
    }
}
