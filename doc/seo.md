# Kế hoạch SEO/GEO — Nối pipeline JSON-LD ra HTML

> Lập: 2026-07-04. Trạng thái: **GĐ1–4 ĐÃ CODE XONG cùng ngày** — còn GĐ5 (verify runtime, cần Docker).
> Bối cảnh: audit trang shop phát hiện toàn bộ pipeline JSON-LD backend không render ra HTML.
>
> **Điều chỉnh khi triển khai:**
> - GĐ2 (BreadcrumbList qua component) **bỏ** — DB template đã sinh BreadcrumbList sẵn cho product/category/blog_post (`JsonldService::$typeMap`), làm ở component sẽ đúp. Thay bằng: BreadcrumbList build runtime chỉ trên trang không có schema DB (PLP, blog index).
> - GĐ4.1 (Organization/WebSite) **có sẵn từ trước** — `BusinessJsonldService` đã build đủ Organization + WebSite (kèm SearchAction) + LocalBusiness + FAQPage, HomeController đã pass `$businessSchemas` vào view; partial `seo-head` merge cả `$jsonldSchemas` + `$businessSchemas` là xong.
> - JSON-LD encode dùng `JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP` (không dùng `JSON_UNESCAPED_SLASHES` như kế hoạch ban đầu) — content admin nhập chứa `</script>` sẽ breakout khỏi thẻ script nếu không escape `<` `>`.
>
> **File đã sửa:** `partials/seo-head.blade.php` (mới), `layouts/app.blade.php`, `ProductController::index`, `CategoryController::show`, `BlogController::index`.

---

## 1. Hiện trạng (audit 2026-07-04)

### Backend — ĐÃ CÓ, chạy tốt

- Bảng `jsonld_schemas` + `JsonldTemplate`, jobs `SyncJsonldSchema` / `SyncSitemapEntry` / `SyncLlmsEntry` trên queue `seo`, observers dispatch mỗi lần save Product / Category / BlogPost.
- `JsonldService::getActiveSchemas($model, $locale)` trả payload sẵn.
- Controllers đã tính đủ biến cho view:

| Trang | `jsonldSchemas` | `seoMeta` | `alternateUrls` | `ogType`/`fallbackImage` |
|---|---|---|---|---|
| PDP (`ProductController::show`) | ✅ từ DB (có logic strip price theo `show_price`) | ✅ | ✅ | ✅ |
| Category (`CategoryController::show`) | ✅ từ DB | ✅ | ✅ | ✅ |
| Blog post (`BlogController::show`) | ✅ từ DB | ✅ | ✅ | ✅ `article` |
| Blog category | ✅ từ DB | ✅ | ✅ | ✅ |
| **PLP** (`ProductController::index`) | ❌ `[]` hardcode | null | ✅ | ✅ |
| Blog index | ❌ `[]` hardcode | — | ✅ | ✅ |
| Home | ❌ không có | ✅ | ✅ | ✅ |

### Frontend Blade — LỖ HỔNG

`layouts/app.blade.php` `<head>` chỉ có: title, meta description, favicon, fonts, css. Grep toàn bộ `resources/views`: **không tồn tại chỗ nào output `<script type="application/ld+json">`**. Mọi thứ backend sản xuất đều bị view vứt bỏ.

| Thẻ | Hiện trạng |
|---|---|
| JSON-LD | ❌ Không render — mọi trang |
| `<link rel="canonical">` | ❌ Không có — URL filter `?mau-sac=den&page=2` index trùng nội dung |
| `hreflang` vi/en | ❌ `alternateUrls` chỉ dùng cho nút đổi ngôn ngữ ở header |
| OG / Twitter Card | ❌ Controllers tính `fallbackImage`/`ogType` rồi không ai render |
| `<html lang>` | ❌ Hardcode `"vi"` kể cả trang `/en/` |

---

## 2. Kế hoạch triển khai — thứ tự BẮT BUỘC 1 → 2 → 3 (4 độc lập)

### Giai đoạn 1 — Đường ống ra HTML (nền tảng, mọi trang hưởng ngay)

| # | Việc | File |
|---|---|---|
| 1.1 | Partial `seo-head` render: **(a)** loop `$jsonldSchemas` → `<script type="application/ld+json">` từng schema, `json_encode(..., JSON_UNESCAPED_UNICODE \| JSON_UNESCAPED_SLASHES)`; **(b)** canonical (mặc định `url()->current()` bỏ query, cho phép controller override qua `$canonicalUrl`); **(c)** hreflang vi/en + `x-default` từ `$alternateUrls`; **(d)** OG (`og:title/description/image/type/url/locale`) + `twitter:card` từ `seoMeta` → fallback `fallbackTitle`/`fallbackDescription`/`fallbackImage`/`ogType` | `resources/views/partials/seo-head.blade.php` (mới) |
| 1.2 | Cắm partial vào `<head>`; sửa `<html lang="vi">` → `{{ $locale ?? app()->getLocale() }}` | `resources/views/layouts/app.blade.php` |
| 1.3 | Không đụng controller — PDP / category / blog post tự sống lại schema có sẵn trong DB | — |

**Nghiệm thu GĐ1:** view-source PDP thấy Product schema + canonical + hreflang + OG. Biến nào thiếu ở trang nào thì partial phải im lặng bỏ qua (null-safe), không nổ lỗi.

### Giai đoạn 2 — BreadcrumbList tự động (1 chỗ, phủ mọi trang)

| # | Việc | File |
|---|---|---|
| 2.1 | `x-ui.breadcrumb` đã nhận mảng items ở mọi trang → tự render BreadcrumbList schema từ chính items (position = index+1, item = url tuyệt đối; item cuối không cần url). Không sửa từng trang. | `resources/views/components/ui/breadcrumb.blade.php` |

**Nghiệm thu:** mọi trang có breadcrumb hiện BreadcrumbList hợp lệ, không đúp với BreadcrumbList từ DB (nếu template DB đã có loại này cho PDP → bỏ một trong hai, ưu tiên bản component).

### Giai đoạn 3 — Trang shop (PLP) + category

| # | Việc | File |
|---|---|---|
| 3.1 | PLP: build runtime `CollectionPage` + `ItemList` từ `$products` (position offset theo trang: `($page-1)*24 + $i + 1`, url + name mỗi item) — thay `'jsonldSchemas' => []`. Không lưu DB (thay đổi theo trang/filter). | `ProductController::index` |
| 3.2 | Canonical PLP: **bỏ toàn bộ query filter** (`{group_slug}`, `min_price`, `max_price`, `q`, `brand`), **giữ `page`** — tránh index n! tổ hợp filter. | `ProductController::index` (+ partial 1.1 nhận `$canonicalUrl`) |
| 3.3 | Category page: bổ sung ItemList runtime (schema category từ DB giữ nguyên) + canonical rule y hệt 3.2. | `CategoryController::show` |

**Nghiệm thu:** `/vi/products?mau-sac=den&page=2` có canonical `/vi/products?page=2`; ItemList đúng 24 items với position nối trang.

### Giai đoạn 4 — Site-level GEO (độc lập, làm sau cùng)

| # | Việc | File |
|---|---|---|
| 4.1 | Homepage: `Organization` (từ `BusinessProfile`: name, logo, address, social `sameAs`) + `WebSite` kèm `SearchAction` (sitelinks searchbox → `/vi/products?q={search_term_string}`). | `HomeController` |
| 4.2 | Blog index: `CollectionPage` + ItemList bài viết (đang `[]`). | `BlogController::index` |

### Giai đoạn 5 — Verify (cần Docker bật)

1. View-source 5 loại trang: home / PLP / PDP / category / blog post — đủ schema, **không schema đúp** (chú ý BreadcrumbList GĐ2 vs template DB).
2. Dán HTML vào Google **Rich Results Test** + **Schema.org validator** — 0 error, warning chấp nhận được.
3. `spatie/laravel-responsecache` đang cache full-page — **phải clear cache** sau khi deploy mới thấy thay đổi.
4. Check `php artisan sitemap:generate` + `llms:generate` vẫn chạy bình thường (không liên quan trực tiếp nhưng cùng pipeline SEO).

---

## 3. Khối lượng ước tính

| Giai đoạn | Thời gian |
|---|---|
| GĐ1 + GĐ2 | ~1–2 giờ |
| GĐ3 | ~1 giờ |
| GĐ4 | ~1 giờ |
| GĐ5 (verify) | ~30 phút |

Không migration, không đụng queue/Horizon, không đụng Meilisearch. Rủi ro thấp — partial null-safe nên trang thiếu biến chỉ đơn giản không render thẻ tương ứng.

## 4. Ngoài phạm vi (ghi nhận, chưa làm)

- Nuxt 3 storefront (doc kiến trúc nói `JsonldRenderer` + `useSeo()` — áp dụng khi build Nuxt, không phải blade hiện tại).
- `Product` schema nâng cao trên PLP items (offers, aggregateRating per item) — Google chỉ cần `url` cho ItemList summary page; thêm sau nếu cần.
- Review/AggregateRating schema cho PDP — phụ thuộc module review.
- OG image riêng per-filter cho PLP.
