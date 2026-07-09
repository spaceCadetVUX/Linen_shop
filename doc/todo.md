# TODO

> Cập nhật: 2026-07-09 (tiếp 3) — **Docker/Postgres đã kết nối được, verify runtime thật lần đầu.** Chạy hết 6 migration đang chờ + restart Horizon, test bằng tay toàn bộ checklist "Cần test lại bằng tay" của session trước (Category SEO, deactivate-parent, alt-locale redirect, Cart IDOR, Cart variant-bypass, Wishlist double-click + is_active, Order Inquiry end-to-end) qua curl + tinker trực tiếp trên container. Kết quả: **tất cả fix của session trước đều đúng**. Phát hiện thêm + fix ngay 3 bug mới (ngoài phạm vi các session trước):

## 🔴 3 bug mới phát hiện trong lúc verify — đã fix

1. **`BusinessProfile::instance()` tạo row rác vô hạn, dữ liệu Business Profile chưa bao giờ đọc/lưu được thật** (`app/Models/BusinessProfile.php:44`) — `firstOrCreate(['id' => 1], [...])` nhưng `id` không có trong `$fillable`, nên `create()` âm thầm bỏ qua `id=1`, mỗi lần gọi không tìm thấy row `id=1` lại tạo 1 row rỗng mới (bigint tự tăng). Xảy ra ở **mọi request** (header/footer/JSON-LD gọi `Setting::get()`, cả `EditBusinessProfile::mount()` trong Filament). Hậu quả đo được: bảng `business_profiles` có **35,072 row** lúc phát hiện (tăng lên ~51k trong lúc Horizon catch-up queue), quét toàn bộ **0 row có dữ liệu thật** — nghĩa là og:image, contact info, social links, Organization JSON-LD... tất cả luôn rỗng từ trước tới giờ dù có thể đã từng điền form trong Filament. Đã fix bằng `forceFill(['id' => 1, ...])`, dọn hết row rác (không mất gì vì xác nhận không row nào có data), flush cache `business_jsonld_schemas_{vi,en}`. **Anh cần vào lại Filament → Business Profile điền lại toàn bộ thông tin — lần này sẽ lưu và đọc lại đúng.**
2. **`/sitemap.xml` + toàn bộ sitemap con crash 500** — `SitemapController` gọi `view('sitemap.index'|'sitemap.static'|'sitemap.child')` nhưng `resources/views/sitemap/` **không tồn tại**, chưa từng được tạo (0 lịch sử git). Nghĩa là sitemap chưa từng hoạt động được — nếu Google Search Console đang submit sitemap thì luôn nhận 500. Đã tạo 3 view (`index.blade.php`, `static.blade.php`, `child.blade.php`), verify: index liệt kê đủ 8 child, static + category child sitemap render XML hợp lệ kèm `xhtml:link hreflang`.
3. **`AddCartItemRequest::variant_id` validate sai kiểu — chặn 100% việc thêm sản phẩm có variant vào giỏ qua API** (`app/Http/Requests/Cart/AddCartItemRequest.php:18`) — rule `['nullable', 'uuid', 'exists:product_variants,id']` nhưng `product_variants.id` là `bigint` không phải `uuid`. Mọi request hợp lệ (variant_id là số) đều bị 422 "must be a valid UUID". Phát hiện khi verify chính fix "chặn bypass variant" của session trước — fix đó (chặn thiếu variant_id) đúng, nhưng path "có variant_id" lại bị chặn nhầm bởi bug validate riêng. Đã sửa `uuid` → `integer`.

## ✅ Checklist verify session trước — kết quả (đều PASS)

1. Category `/vi/danh-muc` + `/vi/danh-muc/{slug}`: canonical/robots/hreflang/JSON-LD (CollectionPage + BreadcrumbList) đúng. (og:image thiếu — do bug #1 phía trên, không phải lỗi code SEO.)
2. Deactivate category cha (qua Eloquent `save()`, đúng cách Filament làm) → card biến mất khỏi index **và** mega menu, sitemap entry set `is_active=false` (qua queue `seo`, có delay vì queue backlog — không phải bug).
3. Alt-locale redirect: category active → 302 đúng locale; category/parent inactive → 404 thẳng, không redirect chain.
4. Cart IDOR: PUT/DELETE không có `X-Session-ID` (hoặc sai `X-Session-ID`) nhắm cart_item của user khác → 403 cả 3 case, DB không đổi.
5. Cart variant-bypass: thêm sản phẩm có variant mà không kèm `variant_id` → 422 (đúng). Kèm `variant_id` hợp lệ → bị chặn bởi bug #3 trước khi fix; sau fix → 201 đúng.
6. Wishlist double-click: guard chỉ ở client (JS disable button), backend `toggle()` không lock — đúng như comment code đã ghi. Test 2 request song song qua API không tái hiện được race (đúng bản chất hiếm khi trigger), không phải bug mới, chấp nhận theo thiết kế hiện tại.
7. Wishlist toggle sản phẩm đã deactivate → 404 "Product not found." đúng.
8. Order Inquiry end-to-end: submit qua `channel=email` → tạo đúng record DB (message build từ cart thật server-side, không tin client) + mail render đúng qua log driver (`To:`, `Subject: Yêu cầu đặt hàng`, nội dung cart/tổng tiền/ghi chú/nút gọi điện đều đúng).

## 🟡 Ghi nhận thêm, chưa fix (mức độ thấp, phát hiện phụ trong lúc verify)

- Response 403/404 từ `abort()`/`abort_unless()` (Cart IDOR, Wishlist deactivated) trả về raw debug stack trace, không qua `ApiResponse` envelope như CLAUDE.md yêu cầu. Cần rà lại exception handler cho API nếu muốn đồng bộ format lỗi.
- `failed_jobs` có 7 job cũ lỗi `ModelNotFoundException: No query results for model [App\Models\Brand]` (job SEO sync bị queue cho 1 Brand đã bị xoá trước khi job chạy) — pre-existing, chưa điều tra thêm.
- Trang show() category (`/vi/danh-muc/{slug}`) cũng thiếu `og:image` giống trang index (cùng nguyên nhân bug #1).

## 🔴 Migration mới cần thêm vào danh sách chạy khi Docker lên

- `2026_07_09_130000_create_order_inquiries_table.php` (bảng cho tính năng "Liên hệ đặt hàng")

## 🟠 Đã code xong hôm nay (tiếp 2), chưa verify runtime

1. **Order Inquiry ("Liên hệ đặt hàng")** — thanh toán online tạm disable, thay bằng popup trên trang giỏ hàng: Zalo (copy nội dung đơn hàng vào clipboard, không có API prefill chính thức — đã xác nhận qua WebSearch), gọi điện (`tel:` link), email (form thật → `POST /api/v1/order-inquiries` → gửi mail `OrderInquiryMail` + lưu bảng `order_inquiries` để xem trong Filament). `OrderInquiryService::submit()` tự build nội dung đơn hàng từ `CartService::resolveCart()` phía server, không tin dữ liệu cart do client gửi lên.
2. **Cart — 2 lỗi bảo mật/logic tìm thấy qua list debug có hệ thống:**
   - **IDOR nghiêm trọng** trong `CartService::authorizeItem()` — thiếu check non-null 2 nhánh, request ẩn danh không gửi `X-Session-ID` có thể match `null === null` với cart của BẤT KỲ user đã đăng nhập nào (kết hợp `cart_items.id` là int tự tăng, dễ đoán). Đã fix.
   - **Bypass chọn variant** — `CartService::addItem()` trước đây chỉ chặn ở JS (PDP disable nút), gọi API trực tiếp vẫn thêm được sản phẩm có variant mà không chọn variant nào. Đã thêm check `activeVariants()->exists()` bắt buộc `variant_id`.
   - Kèm theo: JS đọc lỗi validate (`ValidationException` không đi qua `ApiResponse` envelope, có `errors` riêng) — thêm helper `firstApiError()` trong `app.js`, áp cho cả PDP add-to-cart và Wishlist add-to-cart (nút "Thêm vào giỏ" ở trang yêu thích gọi chung API này, trước đây sẽ hiện lỗi mơ hồ "The given data was invalid.").
3. **Wishlist — 3 lỗi tìm thấy qua list debug tương tự Cart:**
   - **Race condition** khi double-click nút xoá (`.fav-card-del` không disable như `.fav-atc`) — 2 request gần như đồng thời có thể làm sản phẩm bị xoá rồi tự thêm lại do `toggle()` không có lock. Đã thêm disable guard.
   - **Thiếu check `is_active`** trong `WishlistService::toggle()` — khác `CartService::addItem()` đã có — cho phép toggle sản phẩm đã bị admin deactivate, tạo dòng "ma" vô hình trong DB. Đã thêm `abort_if(! $product->is_active, 404)`.
   - **Vi phạm rule FormRequest** — `WishlistController@toggle` dùng `$request->validate()` trực tiếp, sai CLAUDE.md. Đã tạo `ToggleWishlistRequest`.
4. **Category — 2 lỗi 404/redirect-chain tìm thấy khi audit SEO structure:**
   - Trang `/vi/danh-muc` (index liệt kê tất cả category) hiện link tới category con mà **cha đã bị deactivate** → bấm vào 404 ngay (index chỉ check `is_active` của chính nó, không check `isPubliclyVisible()` như `show()` đã làm). Đã fix bằng filter `isPubliclyVisible()` + eager-load `category.parent` (tránh N+1).
   - Redirect alt-locale trong `show()` (khi slug không tồn tại ở locale hiện tại nhưng có ở locale khác) không check category đó còn active hay không → có thể redirect 302 sang 1 trang tự 404 luôn (redirect chain, xấu cho SEO). Đã fix, check `isPubliclyVisible()` trước khi redirect.
5. **Redesign trang danh mục (`/vi/danh-muc`)** — đổi từ list text sang grid card ảnh nền + tên serif nghiêng (tái dùng visual style `.edit-grid` của trang chủ, nhưng chỉnh kích thước cố định 4:5 phù hợp liệt kê nhiều category thay vì hero 3-6 item). Đồng thời vá 2 lỗi thiếu structure phát hiện khi audit: trang này trước đây **0 JSON-LD** (không có `BreadcrumbList` dù UI có hiện breadcrumb) và **thiếu `og:image`** — đã build tay `BreadcrumbList` qua `JsonldService::buildBreadcrumbSchema()` (trang không phải 1 Category model nên không có sẵn row `jsonld_schemas`) + thêm `og:image` từ `Setting::get('default_og_image')`.

## 🟡 Cần test lại bằng tay khi Docker/Postgres lên (theo bộ checklist SEO Category đã đưa ra trong chat — nhóm structure, chưa test nhóm nội dung)

1. View-source `/vi/danh-muc/{slug}` và `/vi/danh-muc` — xác nhận đủ canonical/robots/hreflang/OG/JSON-LD như đã audit.
2. Deactivate 1 category cha trong Filament → xác nhận: (a) category con biến mất khỏi `/vi/danh-muc`, (b) mega menu không còn hiện, (c) sitemap/llms.txt không còn liệt kê con.
3. Test đường redirect alt-locale: slug chỉ có bản dịch 1 locale + category/parent inactive → phải 404 thẳng, không redirect.
4. Test lại IDOR Cart đã fix: gọi `PUT/DELETE /api/v1/cart/items/{id}` không gửi `X-Session-ID` nhắm vào cart_item của user khác → phải 403.
5. Test double-click nút xoá Wishlist → không được tự thêm lại sản phẩm.
6. Test toggle Wishlist với `product_id` của sản phẩm đã deactivate → phải 404.
7. Test Order Inquiry: gửi form email → nhận được mail + thấy record trong Filament `Order Inquiries`.

---

> Cập nhật: 2026-07-09 (tiếp) — session: Wishlist (guest-session, toggle thật, trang `/tai-khoan/yeu-thich`), Cart page thật (`/gio-hang`, `/cart`) + nối "Thêm vào giỏ hàng"/"Yêu thích" từ nút giả (chỉ đổi chữ, không lưu gì) sang gọi API thật, Cart hỗ trợ variant (màu/size riêng dòng). Phát hiện `resources/views/pages/cart/index.blade.php` và `pages/account/wishlist.blade.php` tồn tại từ trước nhưng 100% mockup hardcode (ảnh hotlink domain lạ `elleandriley.com`, không route, không JS) — đã build lại thật theo đúng thiết kế đó. **Vẫn chưa verify runtime** — lý do như cũ (không kết nối `postgres` từ host).

## 🔴 Cần chạy khi Docker lên — giờ có 5 migration đang chờ (cộng dồn cả session trước)

```bash
docker compose exec php-fpm php artisan migrate
docker compose restart horizon
```

- `2026_07_09_100000_create_review_images_table.php`
- `2026_07_09_100001_add_email_to_reviews_table.php`
- `2026_07_09_100002_add_rating_check_constraint_to_reviews_table.php`
- `2026_07_09_110000_create_wishlist_items_table.php` (mới)
- `2026_07_09_120000_add_variant_id_to_cart_items_table.php` (mới — đổi unique constraint `cart_items`, nhớ kiểm tra không có data cũ vi phạm constraint mới trước khi migrate lên môi trường có data thật)

## 🟠 Đã code xong hôm nay, chưa verify runtime (cần Docker + trình duyệt thật)

1. **Wishlist thật** — nút tim trên PDP (`#pdWishBtn`) trước đây chỉ toggle CSS class, giờ gọi `POST /api/v1/wishlist/toggle` thật (guest qua `X-Session-ID` localStorage, sẵn `merge()` cho login sau). Trang `/tai-khoan/yeu-thich` (`vi`) / `/account/wishlist` (`en`) — trước đây không có route nào — giờ load thật, có `noindex`.
2. **Cart thật** — nút "Thêm vào giỏ hàng" (`#pdAddBtn`) trước đây chỉ đổi chữ 2.2s rồi thôi, giờ gọi `POST /api/v1/cart/items` thật, gửi kèm `variant_id` từ input ẩn `#pdVariantId` (bộ chọn màu/size đã có sẵn). Trang `/gio-hang` (`vi`) / `/cart` (`en`) — route mới, đổi số lượng/xoá gọi API thật. Thêm icon giỏ hàng + badge số lượng vào navbar (trước đây thiếu hoàn toàn).
3. **Cart hỗ trợ variant** — `cart_items` thêm `product_variant_id`, unique theo `(cart_id, product_id, product_variant_id)` — 1 sản phẩm 2 màu/size là 2 dòng riêng, tồn kho check theo variant khi có chọn. `CartItemResource` trả `variant_label` (tái dùng `ProductVariant::combination_label` có sẵn, trước đó không nơi nào gọi tới nên chưa ai biết nó hoạt động đúng hay không).
4. **Fix bug locale y hệt review** — `WishlistItemResource` và `CartItemResource` trước đây chỉ đọc field gốc (`products.name/slug/price`), không đọc `ProductTranslation` → trang EN sẽ hiện tên tiếng Việt + link 404 (slug sai locale). Đã sửa cả 2, theo `?locale=` query param frontend gửi kèm.

## 🟡 Đã phát hiện, CHƯA fix — cần quyết định hoặc làm tiếp (bổ sung — xem thêm mục 🟡 phía dưới của session trước)

12. **Nút "Thanh toán" trên trang giỏ hàng để `disabled`, có ghi chú "đang hoàn thiện"** — vì `orders` API bắt buộc `auth:sanctum` (`routes/api.php`) trong khi `orders.user_id` là `nullable` (rõ ràng thiết kế gốc định cho guest order) và storefront chưa có trang đăng nhập. Cần quyết định: mở `orders` cho guest (giống Cart/Wishlist) hay bắt buộc login trước khi checkout — xem lại câu trả lời "cách chuyên nghiệp nhất" đã trao đổi (khuyến nghị: guest checkout, login là tuỳ chọn).
13. **Không có coupon/mã giảm giá nào cả** — đã bỏ hẳn ô "Mã giảm giá" khỏi trang giỏ hàng thật (mockup có nhưng không backend) để tránh dựng thêm 1 nút giả.
14. **`Cart::total()` / `CartItem::subtotal` tính theo giá gốc (vi), không qua `ProductTranslation`** — khác với field hiển thị (`price`/`name` đã fix locale). Rủi ro thấp vì seed data hiện tại EN không set giá riêng (`price: null` trong `ProductTranslation`) nên luôn fallback đúng — nhưng nếu sau này admin set giá USD riêng cho bản EN, tổng tiền có thể lệch so với giá từng dòng hiển thị. Chưa sửa vì chưa có nhu cầu thật.
15. `resources/views/partials/cart-drawer.blade.php` **vẫn còn rỗng 0 byte, không dùng** — quyết định làm cart dạng trang riêng (`/gio-hang`) theo đúng mockup `Z/cart.html` thay vì drawer trượt, nên file này giờ là dead file thật sự, có thể xoá khi dọn dẹp (không xoá vội, để anh xác nhận).
16. `layouts/checkout.blade.php` + `layouts/auth.blade.php` — vẫn rỗng 0 byte như session trước, chưa động tới (phụ thuộc quyết định mục #12).

## 🟢 SEO nice-to-have (so với chuẩn Shopee) — không bắt buộc, làm khi có thời gian

## 🔴 Cần chạy khi Docker lên — 3 migration mới đang chờ

```bash
docker compose exec php-fpm php artisan migrate
docker compose restart horizon
```

- `2026_07_09_100000_create_review_images_table.php`
- `2026_07_09_100001_add_email_to_reviews_table.php`
- `2026_07_09_100002_add_rating_check_constraint_to_reviews_table.php` (raw SQL `CHECK (rating BETWEEN 1 AND 5)`)

## 🟠 Đã code xong, chưa verify runtime (cần Docker + trình duyệt thật)

1. **Hệ thống Product Reviews** — guest submit (form trên PDP, không cần login vì storefront chưa có trang đăng nhập) → `is_approved=false` → admin duyệt trong Filament (`Content → Reviews`, nút Create đã mở lại) → `ReviewObserver` sync JSON-LD `AggregateRating` cho **cả vi + en** (đã fix bug locale cũ). Rating badge đã thêm ngay dưới tên sản phẩm trên PDP.
2. **`MerchantReturnPolicy`** trong JSON-LD `Product.offers` — đọc từ Business Profile → tab "Return Policy" mới. Đã nối code (`JsonldService::merchantReturnPolicy()`, gắn cả 4 nhánh `buildOffersPayload()`), **chưa thấy JSON thật xuất ra** — verify bằng:
   ```php
   // php artisan tinker
   $p = App\Models\Product::first();
   app(App\Services\Seo\JsonldService::class)->syncForModel($p, 'vi');
   App\Models\Seo\JsonldSchema::where('model_id',$p->id)->where('schema_type','Product')->first()->payload['offers'];
   ```
3. **Navbar search** — nút "Tìm kiếm" giờ mở overlay + gợi ý sống (ảnh+tên+brand, tối đa 8) qua `ProductController::autocomplete()`, Enter/xem tất cả → PLP thật (`ProductSearchService`, Meilisearch). `/tim-kiem`, `/search` (stub cũ trả text thô) đổi thành redirect sang PLP.
4. **Fix fallback redirect 404** (`routes/web.php` fallback) — `/products/{slug}` v.v. giờ redirect đúng `/en/...` thay vì `/vi/...` (trước đó luôn 404 vì VI dùng `san-pham` không phải `products`). Chưa curl-test Location header thật.
5. Footer: shipping carriers + payment methods (logo động từ Business Profile, kéo-thả sắp xếp) + legal info (tên công ty/MST/địa chỉ/SĐT/email). Mega menu cột 1: label + sản phẩm mới do admin chọn tay (fallback 4 mới nhất). Mega menu cột 4: thêm nhóm "Liên hệ" (địa chỉ/SĐT/email từ Business Profile).
6. Business Profile: field `country` đổi từ text tự do → dropdown search ISO 3166-1 (`config/countries.php` mới).

## 🟡 Đã phát hiện, CHƯA fix — cần quyết định hoặc làm tiếp

1. **Duplicate query trong `ProductController::autocomplete()`** — WHERE clause viết trùng 2 lần (1 cho list, 1 cho `$total`). Fix đề xuất: `clone()` query builder trước `limit()`. Chưa áp dụng.
2. **`Api\V1\Product\ProductController@show`** (`GET /api/v1/products/{slug}`) — cùng loại bug locale-slug đã fix cho Review (`findActiveBySlug` chỉ query `products.slug`, không nhận slug bản dịch EN) — **chưa sửa**, vì API này chưa có client thật dùng (chờ Nuxt/mobile). Cần sửa trước khi client thật kết nối.
3. **`robots.txt` sai domain** — `Sitemap: https://casambi.vn/sitemap.xml` (không phải domain CacyLinen, có vẻ copy sót từ project khác). Chưa sửa.
4. **llms.txt song ngữ chưa có hướng chốt** — 3 phương án đã đề xuất (A: content-negotiate theo `Accept-Language`, B: bỏ bản EN chỉ giữ 1 file đúng spec, C: giữ nguyên). Anh chưa chọn.
5. **`Manufacturer.country`** vẫn là `TextInput` tự do (chỉ có hint text "ISO 3166-1 alpha-2"), chưa đổi thành dropdown như đã làm cho `BusinessProfile.country`.
6. **Mega menu cột 4** — nhóm "Héritage & Savoir-faire" + "Dịch vụ" vẫn 100% hardcode tiếng Việt trong `header.blade.php`, không đọc từ Setting/Mega Menu Setting như cột 1 đã sửa.
7. **Link nội bộ không locale-prefix** (`url('/about')`, `url('/contact')`, `url('/care-guide')` ở header/footer) — luôn tốn 1 hop redirect 301 qua fallback route mỗi lần user bấm. Phát hiện phụ khi debug fallback, chưa sửa.
8. **Sản phẩm 0 ảnh → JSON-LD tự mất field `image`** → mất điều kiện Google rich result. Chưa có validation/cảnh báo trong Filament ProductResource chặn lưu sản phẩm thiếu ảnh.
9. **Chưa có trang đăng nhập/đăng ký cho storefront** (chỉ có API `/api/v1/auth/*`, không có UI) — lý do phải mở review cho guest. Nếu sau này build trang login thật, nên xét lại có nên bắt buộc đăng nhập để review (chống spam tốt hơn).
10. **Chưa rate-limit / chống spam form review công khai** — chủ động bỏ qua theo yêu cầu, làm sau khi cần.
11. Ảnh review: giới hạn 4MB/ảnh nhưng không giới hạn tổng dung lượng 5 ảnh/request — rủi ro nhỏ do chưa có rate-limit đi kèm (mục #10).

## 🟢 SEO nice-to-have (so với chuẩn Shopee) — không bắt buộc, làm khi có thời gian

1. `OfferShippingDetails` (thời gian/phí giao hàng) trong JSON-LD `Offer`.
2. `review` (mảng review text thật) trong JSON-LD `Product` — hiện chỉ có `aggregateRating` tổng hợp, chưa có từng review riêng.
3. `gtin`/`mpn` — chấp nhận được vì brand tự sản xuất, không có mã vạch toàn cầu.

---



## 🟠 JSON-LD trang chủ — đã fix 2 lỗi, còn lại cần làm

Đã fix trong `app/Services/Seo/BusinessJsonldService.php`: thêm `contactPoint` (Organization) và liên kết `publisher` từ `WebSite` → `@id` Organization. Pint pass. Chưa verify runtime (không kết nối được `postgres`/`redis` từ shell host, chỉ resolve trong Docker network).

1. **Flush cache `business_jsonld_schemas_{vi,en}` trong container** (fix code chưa lên hiệu lực vì Redis cache 24h) — chưa chắc lệnh tinker chạy từ host đã flush được, cần chạy lại đúng chỗ:
   ```bash
   docker compose exec app php artisan tinker --execute="app(App\Services\Seo\BusinessJsonldService::class)->flushCache();"
   ```
2. **Verify runtime sau flush** — view-source trang chủ (`/vi/`, `/en/`), kiểm tra `<script type="application/ld+json">` có `contactPoint` và `WebSite.publisher.@id` đúng; chạy qua Google Rich Results Test.
3. **Kiểm tra dữ liệu `BusinessProfile` thật** (Filament → Business Profile):
   - `logo_path`: ảnh phải ≥112×112px, tỉ lệ vuông (yêu cầu Google Knowledge Panel).
   - `social_links`: điền đủ profile mạng xã hội thật để `sameAs[]` có giá trị (hiện code chỉ render nếu có data).
4. **LocalBusiness thiếu `priceRange`** — nên bổ sung field này vào `BusinessProfile` + `localBusiness()` builder.
5. **`@type: LocalBusiness` đang generic** — nên đổi sang subtype cụ thể hơn (`Store` hoặc `HomeGoodsStore`) để entity chính xác hơn cho ngành bán lẻ linen.
6. **`FAQPage` trên trang chủ không còn tạo rich snippet** — Google đã ngừng hỗ trợ FAQ rich result cho site thương mại từ 8/2023. Vẫn có giá trị AEO/AI Overview nhưng đừng kỳ vọng SERP snippet.
7. **Chưa có test cho `BusinessJsonldService`** — cân nhắc viết feature test cho `getSchemas()` (Organization/WebSite/LocalBusiness/FAQPage) khi test suite chạy được lại (xem mục nợ kỹ thuật #1 bên dưới).

## 🟡 Nợ kỹ thuật (chưa fix — mang từ trước)

1. **Test suite chết trên sqlite** — `php artisan test` fail toàn bộ (0 assertion): migration `blog_posts drop column slug` không tương thích sqlite in-memory.
2. **Pint fail có sẵn** ở `ProductResource/Pages/*`, `FilterGroupResource`, `ProductSeeder` (aligned assignment style).
3. **phpstan/larastan chưa cài** dù CLAUDE.md ghi là có (`vendor/bin/phpstan` không tồn tại).
4. **ERD `doc/databse.md` thiếu bảng filter** (`filter_groups`, `filter_values`, `product_filter_values`) — thêm khi cập nhật ERD, kèm cột `filter_groups.type`.
5. **Sort dropdown PLP/category** chỉ là UI, chưa có server-side sort (Meilisearch đã có `sortableAttributes` sẵn: price, effective_price, created_at, name).
6. **Brand filter chưa có trong UI modal** — chọn brand hiện phải qua `?brand=` tay, brand đi SQL path thay vì Meilisearch (chưa được index).
7. **`layouts/checkout.blade.php` + `layouts/auth.blade.php` rỗng 0 byte, chưa trang nào dùng** — khi build thật, nhớ include `partials.seo-head` + đăng ký favicon view composer.
