<?php

namespace App\Services\OrderInquiry;

use App\Enums\OrderInquiryStatus;
use App\Mail\OrderInquiryMail;
use App\Models\OrderInquiry;
use App\Models\Setting;
use App\Repositories\Eloquent\OrderInquiryRepository;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderInquiryService
{
    public function __construct(
        private readonly OrderInquiryRepository $orderInquiryRepository,
        private readonly CartService $cartService,
    ) {}

    /**
     * Builds the order summary server-side from the resolved cart — never
     * from client-submitted product/price data, which is easy to tamper
     * with in a plain fetch() body. The customer only supplies contact
     * info + channel + an optional free-text note.
     */
    public function submit(Request $request, array $data): OrderInquiry
    {
        $cart = $this->cartService->resolveCart($request);
        $message = $this->buildMessage($cart, $data['message'] ?? null);

        $user = $request->user() ?? auth('sanctum')->user();

        $inquiry = $this->orderInquiryRepository->create([
            'user_id' => $user?->id,
            'session_id' => $user ? null : $request->header('X-Session-ID'),
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'message' => $message,
            'channel' => $data['channel'],
            'status' => OrderInquiryStatus::New,
        ]);

        $this->sendNotification($inquiry);

        return $inquiry;
    }

    private function buildMessage($cart, ?string $note): string
    {
        $lines = [];

        foreach ($cart->items as $item) {
            $name = $item->product->name . ($item->variant ? ' (' . $item->variant->combination_label . ')' : '');
            $lines[] = "- {$name} x{$item->quantity} = " . number_format($item->subtotal, 0, ',', '.') . ' ₫';
        }

        $lines[] = '';
        $lines[] = 'Tổng cộng: ' . number_format($cart->total, 0, ',', '.') . ' ₫';

        if (filled($note)) {
            $lines[] = '';
            $lines[] = 'Ghi chú của khách: ' . $note;
        }

        return implode("\n", $lines);
    }

    /**
     * Best-effort — a failed send (e.g. SMTP not configured yet, MAIL_MAILER=log
     * in dev) must never lose the inquiry itself. The DB row is the source of
     * truth admin can always fall back to in Filament.
     */
    private function sendNotification(OrderInquiry $inquiry): void
    {
        $shopEmail = Setting::get('contact_email');

        if (blank($shopEmail)) {
            return;
        }

        try {
            Mail::to($shopEmail)->send(new OrderInquiryMail($inquiry));
        } catch (\Throwable) {
            // Swallow — the inquiry is already persisted; admin still sees it in Filament.
        }
    }
}
