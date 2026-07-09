<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

class CartController extends Controller
{
    /**
     * Static shell — guest-session based (X-Session-ID in localStorage),
     * same reasoning as WishlistController::index(): the server has no way
     * to know which cart belongs to this visitor at request time. app.js
     * fetches GET /api/v1/cart client-side and hydrates #cartItemsCol /
     * #cartSummary after the page loads.
     */
    public function index(string $locale): View
    {
        $fallbackTitle = $locale === 'vi' ? 'Giỏ hàng' : 'Cart';
        $fallbackDescription = $locale === 'vi'
            ? 'Xem lại các sản phẩm trong giỏ hàng và tiến hành thanh toán.'
            : 'Review the items in your cart.';

        return view('pages.cart.index', [
            'locale' => $locale,
            'fallbackTitle' => $fallbackTitle,
            'fallbackDescription' => $fallbackDescription,
            'shopPhone' => Setting::get('contact_phone'),
            'seoMeta' => null,
            'fallbackImage' => null,
            'ogType' => 'website',
        ]);
    }
}
