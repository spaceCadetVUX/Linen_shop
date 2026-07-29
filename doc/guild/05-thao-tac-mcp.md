# 05. Thao tác trên MCP (AI/Claude): Tham chiếu đầy đủ 48 tool

> Đọc `00-phan-quyen.md` trước nếu chưa đọc. File này dành cho người điều khiển AI (Claude) thao tác vào dữ liệu qua MCP, hoặc admin muốn hiểu AI đang làm gì với dữ liệu. Nội dung lấy trực tiếp từ code nguồn `mcp-server/src/tools/sprint0.ts` đến `sprint9.ts` (48 tool, tính đủ).

---

## 1. MCP là gì, dùng để làm gì

MCP là lớp API riêng (tách biệt hoàn toàn với Filament Admin) cho phép AI (Claude) tự động tạo/sửa sản phẩm, danh mục, thương hiệu, nhà sản xuất, bài viết blog, danh mục blog, nhóm thuộc tính lọc, biến thể sản phẩm, trang tĩnh... thay vì nhập tay từng trường trong Filament.

Mọi thứ AI tạo ra qua MCP đều ở trạng thái **chưa kích hoạt** cho tới khi có người bấm Activate/Publish, kể cả khi AI tự báo "đã lưu thành công". AI không tự đưa nội dung lên site.

## 2. Bảng tổng quan 48 tool theo nhóm

| Nhóm | Số tool | Tool |
|---|---|---|
| Khám phá / Tra cứu chung | 4 | `audit_site_content`, `list_entities`, `search_entities`, `get_review_queue` |
| Sản phẩm | 4 | `get_product_context`, `check_product_readiness`, `save_product`, `activate_product` |
| Danh mục sản phẩm | 4 | `get_category_context`, `check_category_readiness`, `save_category`, `activate_category` |
| Bài viết Blog | 4 | `get_blog_post_context`, `check_blog_post_readiness`, `save_blog_post`, `publish_blog_post` |
| Danh mục Blog | 4 | `get_blog_category_context`, `check_blog_category_readiness`, `save_blog_category`, `activate_blog_category` |
| Thương hiệu | 4 | `get_brand_context`, `check_brand_readiness`, `save_brand`, `activate_brand` |
| Nhà sản xuất | 4 | `get_manufacturer_context`, `check_manufacturer_readiness`, `save_manufacturer`, `activate_manufacturer` |
| Hàng loạt (Bulk) | 2 | `bulk_seo_fill`, `bulk_translate` |
| Import từ Spec | 1 | `import_from_specs` |
| Nhóm thuộc tính lọc (Filter Group) | 6 | `list_filter_groups`, `get_filter_group_context`, `check_filter_group_readiness`, `save_filter_group`, `activate_filter_group`, `deactivate_filter_group` |
| Biến thể sản phẩm (Variant) | 5 | `list_product_variants`, `save_product_variant`, `generate_product_variants`, `activate_product_variant`, `deactivate_product_variant` |
| Trang tĩnh (Pages) | 6 | `list_pages`, `get_page_context`, `check_page_readiness`, `save_page`, `activate_page`, `deactivate_page` |
| **Tổng** | **48** | |

---

## 3. Nhóm: Khám phá & tra cứu chung

| Tool | Tham số chính | Ghi chú |
|---|---|---|
| `audit_site_content` | `model_type`, `locale`, `missing`, `is_active`, `per_page`, `page` | Quét toàn site tìm entity thiếu content/SEO/translations. **Nên gọi đầu tiên** trước khi bắt đầu 1 phiên làm việc lớn. |
| `list_entities` | `model_type` (bắt buộc: products/categories/blog-posts/blog-categories/brands/manufacturers), `has_description`, `has_seo`, `locale`, `per_page` | Liệt kê theo loại + filter, dùng để xem danh sách cần xử lý. |
| `search_entities` | `q` (bắt buộc), `types`, `locale`, `per_page` | Tìm theo từ khóa khi biết tên nhưng không biết slug. |
| `get_review_queue` | `model_type`, `drafted_after`, `per_page` | Danh sách bản ghi AI đã tạo (`mcp_drafted_at` có giá trị) nhưng chưa được người duyệt/kích hoạt. |

## 4. Nhóm: Sản phẩm (`save_product` là tool phức tạp nhất trong toàn bộ 48 tool)

| Tool | Việc làm |
|---|---|
| `get_product_context` | Load toàn bộ context (translations, SEO, GEO, attributes, variants...) trước khi viết. Luôn gọi trước `save_product` nếu là sửa bản ghi đã có. |
| `check_product_readiness` | Kiểm tra đủ điều kiện activate chưa. |
| `save_product` | Upsert, xem chi tiết field bên dưới. |
| `activate_product` | Kích hoạt sau khi readiness pass. |

**Các trường đáng chú ý trong `save_product`:**

- **`price`/`sale_price`/`currency` ở cấp root** tự động "rót xuống" bản dịch (translations) theo locale nếu locale đó **chưa** được set giá riêng trong `translations`. Không phải 1 giá dùng chung cho mọi ngôn ngữ, xem thêm `01-quy-cach-hinh-anh.md`/`02-quy-trinh-san-pham.md` về khái niệm 2 khối giá VND/USD.
- **`attributes` là FULL-REPLACE, nguy hiểm nếu dùng sai:** gửi mảng này sẽ **XÓA SẠCH** toàn bộ thông số kỹ thuật cũ rồi tạo lại từ đầu theo đúng mảng gửi lên. Muốn thêm 1 thông số mới, phải gửi **đủ cả thông số cũ lẫn mới**, không phải chỉ phần muốn thêm. Nhầm chỗ này sẽ mất hết bảng thông số cũ.
- **`filter_values`/`remove_filter_values` thì NGƯỢC LẠI, chỉ cộng/trừ (additive):** gửi `filter_values` chỉ **gán thêm**, không đụng tới value đã gán trước đó; `remove_filter_values` chỉ gỡ đúng những value liệt kê. Muốn biết slug hợp lệ, gọi `list_filter_groups` trước.
- **`_stubs`** cho phép tự tạo `manufacturer`/`category` chưa tồn tại ngay trong lúc lưu sản phẩm (kèm dữ liệu tối thiểu). Bản ghi tự tạo ở trạng thái chưa kích hoạt, cần bổ sung nội dung và activate riêng sau, theo dõi qua `meta.auto_created` trong response.
- **`geo.{locale}.faq` ưu tiên hơn `faq_items_vi`/`faq_items_en`**, 2 field sau đã deprecated, chỉ giữ để tương thích ngược.
- Không có field `slug` trong `translations` cho phép AI set trực tiếp mọi lúc. Slug bị khóa sau lần lưu đầu, xem mục 14.

## 5. Nhóm: Danh mục sản phẩm

| Tool | Việc làm |
|---|---|
| `get_category_context` | Load context trước khi viết. |
| `check_category_readiness` | Kiểm tra đủ điều kiện activate. |
| `save_category` | Upsert, có thêm `parent_slug` (danh mục cha, tối đa 2 cấp, xem `02-quy-trinh-san-pham.md`), `translations.{locale}.rich_content` (gửi HTML thường, backend tự convert sang Tiptap JSON). |
| `activate_category` | Kích hoạt. |

## 6. Nhóm: Bài viết Blog & Danh mục Blog

| Tool | Việc làm |
|---|---|
| `get_blog_post_context` / `check_blog_post_readiness` | Xem context / kiểm tra sẵn sàng. |
| `save_blog_post` | Upsert bài viết. Có `blog_category_slug`, `author_slug`, `tags` (mảng slug). ⚠️ Xem mục 14, quy tắc slug của Blog Post khác hẳn mọi entity còn lại. |
| `publish_blog_post` | Xuất bản, có thể hẹn giờ qua `published_at` (ISO 8601). Không truyền = publish ngay. |
| `get_blog_category_context` / `check_blog_category_readiness` | Xem context / kiểm tra sẵn sàng cho danh mục blog. |
| `save_blog_category` | Upsert danh mục blog. `geo.{locale}.faq` cần **ít nhất 3 câu** để đạt readiness. |
| `activate_blog_category` | Kích hoạt danh mục blog. |

## 7. Nhóm: Thương hiệu & Nhà sản xuất

| Tool | Việc làm |
|---|---|
| `get_brand_context` / `check_brand_readiness` / `save_brand` / `activate_brand` | Brand chỉ có 1 tên/mô tả dùng chung (không tách vi/en), chỉ `seo` mới tách theo locale. |
| `get_manufacturer_context` / `check_manufacturer_readiness` / `save_manufacturer` / `activate_manufacturer` | Giống Brand, có thêm `country`. |

## 8. Nhóm: Thao tác hàng loạt (Bulk)

| Tool | Giới hạn | Ghi chú |
|---|---|---|
| `bulk_seo_fill` | Tối đa **50 items**/lần | Điền SEO meta hàng loạt cho nhiều entity thuộc nhiều loại khác nhau cùng lúc. |
| `bulk_translate` | Tối đa **20 items**/lần | Dịch content từ 1 locale sang locale khác, có thể chỉ định `fields` cụ thể (không truyền = dịch hết). |

## 9. Nhóm: Import từ Spec

| Tool | Ghi chú |
|---|---|
| `import_from_specs` | Chỉ **phân tích** text từ datasheet/catalog thành attributes thô và tra cứu manufacturer/category liên quan. **Không tự lưu vào DB.** Sau khi có kết quả, Claude phải tự viết translations/SEO/FAQ rồi gọi `save_product` riêng để lưu thật. |

## 10. Nhóm: Nhóm thuộc tính lọc (Filter Group)

| Tool | Ghi chú |
|---|---|
| `list_filter_groups` | Không tham số. Gọi trước khi gán `filter_values` cho sản phẩm để biết slug hợp lệ. |
| `get_filter_group_context` / `check_filter_group_readiness` | Xem context / kiểm tra sẵn sàng (có value active, value màu phải có `color_hex`...). |
| `save_filter_group` | `type`: `text` hoặc `color`. **`is_variant_dimension = true`** là cờ quan trọng nhất, chỉ group nào bật cờ này mới dùng để sinh biến thể sản phẩm được (xem mục 11). `values` là **upsert theo slug/name, KHÔNG xóa** value không có trong mảng, khác hẳn `attributes` của Product (full-replace). |
| `activate_filter_group` / `deactivate_filter_group` | Bật/tắt cả group khỏi storefront. Muốn tắt riêng 1 value, dùng `save_filter_group` với `values: [{ name, is_active: false }]`, không có tool riêng cho từng value. |

## 11. Nhóm: Biến thể sản phẩm (Variant), quy trình bắt buộc theo đúng thứ tự

| Tool | Ghi chú |
|---|---|
| `list_product_variants` | Xem toàn bộ variant hiện có của 1 sản phẩm. |
| `generate_product_variants` | Sinh tổ hợp Cartesian (VD Color × Size) từ các `filter_values` đã gán cho sản phẩm thuộc group có `is_variant_dimension = true`. **Chỉ tạo tổ hợp còn thiếu**, không sửa/xóa variant đã có. ⚠️ **Sản phẩm phải có SKU trước** (set qua `save_product`), thiếu SKU sẽ bị từ chối thẳng. |
| `save_product_variant` | Sửa 1 variant đã tồn tại qua `sku` (giá VND/USD, tồn kho, `availability_status`). **Không đổi được tổ hợp Color/Size** qua tool này, chỉ làm được trong Filament. `is_active` qua tool này **chỉ nhận `false`** (tắt), muốn bật lại phải dùng `activate_product_variant`. |
| `activate_product_variant` | Bật 1 variant, điều kiện bắt buộc: `price_vnd > 0`. |
| `deactivate_product_variant` | Tắt 1 variant, không cần điều kiện gì. |

**Thứ tự đúng để tạo biến thể qua MCP:**
1. `save_product` set SKU + gán `filter_values` thuộc group đã bật `is_variant_dimension`.
2. `generate_product_variants`.
3. `save_product_variant` để điền giá/tồn kho từng variant.
4. `activate_product_variant` khi giá đã lớn hơn 0.

## 12. Nhóm: Trang tĩnh (Pages)

| Tool | Ghi chú |
|---|---|
| `list_pages` | Không tham số. Xem toàn bộ `page_key`, trạng thái active, translations hiện có. |
| `get_page_context` / `check_page_readiness` | Theo `page_key` (không phải slug hiển thị), ví dụ `'privacy-policy'`, `'terms'`. Readiness: `title`/`body`/`meta_title`/`meta_description` **bắt buộc ở vi, chỉ cảnh báo ở en**. |
| `save_page` | ⚠️ **Slug KHÔNG sửa được qua tool này**, luôn tự sinh từ `title`, khác hẳn mọi entity còn lại (nơi đều có field `slug` trong `translations` để tự set). Định danh duy nhất là `page_key`. |
| `activate_page` | Kích hoạt **cả 2 locale cùng lúc**. `is_active` nằm ở cấp trang (page), không tách riêng theo từng locale như các entity khác. |
| `deactivate_page` | Ẩn khỏi public ngay, không cần điều kiện. **Không có tool xóa trang**, chỉ deactivate được. |

---

## 13. Tham số dùng chung ở hầu hết `save_*`

- **`overwrite_existing`** (mặc định `false`): nếu `false`, chỉ điền vào trường **đang trống**, không đụng vào trường đã có nội dung. Nếu `true`, ghi đè cả trường đã có nội dung.
- **`dry_run`** (mặc định `false`): đặt `true` để xem trước kết quả **mà không thực sự lưu vào DB**.

## 14. Slug: 3 kiểu quy tắc khác nhau, đừng áp dụng nhầm

| Nhóm entity | Ai quyết định slug | Ghi chú |
|---|---|---|
| Product, Category, Brand, Manufacturer, Blog Category | Slug trên URL gọi tool là định danh ổn định, lưu vào 1 cột riêng. `translations.{locale}.slug` có thể set tay, khóa sau lần lưu đầu tiên. | Dùng đúng slug ban đầu cho mọi lần gọi tiếp theo là an toàn. |
| **Blog Post** | **Slug trên URL chính là slug của bản dịch tiếng Việt**, không có cột định danh riêng. | ⚠️ **Luôn dùng đúng 1 slug cho mọi lần gọi `save_blog_post` vào cùng 1 bài**, kể cả lần đầu chỉ gửi metadata. Nếu `translations.vi.slug` gửi lên khác slug trên URL, hệ thống ưu tiên slug trên URL (chỉ áp dụng cho locale `vi`, ở lần lưu translation đầu tiên). |
| **Page** | **Không set slug được qua tool**, luôn tự sinh từ `title`. Định danh là `page_key`, không phải slug. | Muốn đổi URL hiển thị của 1 trang tĩnh, đổi `title` (slug tự đổi theo). Không có cách set slug tùy ý qua MCP. |

## 15. Khi slug bị chặn: 2 loại lỗi 422 cần phân biệt

| Thông báo lỗi | Ý nghĩa | Cách xử lý |
|---|---|---|
| "...is already used by another product's {locale} translation" | Slug này **đang có 1 bản ghi khác đang dùng thật** | Đổi slug/tên khác cho bản ghi đang lưu |
| "...is currently redirected elsewhere (to ...)" | Slug này **từng thuộc về 1 bản ghi khác đã đổi tên**, giờ chỉ còn là redirect trỏ đi chỗ khác | Đổi slug khác, hoặc vào mục **Redirect** (nhóm SEO & GEO) xử lý redirect đó nếu chắc chắn muốn tái sử dụng slug |

Cả 2 lỗi đều là **chặn cứng, không lưu gì cả**, an toàn, không tạo dữ liệu rác. Áp dụng cho `save_product`, `save_category`, `save_blog_post`, `save_blog_category` (những entity có slug do AI/admin set).

## 16. Khóa nội dung khỏi AI (`is_mcp_protected`)

Xem cách bật ở `00-phan-quyen.md` mục 6.

- Khi 1 locale (vi hoặc en) của 1 bản ghi đã bị khóa, lệnh `save_*` viết vào locale đó sẽ **âm thầm bị bỏ qua**, không báo lỗi.
- Response trả về vẫn là **thành công (200)**, nhưng có thêm `meta.protected_fields_skipped`, ví dụ: `["translations.vi", "seo.vi"]`. **Luôn kiểm tra field này** trước khi báo "đã cập nhật xong". Nếu nó xuất hiện, nội dung thực tế KHÔNG đổi dù response trông như thành công.
- Chỉ locale nào **đã có nội dung thật** mới bị khóa. Locale trống vẫn ghi được cho tới khi có nội dung và được lưu lại từ Filament.

## 17. Quy trình khuyến nghị chung

1. Kiểm tra trước bằng `get_*_context`, `search_entities`, hoặc `list_entities`. Tránh đoán slug rồi tạo trùng.
2. Với sản phẩm cần biến thể/thuộc tính lọc: `list_filter_groups` trước để biết slug hợp lệ.
3. Gọi `save_*` với `dry_run: true` trước nếu không chắc chắn dữ liệu sẽ ghi.
4. Gọi thật, luôn dùng **cùng 1 slug** cho các lần gọi tiếp theo vào cùng 1 bản ghi (đặc biệt với Blog Post, xem mục 14).
5. Đọc kỹ response: `meta.protected_fields_skipped` và `meta.auto_created` (nếu `save_product` tự tạo brand/category tạm qua `_stubs`) trước khi báo kết quả.
6. Gọi `check_*_readiness` trước khi `activate_*`/`publish_blog_post`. Nếu chưa đủ điều kiện, tool sẽ báo rõ lý do.
7. Không tự ý `activate`/`publish` khi chưa được người dùng xác nhận rõ ràng, kể cả khi readiness pass.

---

*Hết. Xem lại `00-phan-quyen.md` nếu cần tra cứu khái niệm chung (Ẩn/Xóa, Draft/Published, khóa AI...).*
