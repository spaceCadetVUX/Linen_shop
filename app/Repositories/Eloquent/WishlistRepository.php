<?php

namespace App\Repositories\Eloquent;

use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Collection;

class WishlistRepository extends BaseRepository
{
    protected function model(): string
    {
        return WishlistItem::class;
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function forUser(string $userId): Collection
    {
        return WishlistItem::where('user_id', $userId)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->with(['product.thumbnail', 'product.translations'])
            ->latest()
            ->get();
    }

    public function forSession(string $sessionId): Collection
    {
        return WishlistItem::where('session_id', $sessionId)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->with(['product.thumbnail', 'product.translations'])
            ->latest()
            ->get();
    }

    public function find(?string $userId, ?string $sessionId, string $productId): ?WishlistItem
    {
        return WishlistItem::where('product_id', $productId)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
            ->first();
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function add(?string $userId, ?string $sessionId, string $productId): WishlistItem
    {
        return WishlistItem::create([
            'user_id' => $userId,
            'session_id' => $userId ? null : $sessionId,
            'product_id' => $productId,
        ]);
    }

    public function remove(WishlistItem $item): void
    {
        $item->delete();
    }

    // ── Merge (guest session → logged-in user, future login flow) ──────────────

    public function mergeSessionIntoUser(string $sessionId, string $userId): void
    {
        $guestItems = WishlistItem::where('session_id', $sessionId)->get();

        foreach ($guestItems as $guestItem) {
            $exists = WishlistItem::where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->exists();

            if ($exists) {
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $userId, 'session_id' => null]);
            }
        }
    }
}
