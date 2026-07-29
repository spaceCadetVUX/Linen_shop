# 05. Thao tác trên MCP (AI/Claude)

> Đọc `00-phan-quyen.md` trước nếu chưa đọc. File này dành cho người điều khiển AI (Claude) thao tác vào dữ liệu qua MCP, hoặc admin muốn hiểu AI đang làm gì với dữ liệu.

---

## 1. MCP là gì, dùng để làm gì

MCP là lớp API riêng (tách biệt hoàn toàn với Filament Admin) cho phép AI (Claude) tự động tạo/sửa sản phẩm, danh mục, thương hiệu, nhà sản xuất, bài viết blog, danh mục blog... thay vì nhập tay từng trường trong Filament.

Mọi thứ AI tạo ra qua MCP đều ở trạng thái **chưa kích hoạt** (draft/is_active = false) cho tới khi có người bấm Activate, kể cả khi AI tự báo "đã lưu thành công". AI không tự đưa nội dung lên site.

## 2. Danh sách tool theo từng loại dữ liệu

| Loại | Tool lưu | Tool xem context | Tool kiểm tra sẵn sàng | Tool kích hoạt |
|---|---|---|---|---|
| Sản phẩm | `save_product` | `get_product_context` | `check_product_readiness` | `activate_product` |
| Danh mục | `save_category` | `get_category_context` | `check_category_readiness` | `activate_category` |
| Thương hiệu | `save_brand` | `get_brand_context` | `check_brand_readiness` | `activate_brand` |
| Nhà sản xuất | `save_manufacturer` | `get_manufacturer_context` | `check_manufacturer_readiness` | `activate_manufacturer` |
| Bài viết Blog | `save_blog_post` | `get_blog_post_context` | `check_blog_post_readiness` | `publish_blog_post` |
| Danh mục Blog | `save_blog_category` | `get_blog_category_context` | `check_blog_category_readiness` | `activate_blog_category` |

Mỗi `save_*` xác định "đang lưu vào bản ghi nào" bằng **slug trên URL gọi tool**, không phải bằng tên hay ID. Xem mục 4 để hiểu rõ vì sao chọn đúng slug là quan trọng nhất.

## 3. Tham số dùng chung ở mọi `save_*`

- **`overwrite_existing`** (mặc định `false`): nếu `false`, chỉ điền vào trường **đang trống**, không đụng vào trường đã có nội dung. Nếu `true`, ghi đè cả trường đã có nội dung. Mặc định `false` để AI không vô tình xóa mất nội dung admin đã viết tay.
- **`dry_run`** (mặc định `false`): đặt `true` để xem trước kết quả sẽ ra sao **mà không thực sự lưu vào DB**. Dùng để kiểm tra trước khi ghi thật.
- **`_stubs`** (chỉ có ở `save_product`): cho phép tự động tạo tạm brand/manufacturer/category nếu slug được chỉ định chưa tồn tại, kèm dữ liệu tối thiểu. Bản ghi tạo tạm này ở trạng thái chưa kích hoạt, cần bổ sung nội dung sau.

## 4. Slug: quy tắc chung và điểm khác biệt của Bài viết Blog

Slug trên URL gọi tool (`PUT .../products/{slug}`, `.../blog-posts/{slug}`...) chính là **định danh** để tìm lại đúng bản ghi ở lần gọi sau. Gọi lại đúng slug đó → cập nhật bản ghi cũ. Gọi slug khác → tạo bản ghi mới.

**Với Product/Category/Brand/Manufacturer/Blog Category**: slug trên URL được lưu vào 1 cột riêng, ổn định, không đổi trừ khi có ai chủ động sửa. Cứ dùng đúng slug ban đầu cho mọi lần gọi tiếp theo là an toàn.

**Với Bài viết Blog: KHÁC HẲN.** Hệ thống không có cột định danh riêng cho bài viết, slug trên URL chính là slug của bản dịch tiếng Việt. Vì vậy:

- ⚠️ **Luôn dùng đúng 1 slug cho mọi lần gọi `save_blog_post` vào cùng 1 bài**, kể cả lần đầu chỉ gửi metadata (danh mục, tác giả...) chưa có nội dung.
- Nếu trong `translations.vi.slug` gửi lên khác với slug trên URL, hệ thống sẽ **ưu tiên slug trên URL**, bỏ qua giá trị gửi lên trong `translations.vi.slug` (chỉ đúng cho locale `vi`, ở lần lưu translation đầu tiên).
- Slug cũng bị khóa sau lần lưu translation đầu tiên, giống Filament: gọi lại `save_blog_post` với title mới **không tự đổi slug**.

*(Đã từng có bug: gọi `save_blog_post` lần đầu không kèm translations, lần sau gửi `translations.vi.slug` khác slug trên URL, hệ thống tạo ra 2 bản ghi riêng biệt thay vì cập nhật cùng 1 bài. Đã vá 30/07, nhưng vẫn nên tuân theo quy tắc "1 slug xuyên suốt" để chắc chắn.)*

## 5. Khi slug bị chặn: 2 loại lỗi 422 cần phân biệt

| Thông báo lỗi | Ý nghĩa | Cách xử lý |
|---|---|---|
| "...is already used by another product's {locale} translation" | Slug này **đang có 1 bản ghi khác đang dùng thật** (trùng tên/tiêu đề tình cờ) | Đổi slug/tên khác cho bản ghi đang lưu |
| "...is currently redirected elsewhere (to ...)" | Slug này **từng thuộc về 1 bản ghi khác đã đổi tên**, giờ chỉ còn là redirect trỏ đi chỗ khác | Đổi slug khác, hoặc vào mục **Redirect** (nhóm SEO & GEO) tắt/sửa redirect đó nếu chắc chắn muốn tái sử dụng slug này |

Cả 2 lỗi đều là **chặn cứng, không lưu gì cả** (an toàn, không tạo dữ liệu rác, không đụng bản ghi khác). Trước 29/07, lỗi trùng slug với bản ghi khác từng trả về "Server Error" chung chung; giờ trả lỗi rõ ràng như trên.

## 6. Khóa nội dung khỏi AI (`is_mcp_protected`)

Xem cách bật ở `00-phan-quyen.md` mục 6 (toggle trong Filament). Về phía MCP:

- Khi 1 locale (vi hoặc en) của 1 bản ghi đã bị khóa, lệnh `save_*` viết vào locale đó sẽ **âm thầm bị bỏ qua**, không báo lỗi.
- Response trả về vẫn là **thành công (200)**, nhưng có thêm `meta.protected_fields_skipped` liệt kê những phần bị bỏ qua, ví dụ: `["translations.vi", "seo.vi"]`. **Luôn kiểm tra field này** trước khi báo "đã cập nhật xong". Nếu nó xuất hiện, nội dung thực tế KHÔNG đổi dù response trông như thành công.
- Chỉ locale nào **đã có nội dung thật** mới bị khóa. Locale trống (chưa từng có bản dịch) sẽ không bị khóa dù toggle đang bật, AI vẫn ghi được vào locale đó cho tới khi nó có nội dung và được lưu lại từ Filament.

## 7. Quy trình khuyến nghị

1. Kiểm tra trước bằng `get_*_context` hoặc tìm kiếm, tránh đoán slug rồi tạo trùng.
2. Gọi `save_*` với `dry_run: true` trước nếu không chắc chắn về dữ liệu sẽ ghi.
3. Gọi thật, luôn dùng **cùng 1 slug** cho các lần gọi tiếp theo vào cùng 1 bản ghi (đặc biệt quan trọng với Blog Post, xem mục 4).
4. Đọc kỹ response: kiểm tra `meta.protected_fields_skipped` và `meta.auto_created` (nếu `save_product` tự tạo brand/category tạm qua `_stubs`) trước khi báo kết quả.
5. Gọi `check_*_readiness` trước khi `activate_*`/`publish_blog_post`. Nếu chưa đủ điều kiện, tool sẽ báo rõ lý do (thiếu SEO, thiếu category active...).
6. Không tự ý `activate`/`publish` khi chưa được người dùng xác nhận rõ ràng, kể cả khi readiness pass.

## 8. Lưu ý riêng theo từng loại

- **Brand/Manufacturer**: không có nội dung tách theo ngôn ngữ (chỉ 1 tên/mô tả dùng chung), chỉ phần SEO mới tách vi/en. Toggle khóa ở đây chỉ khóa SEO, không có "nội dung" riêng để khóa.
- **Category**: nội dung và SEO nằm chung 1 bảng (`category_translations`), nên `is_mcp_protected` của Category bảo vệ **cả 2 cùng lúc bằng 1 cờ duy nhất**, không tách riêng "nội dung" và "SEO" như Product.
- **Product**: `_stubs` cho phép tự tạo brand/manufacturer/category chưa tồn tại ngay trong lúc lưu sản phẩm. Các bản ghi tự tạo này ở trạng thái chưa kích hoạt, cần vào bổ sung nội dung và activate riêng sau.

---

*Hết. Xem lại `00-phan-quyen.md` nếu cần tra cứu khái niệm chung (Ẩn/Xóa, Draft/Published, khóa AI...).*
