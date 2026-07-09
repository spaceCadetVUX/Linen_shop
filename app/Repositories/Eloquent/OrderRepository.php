<?php

namespace App\Repositories\Eloquent;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OrderRepository extends BaseRepository
{
    protected function model(): string
    {
        return Order::class;
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function getCartWithItems(User $user): ?Cart
    {
        return Cart::where('user_id', $user->id)
            ->with('items.product')
            ->first();
    }

    public function paginateForUser(User $user, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('user_id', $user->id)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    public function findForUser(User $user, string $orderId): ?Order
    {
        /** @var Order|null */
        return $this->query()
            ->where('id', $orderId)
            ->where('user_id', $user->id)
            ->with('items')
            ->first();
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function createOrder(array $data): Order
    {
        return Order::create($data);
    }

    /**
     * Must run inside the caller's DB::transaction() (OrderService::placeOrder()) —
     * lockForUpdate() only holds the lock for the transaction's lifetime.
     *
     * Re-reads each product FOR UPDATE right before checking stock, rather than
     * trusting $item->product (eager-loaded earlier, potentially stale). Without
     * the lock, two concurrent orders for the last unit of the same product could
     * both read "1 in stock" and both pass validation before either commits —
     * decrement() itself is an atomic SQL UPDATE, but the *validation preceding it*
     * wasn't, so both orders would still go through and stock would go negative.
     * The lock forces the second transaction to block until the first commits,
     * at which point it re-reads the now-decremented value and correctly fails.
     */
    public function createOrderItems(Order $order, Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

            if (! $product || $product->stock_quantity < $item->quantity) {
                $available = $product->stock_quantity ?? 0;
                throw ValidationException::withMessages([
                    'cart' => ["\"{$item->product->name}\" only has {$available} unit(s) in stock."],
                ]);
            }

            $order->items()->create([
                'product_id'   => $item->product_id,
                'product_name' => $item->product->name,
                'product_sku'  => $item->product->sku,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->product->sale_price ?? $item->product->price,
            ]);

            $product->decrement('stock_quantity', $item->quantity);
        }
    }

    public function restoreStock(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            if ($item->product) {
                $item->product->increment('stock_quantity', $item->quantity);
            }
        }
    }
}
