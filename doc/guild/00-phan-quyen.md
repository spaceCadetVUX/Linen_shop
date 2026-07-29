# 00. Phân quyền & Khái niệm cơ bản trong trang Admin

> Đọc file này trước tiên. Các khái niệm ở đây lặp lại xuyên suốt mọi màn hình trong trang quản trị.

---

## 1. Ai được vào trang quản trị (`/admin`)?

Hệ thống có 3 loại tài khoản:

| Vai trò | Vào được `/admin`? |
|---|---|
| **Admin** | ✅ Toàn quyền |
| **Manager** | ✅ Gần như toàn quyền (trừ 4 khu vực ở mục 2) |
| **Customer** | ❌ Không bao giờ. Dù có tài khoản đăng nhập ở web bán hàng, tài khoản đó vẫn không thể mở được trang quản trị |

## 2. Khác nhau giữa Admin và Manager

Manager làm được **hầu hết mọi việc** giống Admin: quản lý sản phẩm, danh mục, đơn hàng, blog, khuyến mãi, đánh giá...

Chỉ có **4 khu vực sau là dành riêng cho Admin**, Manager sẽ không thấy mục này trong menu bên trái (và nếu cố tình gõ thẳng đường dẫn cũng sẽ bị từ chối):

1. **Nhật ký hoạt động** (Activity Log): xem ai đã sửa gì, khi nào
2. **API Tokens**: tạo khóa API để kết nối công cụ AI/MCP
3. **Cài đặt Analytics**: mã GA4, Google Tag Manager, Search Console
4. **Trang Developer**: thông tin kỹ thuật hệ thống, cấu hình MCP, đổi robots.txt

Nếu bạn là Manager và cần một trong 4 việc trên, hãy nhờ Admin thực hiện hoặc cấp quyền.

### Quản lý tài khoản nhân viên khác

Trong mục **Người dùng (Users)**:
- Admin thấy được và quản lý được **tất cả** tài khoản (Admin khác, Manager, Customer).
- Manager chỉ thấy được tài khoản **Customer** trong danh sách. Không thấy Manager khác hay Admin, kể cả khi biết ID và gõ thẳng đường dẫn.
- **Không ai tự xóa được chính tài khoản của mình.**
- Xóa một tài khoản nhân viên sẽ **thu hồi luôn** toàn bộ API Token mà người đó đã tạo.

---

## 3. Ba trạng thái khiến sản phẩm "biến mất" khỏi mắt khách hàng (dễ nhầm nhất)

Đây là điểm gây nhầm lẫn nhiều nhất khi mới dùng hệ thống. Có **3 mức độ khác nhau**, không phải một:

| Hành động | Ý nghĩa | Khôi phục được không? |
|---|---|---|
| **Ẩn** (tắt nút "Kích hoạt" / `is_active`) | Vẫn còn nguyên trong hệ thống, chỉ là không hiển thị công khai trên web. Có thể bật lại bất cứ lúc nào. | ✅ Bật lại tức thì |
| **Xóa** (nút thùng rác, xuất hiện trong danh sách) | Chuyển vào "thùng rác": không còn thấy trong danh sách chính, không hiển thị công khai. | ✅ Vẫn khôi phục được qua bộ lọc "Đã xóa" (Trashed) |
| **Xóa vĩnh viễn** (Force Delete) | Mất hoàn toàn khỏi hệ thống. | ❌ **Không thể khôi phục** |

**Lưu ý quan trọng:** không phải mục nào cũng có đủ cả 3 mức. Ví dụ:
- **Sản phẩm** và **Danh mục** có đủ cả 3 mức (Ẩn / Xóa / Xóa vĩnh viễn).
- **Đơn hàng, Thương hiệu (Brand), Bài viết Blog danh mục...** khi bấm Xóa là **xóa thẳng luôn, không có thùng rác**. Thao tác này không thể hoàn tác, hãy cân nhắc kỹ trước khi xóa các mục này.

> 👉 Nếu chỉ muốn tạm ngưng bán 1 sản phẩm mà không mất dữ liệu, **luôn dùng "Ẩn"**, đừng dùng "Xóa".

---

## 4. Draft/Published: chỉ áp dụng cho Bài viết Blog

Duy nhất **Bài viết Blog** có khái niệm "Nháp / Đã đăng / Lưu trữ" (Draft / Published / Archived).

Sản phẩm, Danh mục, Thương hiệu... **không có khái niệm "nháp"**, chúng chỉ có bật/tắt (Ẩn, xem mục 3). Nếu bạn tạo một sản phẩm mới và chưa muốn khách thấy, hãy **để "Ẩn"** cho tới khi sẵn sàng, không có trạng thái "nháp" riêng để lưu tạm.

---

## 5. Nút đổi ngôn ngữ ở góc trên trang admin là gì?

Trên thanh trên cùng của trang quản trị có một nút đổi ngôn ngữ (VI/EN). Nút này **chỉ đổi ngôn ngữ hiển thị của giao diện quản trị** (tên các nút, nhãn field...). **Không liên quan** đến nội dung tiếng Việt/tiếng Anh của sản phẩm hay bài viết trên web bán hàng.

Nội dung song ngữ của sản phẩm/danh mục/blog nằm ở các **tab riêng "VI" và "EN"** ngay trong form chỉnh sửa. Xem chi tiết ở các file hướng dẫn tiếp theo.

---

## 6. Toggle "Khóa nội dung + SEO khỏi AI/MCP"

Product, Category, Brand, Manufacturer, Blog Post, Blog Category đều có 1 công tắc này trong tab **General** (tên đầy đủ tùy resource, ví dụ "Khóa nội dung + SEO (VI & EN) khỏi AI/MCP").

**Dùng để làm gì:** hệ thống có kết nối MCP cho phép AI (Claude) tự động tạo/sửa nội dung sản phẩm, bài viết... qua các lệnh như `save_product`. Nếu bạn đã tự tay viết nội dung hoàn chỉnh cho 1 sản phẩm/bài viết và không muốn AI vô tình ghi đè lại sau này, bật công tắc này lên.

**Cách hoạt động:**
- Bật lên: khóa **cả nội dung lẫn SEO** của **cả 2 ngôn ngữ VI và EN cùng lúc** (không tách riêng được từng ngôn ngữ).
- Khi đã khóa, mọi lệnh AI/MCP cố ghi vào sản phẩm/bài viết này sẽ **bị bỏ qua ở phần đã khóa**, không báo lỗi ầm ĩ, chỉ đơn giản không ghi đè.
- Có thể **bật/tắt lại bất cứ lúc nào**, không phải thao tác một chiều. Tắt đi để AI được phép ghi lại bình thường.

> 👉 Chỉ bật khi bạn thực sự muốn "đóng băng" nội dung đó khỏi AI. Nếu ngôn ngữ EN chưa có nội dung gì, hệ thống sẽ không khóa phần EN dù bạn có bật công tắc (không có gì để khóa). Nội dung EN vẫn có thể được AI điền vào bình thường cho tới khi bạn quay lại bấm Save một lần sau khi EN đã có nội dung.

---

*Tiếp theo: xem `01-quy-cach-hinh-anh.md` để biết quy cách ảnh cho từng vị trí, hoặc `02-quy-trinh-san-pham.md` để bắt đầu tạo sản phẩm.*
