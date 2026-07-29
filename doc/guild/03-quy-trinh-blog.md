# 03 — Quy trình tạo & quản lý Bài viết Blog

> Đọc `00-phan-quyen.md` trước nếu chưa đọc.

---

## Bước 0 — Chuẩn bị trước

- **Danh mục Blog (Blog Category)** — bài viết nên thuộc 1 danh mục. Khác với Danh mục sản phẩm, **Danh mục Blog không có cấu trúc cha-con** (chỉ 1 cấp phẳng).
- **Thẻ (Tag)** — có thể tạo mới ngay trong lúc soạn bài, không cần tạo trước.
- **Tác giả (Author)** — có thể tạo mới ngay trong lúc soạn bài, không cần tạo trước. Tác giả có thể (không bắt buộc) được liên kết với 1 tài khoản nhân viên đang có trong hệ thống.

## Bước 1 — Tab General

- **Ảnh đại diện (featured_image)** — xem quy cách tỷ lệ ảnh ở file `01-quy-cach-hinh-anh.md` (ảnh này hiển thị ở 3 nơi khác tỷ lệ, cần chọn ảnh có chủ thể ở giữa).
- **Thẻ (Tags)** — chọn nhiều thẻ có sẵn, hoặc bấm để tạo thẻ mới ngay tại chỗ.
- Tiêu đề, mô tả ngắn... theo từng ngôn ngữ (xem tab Content nếu có tách VI/EN riêng theo giao diện hiện tại).

## Bước 2 — Tab Publishing (Xuất bản)

Bài viết có **3 trạng thái**:

| Trạng thái | Ý nghĩa |
|---|---|
| **Draft (Nháp)** | Đang soạn, khách **không thấy** trên web |
| **Published (Đã đăng)** | Hiển thị công khai trên web |
| **Archived (Lưu trữ)** | Đã từng đăng, nay ẩn đi (dùng khi muốn gỡ bài tạm thời mà không xóa) |

- **Ngày đăng (published_at)** — có thể đặt ngày trong tương lai để hẹn giờ, hoặc để trống/hiện tại nếu đăng ngay.
- Chọn **Tác giả** hiển thị cho bài viết.

⚠️ **Quan trọng:** Dữ liệu **JSON-LD và LLMs của bài viết chỉ được tạo ra khi trạng thái = Published**. Nếu bạn soạn đầy đủ nội dung, FAQ, GEO/AI... nhưng để ở Draft, các dữ liệu SEO nâng cao này **sẽ chưa được sinh ra** cho tới khi bạn chuyển sang Published và lưu lại.

## Bước 3 — Tab FAQ

Thêm các câu hỏi thường gặp cho bài viết (nếu có), theo từng ngôn ngữ.

## Bước 4 — Tab GEO/AI

Tương tự sản phẩm: Tóm tắt AI, Đối tượng đọc, Gợi ý ngữ cảnh AI, Thông tin chính — theo từng ngôn ngữ.

## Bước 5 — Tab SEO

Tiêu đề SEO, Mô tả SEO, ảnh chia sẻ — theo từng ngôn ngữ.

## Bước 6 — Tab JSON-LD & Tab LLMs

⚠️ **2 tab này sẽ KHÔNG HIỆN khi bạn đang tạo bài viết mới** (form Create) — đây không phải lỗi giao diện. Chúng chỉ xuất hiện **sau khi bạn đã lưu bài viết lần đầu tiên**. Nếu tạo bài mới mà không thấy 2 tab này, hãy lưu bài trước, sau đó mở lại để sửa.

Giống sản phẩm: nội dung JSON-LD mặc định tự sinh và tự ghi đè mỗi lần lưu (nếu status = Published); muốn sửa tay phải vào menu ẩn **"JSON-LD Schemas"** (`/admin/json-ld-schemas`).

---

## Quản lý Danh mục Blog (Blog Category)

- Có 2 cặp tên/slug: **"Tên/Slug nội bộ"** (chỉ nhân viên thấy, có cảnh báo màu vàng nhắc không hiển thị công khai) và **tên/slug hiển thị công khai** nằm ở tab **Translations** (theo VI/EN). Đừng nhầm sửa nhầm chỗ.
- **Không có thùng rác** — Xóa danh mục blog là xóa thẳng, không khôi phục được.

## Quản lý Bình luận (Blog Comment)

- Đây là bình luận **do khách hàng gửi lên** — admin **không tạo bình luận mới** được từ đây, chỉ có thể:
  - **Duyệt (Approve)** — bình luận chỉ hiển thị công khai sau khi được duyệt.
  - Sửa nội dung hoặc xóa nếu là spam/vi phạm.
  - Có nút **duyệt hàng loạt** nhiều bình luận cùng lúc.

## Quản lý Thẻ (Blog Tag)

Đơn giản — chỉ có tên/slug, kèm số đếm bài viết đang dùng thẻ đó.

---

*Tiếp theo: `04-duyet-danh-gia.md`.*
