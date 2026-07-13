# Song ngữ giao diện Admin (Filament UI labels) — Sprint Plan

**Last Updated:** 2026-07-13

> Phạm vi: chuyển toàn bộ label/placeholder/helperText/section/tab/step title/notification
> hardcode trong `app/Filament` sang `__('admin.xxx')`, dùng cơ chế locale switcher đã
> dựng sẵn (`SetAdminLocale` middleware + `lang/{vi,en}/admin.php`).
> **Không liên quan** tới content translation (`product_translations`,
> `blog_post_translations`...) — cái đó đã xong, xem `doc/todo.md`.

Baseline audit (2026-07-13), đếm bằng:
```bash
grep -oE "\->label\(|\->placeholder\(|\->helperText\(|Section::make\(|Tab::make\(|Step::make\(" <file> | wc -l
```
Tổng: **~1.237 chỗ hardcode** trên 28 Resources + 7 Pages + 1 RelationManager.

---

## Sprint 0 — Chuẩn hoá quy ước (bắt buộc làm trước tất cả)

**Mục tiêu:** chốt cấu trúc key trong `lang/vi/admin.php` + `lang/en/admin.php` trước khi
bất kỳ sprint nào khác bắt đầu, theo đúng pattern `UserResource.php` đã dùng.

```php
'product' => [
    'fields'        => ['name' => '...', 'price' => '...'],
    'sections'      => ['pricing' => '...'],
    'actions'       => [...],
    'notifications' => [...],
],
```

**Vì sao tách riêng:** nếu mỗi sprint tự đặt tên key khác kiểu, sẽ không đồng bộ được —
phải làm lại toàn bộ về sau.

**Deliverable:** cấu trúc nested key mẫu (ít nhất 1 resource ví dụ ngoài `user`) +
convention ghi lại ở đầu file `lang/vi/admin.php`.

---

## Sprint 1 — Product (ưu tiên cao nhất)

| File | Hardcode |
|---|---|
| `app/Filament/Resources/ProductResource.php` | 234 |

⚠️ File này đã có tab lồng `translations.vi.*` / `translations.en.*` cho **content**
(tên sản phẩm, giá theo ngôn ngữ) — **không đụng vào các key `translations.xxx`**, chỉ
convert label/placeholder/section title của UI (ví dụ tiêu đề tab, tên field ngoài,
helper text).

---

## Sprint 2 — Category

| File | Hardcode |
|---|---|
| `app/Filament/Resources/CategoryResource.php` | 144 |

---

## Sprint 3 — Blog Category / Tag / Comment

| File | Hardcode |
|---|---|
| `app/Filament/Resources/BlogCategoryResource.php` | 134 |
| `app/Filament/Resources/BlogTagResource.php` | 1 |
| `app/Filament/Resources/BlogCommentResource.php` | 9 |
| **Tổng** | **144** |

---

## Sprint 4 — Business Profile

| File | Hardcode |
|---|---|
| `app/Filament/Resources/BusinessProfileResource.php` | 133 |

---

## Sprint 5 — Blog Post

| File | Hardcode |
|---|---|
| `app/Filament/Resources/BlogPostResource.php` | 127 |

---

## Sprint 6 — Brand / Manufacturer

| File | Hardcode |
|---|---|
| `app/Filament/Resources/BrandResource.php` | 121 |
| `app/Filament/Resources/ManufacturerResource.php` | 25 |
| **Tổng** | **146** |

Ghi chú: 2 model này hiện chưa có content translation (`translations()` relation) — chỉ
cần convert UI label, không có tab vi/en content để tránh nhầm.

---

## Sprint 7 — Settings Pages

| File | Hardcode |
|---|---|
| `app/Filament/Pages/LandingSetup.php` | 35 |
| `app/Filament/Pages/ShopSetting.php` | 25 |
| `app/Filament/Pages/AnalyticsSettings.php` | 18 |
| `app/Filament/Pages/BlogSetting.php` | 17 |
| `app/Filament/Pages/MegaMenuSettings.php` | 15 |
| `app/Filament/Pages/DeveloperPage.php` | 5 |
| `app/Filament/Pages/PagesSetting.php` | 0 |
| **Tổng** | **115** |

Ghi chú: đây là Filament **Pages** (không phải Resource) — cấu trúc code khác
(`getFormSchema()` thay vì `form()` trên Resource), tách sprint riêng.

---

## Sprint 8 — Order / Promotion / Review

| File | Hardcode |
|---|---|
| `app/Filament/Resources/OrderResource.php` | 35 |
| `app/Filament/Resources/OrderInquiryResource.php` | 15 |
| `app/Filament/Resources/PromotionResource.php` | 34 |
| `app/Filament/Resources/ReviewResource.php` | 16 |
| **Tổng** | **100** |

---

## Sprint 9 — SEO / GEO cluster

| File | Hardcode |
|---|---|
| `app/Filament/Resources/SeoMetaResource.php` | 25 |
| `app/Filament/Resources/GeoEntityProfileResource.php` | 24 |
| `app/Filament/Resources/JsonldSchemaResource.php` | 18 |
| `app/Filament/Resources/JsonldTemplateResource.php` | 9 |
| `app/Filament/Resources/LlmsDocumentResource.php` | 26 |
| `app/Filament/Resources/LlmsDocumentResource/RelationManagers/EntriesRelationManager.php` | 3 |
| `app/Filament/Resources/SitemapIndexResource.php` | 10 |
| `app/Filament/Resources/RedirectResource.php` | 20 |
| `app/Filament/Resources/PageResource.php` | 19 |
| **Tổng** | **154** |

Ghi chú: nhóm liên quan nhau (schema, sitemap, canonical, llms...) — dịch cùng lúc để
nhất quán thuật ngữ kỹ thuật SEO giữa các file.

---

## Sprint 10 — Hệ thống còn lại

| File | Hardcode |
|---|---|
| `app/Filament/Resources/AuthorResource.php` | 32 |
| `app/Filament/Resources/FilterGroupResource.php` | 23 |
| `app/Filament/Resources/SizeGuideResource.php` | 20 |
| `app/Filament/Resources/ActivityLogResource.php` | 24 |
| `app/Filament/Resources/PersonalAccessTokenResource.php` | 13 |
| `app/Filament/Resources/MediaResource.php` | 9 |
| `app/Filament/Resources/UserResource.php` (phần còn sót) | 12 |
| **Tổng** | **133** |

Ghi chú: `UserResource.php` đã convert phần lớn (17 chỗ dùng `__('admin.xxx')` —
resource mẫu tham chiếu cho Sprint 0), 12 chỗ còn lại là sót (có thể ở table
columns/filters/bulk actions chưa đụng tới lần đầu).

---

## Sprint 11 — Audit & Test (bắt buộc, không được bỏ qua)

1. Grep lại toàn bộ `app/Filament` sau khi xong Sprint 1–10, xác nhận tổng hardcode
   giảm từ ~1.237 về gần 0 (trừ chỗ cố ý giữ nguyên, nếu có, phải ghi chú rõ lý do).
2. **Test tay trên trình duyệt thật** (chưa từng làm — xem `doc/todo.md`): bật locale
   switcher trên topbar admin, đi qua **từng resource/page** trong Sprint 1–10, xác
   nhận:
   - Không có label nào hiện literal key kiểu `admin.product.fields.name` (nghĩa là
     thiếu key trong `lang/{vi,en}/admin.php`).
   - Nội dung tiếng Việt và tiếng Anh đọc tự nhiên, không lẫn ngôn ngữ.
   - Notification/toast (create/update/delete) cũng đổi theo locale.

---

## Tổng kết

| Sprint | Chủ đề | Hardcode |
|---|---|---|
| 0 | Chuẩn hoá quy ước | — |
| 1 | Product | 234 |
| 2 | Category | 144 |
| 3 | Blog Category/Tag/Comment | 144 |
| 4 | Business Profile | 133 |
| 5 | Blog Post | 127 |
| 6 | Brand/Manufacturer | 146 |
| 7 | Settings Pages | 115 |
| 8 | Order/Promotion/Review | 100 |
| 9 | SEO/GEO cluster | 154 |
| 10 | Hệ thống còn lại | 133 |
| 11 | Audit & Test | — |
| **Tổng** | | **~1.237** |

Có thể làm tuần tự (1 người) hoặc giao song song nhiều sprint cùng lúc cho nhiều người
— miễn Sprint 0 phải xong và thống nhất trước khi bất kỳ sprint nào khác bắt đầu.
