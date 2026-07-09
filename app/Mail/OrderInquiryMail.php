<?php

namespace App\Mail;

use App\Models\OrderInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the shop (Setting::get('contact_email')) when a customer submits
 * the "Liên hệ đặt hàng" cart popup — the current stand-in for real
 * checkout/payment (see doc/todo.md). Not sent to the customer.
 */
class OrderInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly OrderInquiry $inquiry) {}

    public function build(): self
    {
        return $this
            ->subject('Yêu cầu đặt hàng mới từ ' . $this->inquiry->name)
            ->markdown('emails.order-inquiry');
    }
}
