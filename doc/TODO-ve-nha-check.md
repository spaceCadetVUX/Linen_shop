# TODO — Về nhà kiểm tra

> Ghi lại lúc 2026-07-02, máy hiện tại không start được services để test. Checklist dưới đây làm khi có máy chạy được Docker/Meilisearch.

---

## 1. Kiểm tra Meilisearch

**Mục tiêu:** xác nhận Meilisearch đã chạy + đã index sản phẩm chưa, để làm filter (màu sắc, giá...) qua search engine thay vì raw SQL.

### Bước 1 — Container có chạy không
```bash
docker compose up -d meilisearch
docker compose ps meilisearch
```
Service đã khai trong `docker-compose.yml` (image `getmeili/meilisearch:v1.7`, port `7700:7700`, volume `meilisearch_data`) — chỉ chưa test start được trên máy hiện tại.

### Bước 2 — Health check
```bash
curl http://127.0.0.1:7700/health
# kỳ vọng: {"status":"available"}
```

### Bước 3 — Tạo `.env` thật (hiện repo chỉ có `.env.example`, chưa có `.env`)
Copy `.env.example` → `.env`, đảm bảo có:
```bash
MEILISEARCH_HOST=http://meilisearch:7700   # hoặc http://127.0.0.1:7700 nếu chạy app ngoài Docker
MEILISEARCH_KEY=
SCOUT_DRIVER=meilisearch
```
Đồng thời set `QUEUE_CONNECTION` và `CACHE_STORE` (mặc định trong `config/queue.php`/`config/cache.php` là `redis` — nếu Redis local chưa cài client PHP, tinker/artisan sẽ lỗi `Class "Redis" not found`, gặp nhiều lần trong session hôm nay). Nếu máy nhà có Redis chạy thật thì để mặc định `redis` là đúng nhất — không cần đổi.

### Bước 4 — Index dữ liệu
```bash
php artisan scout:import "App\Models\Product"
```
`Product::toSearchableArray()` (app/Models/Product.php) đã có sẵn, index sẵn: `name`, `sku`, `short_description`, `category_ids`, `categories`, `price`, `sale_price`, `stock_quantity`, `is_active`, `created_at`. **Chưa index** `color_hex`/filter values — cần bổ sung nếu định chuyển filter màu sang Meilisearch.

### Bước 5 — Test search thử
```bash
php artisan tinker --execute="print_r(App\Models\Product::search('linen')->raw());"
```

---

## 2. Bối cảnh — tại sao đang bàn Meilisearch

Đang tính hướng "pro" cho filter sản phẩm (màu sắc, giá, category, brand) — thay vì query SQL `whereHas` nhiều tầng (chậm dần khi catalog lớn), dùng Meilisearch filter/facet API (hỗ trợ range số + facet count có sẵn). Đây là bước **chưa làm**, mới dừng ở mức bàn hướng đi. Việc trước mắt (đã làm xong) chỉ là filter theo `FilterGroup`/`FilterValue` qua SQL thường.

---

## 3. Tóm tắt đã làm trong session hôm nay (2026-07-02)

### Đã xong, đã test:
- **`resources/views/components/product/card.blade.php`** — fix nối data thật (trước là mockup field ảo: `thumbnail` string, `badge`, `swatches`...). Test cả VI/EN.
- **`resources/views/components/ui/breadcrumb.blade.php`** — component breadcrumb dùng chung, mới tạo.
- **`resources/views/components/product/grid.blade.php`** — component grid dùng chung (card + pagination + empty-state), tránh viết lặp giữa các trang PLP.
- **`resources/views/pages/category/show.blade.php`** — trang category + sản phẩm, nối thật `CategoryController::show()`.
- **`resources/views/pages/category/index.blade.php`** — danh sách category, nối thật `CategoryController::index()`. Có thêm CSS `.cat-index-*` mới trong `app.css` (trang này chưa từng có mockup).
- **`resources/views/pages/product/index.blade.php`** — trang "Tất cả sản phẩm" (PLP), nối thật `ProductController::index()`.
- **Xóa file orphan:** `pages/shop/index.blade.php`, `pages/shop/category.blade.php` (đã có bản thay thế thật).
- **Dọn tàn dư KNXStore cũ:** xóa hẳn `SolutionController.php` + route `giai-phap/*`/`solutions/*` + entry sitemap liên quan. Sửa text fallback SEO (`ProductController`, `CategoryController`, `HomeController`) từ "KNX/DALI-2/Casambi" sang LINNÉ.
- **Fix bug mismatch key trong `app/Models/Setting.php`:** `site_tagline_en` giờ đọc đúng `extra.tagline_en`, `default_og_image` đọc đúng `extra.og_image` (trước đó 2 field admin này không bao giờ có tác dụng).
- **Thêm tab "Page Fallbacks" trong `BusinessProfileResource.php`** — admin tự chỉnh title/description fallback cho trang Product Catalog + Category Index, không cần sửa code.
- **Filter màu sắc:** migration `add_color_hex_to_filter_values_table` (cột `color_hex` varchar(7) nullable trên `filter_values`), `ColorPicker` trong `FilterGroupResource.php`. Đã test lưu/đọc `#e63946` thành công.
- Chạy thêm các migration pending liên quan (product show_price/show_original_price, filter_groups/filter_values/product_filter_values/slug) — **KHÔNG** chạy migration `add_sort_order_and_rich_content_to_blog_categories` vì lỗi quyền Postgres `must be owner of table blog_category_translations` (cần fix owner DB riêng, chưa đụng).

### Còn thiếu / chưa làm (theo độ ưu tiên gợi ý):
1. **PDP thật (`pages/product/show.blade.php`)** — chưa build. `pages/product/detail.blade.php` vẫn là mockup gốc (giữ lại làm tham chiếu thiết kế — gallery, variant-selector, accordion). 2 component liên quan còn rỗng: `components/product/gallery.blade.php`, `components/product/variant-selector.blade.php`.
2. **`pages/blog/show.blade.php`** — chưa build, `BlogController::show()` phức tạp nhất (nhiều quan hệ: author, category, tags, GEO/FAQ).
3. **`pages/page/about.blade.php`, `pages/page/show.blade.php`** — chưa build. Mockup `pages/static/about.blade.php` có sẵn nhưng ~95% là copy tĩnh (không data-driven), chỉ cần dời file + gắn seo/breadcrumb ở đầu.
4. **Filter UI thật cho PLP/category** — hiện `plp-fmodal` (màu/size/giá) vẫn là mockup tĩnh 100%, chưa đọc `$filterGroups`/`$brands` thật. Cũng chưa render swatch từ `color_hex` mới thêm.
5. **Price range filter** — chưa làm, đã bàn hướng (whereBetween đơn giản, hoặc Meilisearch facet nếu muốn "pro").
6. **`cart/`, `checkout/`, `account/*`** — chưa có Controller nào cả, mockup thuần frontend.
7. Leftover nhỏ: `about-fq-role` trong `pages/static/about.blade.php` có text giỡn "Người code trang này muốn trĩ" — nhớ đổi trước khi lên thật.
8. `Setting::get('meta_description')` — chưa có field admin tương ứng (không phải bug, chỉ là chưa build field), fallback description trang chủ luôn dùng tagline/text cứng.
9. File `pages/shop/*` đã xóa xong, không còn tồn đọng.

---

## 4. Trạng thái Git

**Đã tự commit trong lúc làm** (không phải Claude commit, bạn đã commit thủ công song song) — `git log` gần nhất:
```
9c7c608 remove unused                        ← xóa pages/shop/*, SolutionController, routes Solutions
0fffcd8 setting fix                          ← Setting.php key mismatch fix, HomeController/ProductController/CategoryController fallback text
4080ed5 add fallback edit + remove old assets ← BusinessProfileResource tab "Page Fallbacks"
7b053df cat                                  ← category/show.blade.php, category/index.blade.php
f165d5e mapping product card                 ← card.blade.php fix, grid.blade.php, breadcrumb.blade.php, product/index.blade.php
bcdadbd mapping Editorial Grid
```

**Còn CHƯA commit** (working tree hiện tại, `git status --short`):
```
 M app/Filament/Resources/FilterGroupResource.php   ← ColorPicker cho filter màu
 M app/Models/FilterValue.php                       ← color_hex vào fillable
?? database/migrations/2026_07_02_094447_add_color_hex_to_filter_values_table.php
?? doc/TODO-ve-nha-check.md                         ← file này
```

Nhớ `git status`/`git diff` review lại phần filter màu trước khi commit khi về nhà.
