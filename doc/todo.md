# TODO

> Cập nhật: 2026-07-06 — session: Size Guide song ngữ (admin + PDP modal + trang tổng), điều tra admin login fail, phát hiện DB dev bị wipe + Docker engine treo

## 🔴 KHẨN — Runtime đang hỏng (2026-07-06)

1. **Docker Desktop engine API bị treo** — container vẫn chạy (site vẫn serve) nhưng mọi lệnh `docker` đứng im vô hạn. → **Restart Docker Desktop** (quit từ system tray, mở lại).
2. **DB dev đã bị `php artisan test` wipe sạch** (trước khi phpunit.xml được fix `force="true"` ở commit `bfb0af5`) — mất toàn bộ users/products/categories/blog. Đây là lý do đăng nhập `admin@example.com` fail (API login trả 422 — user không tồn tại). Nếu có backup thì restore; không thì re-seed + nhập lại data.
3. Sau khi restart Docker Desktop, entrypoint php-fpm tự chạy `migrate --force` (bao gồm migration size_guides mới). Rồi chạy:
   ```bash
   docker compose exec php-fpm php artisan db:seed   # tạo lại admin@example.com/password + size guide mẫu
   docker compose restart horizon
   docker compose exec php-fpm php artisan meilisearch:configure
   docker compose exec php-fpm php artisan scout:import "App\Models\Product"   # (chỉ có ý nghĩa sau khi có lại data sản phẩm)
   ```
4. **⚠️ PostgreSQL 18 cài thẳng trên Windows** (service `postgresql-x64-18`) đang chiếm `0.0.0.0:5432` — mọi kết nối `localhost:5432` từ host đi vào DB này chứ KHÔNG phải postgres Docker. Nên stop/disable service nếu không dùng, tránh debug nhầm DB.

## 🟢 Size Guide song ngữ — code xong (session 2026-07-06), chờ runtime

Admin quản lý nhiều loại hướng dẫn size (Content → Size Guides), song ngữ vi/en, RichEditor có nút Table (Filament 5). Gán per-product qua Select ở tab General. PDP hiện link mở modal (fallback link sang trang tổng khi chưa gán); trang tổng `/vi/huong-dan-size` + `/en/size-guide` (redirect 301 từ `/size-guide` cũ — hết 404 link header/footer).

**Test sau khi migrate + seed:**
| # | Test | Kỳ vọng |
|---|---|---|
| 1 | Admin → Content → Size Guides | Thấy guide mẫu "Áo nữ" (vi+en), bảng số đo edit được bằng toolbar Table |
| 2 | Gán guide vào 1 product → mở PDP | Link "Hướng dẫn chọn size →" mở modal đúng bảng, đóng bằng ×/overlay/Esc |
| 3 | Product chưa gán guide | Link trỏ sang trang tổng thay vì modal |
| 4 | `/vi/huong-dan-size`, `/en/size-guide`, `/size-guide` | 2 trang render guide active; link cũ redirect 301 |

## 🔴 Checklist khi bật Docker (làm theo thứ tự)

Toàn bộ code đã xong, chỉ chờ runtime. Các commit liên quan: `bf24fb8` (Meilisearch search thuộc tính), `2a57142` (filter_groups.type), `8598cb1` (wire filter modal), + batch admin Pages Setting **chưa commit**.

```bash
# 1. Migration — filter_groups.type ('text'|'color') + backfill group có color_hex
docker compose exec app php artisan migrate

# 2. Horizon — bắt buộc restart, worker là process dài hạn không tự reload PHP.
#    Bỏ qua = scout sync fail âm thầm, index nhận data schema cũ.
docker compose restart horizon

# 3. Đẩy searchableAttributes mới (filter_value_names_vi/en) lên Meilisearch
docker compose exec app php artisan meilisearch:configure

# 4. Re-index để document có filter_value_names_*
docker compose exec app php artisan scout:import "App\Models\Product"
```

### Test nhanh sau khi chạy xong

| # | Test | Kỳ vọng |
|---|---|---|
| 1 | Search PLP bằng tên thuộc tính thuần ("trắng", "100% linen") | Ra sản phẩm gắn value đó dù keyword không có trong tên/mô tả; match tên sản phẩm vẫn rank cao hơn |
| 2 | Admin → Filter Groups | Group "Màu sắc" có badge Loại = "Màu sắc (swatch)"; group khác không thấy ColorPicker trong Values |
| 3 | PLP `/vi/products` → bấm "Lọc" | Group màu hiện swatch tròn đúng màu, group thường hiện pill; tick + kéo giá + "Xem kết quả" → URL `?mau-sac=den&min_price=...`, grid lọc đúng; reload vẫn tick, nút hiện "Lọc (n)" |
| 4 | Trang category bất kỳ → "Lọc" | Như trên, thanh giá scoped theo category |
| 5 | Admin → Setting → Pages Setting | Thấy 2 card: Landing Page (mở cùng tab), Shop Setting (mở tab mới); Landing Page biến mất khỏi sidebar |
| 6 | Shop Setting: điền H1/P/ảnh hero + Tab Title → Lưu | `/vi/products` banner đổi H1, hiện đoạn P + ảnh cột trái; tab title đổi, KHÔNG bị đúp "— CacyLinen" |

## 🟠 Chưa commit (batch admin Pages Setting — session 2026-07-04)

| File | Thay đổi |
|---|---|
| `app/Filament/Pages/PagesSetting.php` (mới) | Hub gom page settings về 1 mục sidebar, card registry qua `getCards()` (hỗ trợ `newTab`) |
| `resources/views/filament/pages/pages-setting.blade.php` (mới) | Lưới card, plain CSS + Filament theme vars (pattern developer.blade.php), dark mode |
| `app/Filament/Pages/ShopSetting.php` (mới) + `shop-setting.blade.php` (mới) | Form 2 section: Hero (H1, P, ảnh — lưu `extra['shop']`) và SEO/Tab Title (đọc/ghi key cũ `extra.product_catalog_*`) |
| `app/Filament/Pages/LandingSetup.php` | Ẩn khỏi sidebar (`$shouldRegisterNavigation = false`), truy cập qua card hub |
| `app/Filament/Resources/BusinessProfileResource.php` | Bỏ section "Product Catalog" (chuyển sang Shop Setting, cùng key lưu) |
| `app/Http/Controllers/Web/ProductController.php` | Đọc `extra['shop']` → `$shopHero`; **fix bug** fallback title đúp "— CacyLinen" |
| `resources/views/pages/product/index.blade.php` | Banner PLP dùng `$shopHero` (H1/P/ảnh), fallback y hệt cũ khi chưa điền |

**Lưu ý data:** nếu `product_catalog_title` từng được điền kèm "— CacyLinen" (placeholder cũ gợi ý sai) thì tab title vẫn đúp — mở Shop Setting xoá hậu tố trong ô Tab Title.

## 🟠 SEO/GEO: JSON-LD ra HTML — GĐ1–4 code xong, còn verify (xem `doc/seo.md`)

Audit 2026-07-04 phát hiện pipeline JSON-LD backend không render ra HTML → đã fix cùng ngày: partial `seo-head` (JSON-LD + canonical + hreflang + OG/Twitter, cắm vào layout), `<html lang>` theo locale, PLP/category/blog-index có CollectionPage/ItemList/BreadcrumbList runtime + canonical bỏ query filter. Home tự có Organization/WebSite/LocalBusiness/FAQPage (BusinessJsonldService có sẵn). **Còn lại GĐ5 khi Docker bật:** view-source 5 loại trang + Rich Results Test + nhớ clear responsecache. Chi tiết: `doc/seo.md`.

## 🟡 Nợ kỹ thuật (chưa fix)

1. **Test suite chết trên sqlite** — `php artisan test` fail toàn bộ (0 assertion) từ trước: migration `blog_posts drop column slug` không tương thích sqlite in-memory. Checklist "php artisan test passes" hiện vô nghĩa cho tới khi fix.
2. **Pint fail có sẵn** ở `ProductResource/Pages/*`, `FilterGroupResource`, `ProductSeeder` (aligned assignment style) — có từ trước các session này.
3. **phpstan/larastan chưa cài** dù CLAUDE.md ghi là có (`vendor/bin/phpstan` không tồn tại).
4. ~~**`SCOUT_DRIVER` không set trong `phpunit.xml`**~~ — **ĐÃ FIX** (commit `bfb0af5`): thêm `SCOUT_DRIVER=null` + `force="true"` toàn bộ `<env>` (docker-compose inject `.env` thành OS env var nên `<env>` không force bị thua → test từng chạy vào pgsql thật và wipe DB dev).
5. **ERD `doc/databse.md` thiếu các bảng filter** (`filter_groups`, `filter_values`, `product_filter_values`) — thêm khi cập nhật ERD, nhớ kèm cột `filter_groups.type` mới. (Bảng `size_guides`/`size_guide_translations` đã bổ sung 2026-07-06.)
6. **Sort dropdown PLP/category** chỉ là UI, chưa có server-side sort (Meilisearch đã có `sortableAttributes` sẵn: price, effective_price, created_at, name).
7. **Brand filter chưa có trong UI modal** — chọn brand hiện phải qua `?brand=` tay, và brand đi SQL path thay vì Meilisearch (brand chưa được index).
8. **`layouts/checkout.blade.php` + `layouts/auth.blade.php` là file rỗng 0 byte, chưa trang nào dùng** — khi build layout checkout/auth thật, nhớ include `partials.seo-head` + đăng ký favicon view composer (`AppServiceProvider::registerViewComposers` hiện chỉ gắn vào `layouts.app`), không thì favicon/tab title/OG sẽ lệch với phần còn lại của site.
