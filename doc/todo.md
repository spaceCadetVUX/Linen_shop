# TODO

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
