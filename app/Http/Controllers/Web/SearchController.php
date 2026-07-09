<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * /{locale}/tim-kiem and /{locale}/search were a separate dead-end page
     * (this used to return a raw debug string). The shop/PLP page
     * (ProductController::index) already does real Meilisearch-backed
     * keyword search via ?q= — redirect here instead of maintaining a
     * second, duplicate results template.
     */
    public function index(Request $request, string $locale): RedirectResponse
    {
        return redirect()->route("{$locale}.product.shop", ['q' => $request->query('q', '')]);
    }
}
