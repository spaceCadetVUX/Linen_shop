<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class WishlistController extends Controller
{
    /**
     * Static shell — items are guest-session-based (X-Session-ID in
     * localStorage, see CartService's pattern), so the server has no way
     * to know which items belong to this visitor at request time. JS
     * (app.js) fetches GET /api/v1/wishlist client-side and hydrates
     * #wishlistGrid after the page loads.
     */
    public function index(string $locale): View
    {
        $fallbackTitle = $locale === 'vi' ? 'Danh sách yêu thích' : 'Wishlist';
        $fallbackDescription = $locale === 'vi'
            ? 'Xem lại các sản phẩm bạn đã yêu thích tại CacyLinen.'
            : 'Products you have saved at CacyLinen.';

        return view('pages.account.wishlist', [
            'locale' => $locale,
            'fallbackTitle' => $fallbackTitle,
            'fallbackDescription' => $fallbackDescription,
            'seoMeta' => null,
            'fallbackImage' => null,
            'ogType' => 'website',
        ]);
    }
}
