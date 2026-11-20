<?php

namespace App\Mail;

use App\Models\Listing;
use App\Models\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ListingShare extends Mailable
{
    use Queueable, SerializesModels, HasUlids;

    public function __construct(public Listing $listing, public array $data, public Share $share) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->data['sender_name'].' shared a listing with you!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.listing-share',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
