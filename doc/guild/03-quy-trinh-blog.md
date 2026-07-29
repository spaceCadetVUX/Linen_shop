# 03. Quy trình tạo & quản lý Bài viết Blog

> Đọc `00-phan-quyen.md` trước nếu chưa đọc.
> File này liệt kê đầy đủ từng trường trong form Bài viết, Danh mục Blog, Bình luận, Thẻ và Tác giả.

---

## Bước 0. Chuẩn bị trước

- **Danh mục Blog (Blog Category)**: bài viết nên thuộc 1 danh mục. Khác với Danh mục sản phẩm, **Danh mục Blog không có cấu trúc cha-con** (chỉ 1 cấp phẳng).
- **Thẻ (Tag)**: có thể tạo mới ngay trong lúc soạn bài, không cần tạo trước.
- **Tác giả (Author)**: có thể tạo nhanh ngay trong lúc soạn bài (chỉ Tên/Slug/Chức danh), hoặc tạo đầy đủ hồ sơ trước ở menu riêng **Tác giả**, xem mục "Quản lý Tác giả" ở cuối file này. Tác giả có thể (không bắt buộc) được liên kết với 1 tài khoản nhân viên đang có trong hệ thống.

---

## Bước 1. Tab General

- **Danh mục Blog**: bắt buộc chọn. URL công khai của bài viết phụ thuộc vào danh mục (`/bai-viet/{danh-muc}/{slug}`), bài không có danh mục sẽ gãy link.
- **Ảnh đại diện (featured_image)**: chọn hoặc upload qua Thư viện Media. Xem quy cách tỷ lệ ảnh ở file `01-quy-cach-hinh-anh.md` (ảnh này hiển thị ở nhiều nơi khác tỷ lệ, cần chọn ảnh có chủ thể ở giữa).
- **Thẻ (Tags)**: chọn nhiều thẻ có sẵn, hoặc bấm để tạo thẻ mới ngay tại chỗ.
- Công tắc **"Khóa nội dung + SEO khỏi AI/MCP"**: chi tiết ở `00-phan-quyen.md` mục 6. Chỉ bật khi đã viết xong nội dung và không muốn AI ghi đè lại.

---

## Bước 2. Tab Content (có 2 tab con: VI / EN)

Tiếng Việt là ngôn ngữ **bắt buộc** (Tiêu đề bắt buộc). Tiếng Anh **hoàn toàn tùy chọn**, kể cả khi bạn đã điền Tiêu đề (en) thì Slug (en) vẫn không bắt buộc phải điền theo (khác với Sản phẩm, nơi slug EN bắt buộc nếu đã có tên EN).

- Nhập **Tiêu đề**, **Tóm tắt**, **Nội dung** (rich text) cho từng ngôn ngữ.
- **Slug** (đường dẫn URL) sẽ **tự sinh theo tiêu đề** khi bạn gõ, nhưng **chỉ trong lần tạo mới**. Sau khi bài đã được lưu ít nhất 1 lần, sửa lại Tiêu đề **sẽ không tự đổi Slug nữa** (kể cả tiêu đề VI lẫn EN), tránh việc URL đang chạy tốt bị đổi âm thầm chỉ vì admin sửa 1 lỗi chính tả.
  - ⚠️ Muốn đổi URL của bài đã lưu, phải **tự gõ tay vào ô Slug**.
  - Nếu slug bạn định đặt đang trùng với 1 URL cũ (đang được chuyển hướng sang chỗ khác), hệ thống sẽ **báo lỗi và chặn lưu** cho tới khi bạn đổi sang slug khác hoặc xử lý redirect đó trước (xem mục Redirect trong nhóm SEO & GEO).
  - ⚠️ **Lưu ý quan trọng**: hệ thống **chưa kiểm tra trùng slug giữa 2 bài viết khác nhau** ngay trên form (chỉ kiểm tra trùng với URL cũ đang redirect như trên). Nếu vô tình đặt cùng 1 slug cho 2 bài viết khác nhau (cùng ngôn ngữ), việc lưu sẽ báo **lỗi hệ thống** thay vì lời nhắc rõ ràng. Slug thường tự sinh theo tiêu đề nên hiếm khi trùng, nhưng nếu 2 bài có tiêu đề giống hệt nhau hoặc bạn tự gõ tay, hãy tự kiểm tra danh sách bài viết trước khi đặt trùng slug.

---

## Bước 3. Tab Publishing (Xuất bản)

Bài viết có **3 trạng thái**:

| Trạng thái | Ý nghĩa |
|---|---|
| **Draft (Nháp)** | Đang soạn, khách **không thấy** trên web. **Đây là trạng thái mặc định** khi tạo bài mới |
| **Published (Đã đăng)** | Hiển thị công khai trên web |
| **Archived (Lưu trữ)** | Đã từng đăng, nay ẩn đi (dùng khi muốn gỡ bài tạm thời mà không xóa) |

- **Ngày đăng (published_at)**: có thể đặt ngày trong tương lai để hẹn giờ, hoặc để trống/hiện tại nếu đăng ngay.
- Chọn **Tác giả** hiển thị cho bài viết. Có thể bấm tạo nhanh tác giả mới ngay tại đây (chỉ 3 trường: Tên, Slug, Chức danh), muốn điền đầy đủ hồ sơ (ảnh, giới thiệu, mạng xã hội...) thì vào menu **Tác giả** riêng sau.

⚠️ **Quan trọng:** Dữ liệu **JSON-LD và LLMs của bài viết chỉ được tạo ra khi trạng thái = Published**. Nếu bạn soạn đầy đủ nội dung, FAQ, GEO/AI... nhưng để ở Draft hoặc Archived, các dữ liệu SEO nâng cao này **sẽ chưa được sinh ra** cho tới khi bạn chuyển sang Published và lưu lại.

---

## Bước 4. Tab FAQ

Thêm các câu hỏi thường gặp cho bài viết (nếu có), theo từng ngôn ngữ (2 khối VI/EN riêng biệt, không giới hạn số lượng). Dữ liệu này được đưa vào JSON-LD FAQPage và tài liệu LLMs khi bài viết ở trạng thái Published.

---

## Bước 5. Tab GEO/AI

Theo từng ngôn ngữ (VI/EN):

| Trường | Ghi chú |
|---|---|
| **AI Summary** | Tóm tắt 2-4 câu. Đây là nội dung **hiển thị công khai ngay trên trang bài viết**, ngay dưới tiêu đề (kiểu "answer-first"), không chỉ dùng nội bộ cho AI đọc |
| **Use Cases** | AI dùng để trả lời "bài này phù hợp cho ai / dùng khi nào" |
| **Target Audience** | Đối tượng đọc mục tiêu |
| **LLM Context Hint** | Gợi ý ngữ cảnh thêm cho AI |
| **Key Facts** | Danh sách nhãn/giá trị, hiển thị công khai ngay dưới AI Summary trên trang bài viết |

---

## Bước 6. Tab SEO

Theo từng ngôn ngữ (VI/EN), gồm 2 khối: **Meta Tags** và **Open Graph** (thu gọn sẵn). **Không có khối Twitter Card riêng cho Bài viết** (khác với Danh mục Blog và Sản phẩm, xem phần dưới), đây không phải thiếu sót, Bài viết chỉ dùng chung dữ liệu Open Graph khi chia sẻ lên mọi nền tảng.

### Meta Tags

| Trường | Ghi chú |
|---|---|
| **Meta Title** | Có bộ đếm ký tự, đổi màu cam nếu vượt quá 60 ký tự |
| **Meta Description** | Có bộ đếm ký tự, đổi màu cam nếu vượt quá 155 ký tự |
| **Canonical URL** (chỉ xem) | Một ô riêng, **chỉ để xem**, tự hiển thị URL chuẩn hiện tại của bài viết theo slug + danh mục, chưa có gì để hiện nếu bài chưa từng được lưu |
| **Override canonical URL** | Ô nhập tay riêng, **để trống thì dùng đúng URL ở ô xem phía trên**. Chỉ điền nếu đây là bài đăng lại (syndicated) từ nguồn khác và muốn canonical trỏ ra ngoài |
| **Robots** | Mặc định `index, follow`. 2 lựa chọn còn lại: `noindex, follow` (loại khỏi index nhưng vẫn cho bot đi theo link trong bài) hoặc `noindex, nofollow` (chặn hoàn toàn) |

### Open Graph (ảnh/tiêu đề khi chia sẻ lên Facebook, Zalo...)

| Trường | Ghi chú |
|---|---|
| **OG Image** | ⚠️ Phải **tự chọn/upload tay** qua Thư viện Media, **không tự động lấy theo Ảnh đại diện**. Nếu bỏ trống, bài chia sẻ lên mạng xã hội sẽ **không có ảnh** |
| **OG Title** | Tự điền từ Meta Title nếu để trống |
| **OG Description** | Tự điền từ Meta Description nếu để trống |

---

## Bước 7. Tab JSON-LD & Tab LLMs

⚠️ **2 tab này sẽ KHÔNG HIỆN khi bạn đang tạo bài viết mới** (form Create), đây không phải lỗi giao diện. Chúng chỉ xuất hiện **sau khi bạn đã lưu bài viết lần đầu tiên**. Nếu tạo bài mới mà không thấy 2 tab này, hãy lưu bài trước, sau đó mở lại để sửa.

Giống sản phẩm: nội dung JSON-LD/LLMs mặc định tự sinh và tự ghi đè mỗi lần lưu, **chỉ khi bài ở trạng thái Published** (xem Bước 3); công tắc "Active"/"Published" riêng ở mỗi mục để bật/tắt hiển thị mà không cần xóa nội dung; muốn sửa tay JSON-LD phải vào menu ẩn **"JSON-LD Schemas"** (`/admin/json-ld-schemas`).

---

## Sau khi lưu bài viết: các thao tác trên danh sách

- Cột **Tiêu đề** trên danh sách hiện cả 2 ngôn ngữ: tiêu đề VI ở trên, tiêu đề EN nhỏ hơn ở dưới (nếu có).
- Có thể lọc theo **Trạng thái** (Draft/Published/Archived) và theo **Danh mục Blog**.
- ⚠️ **Bài viết Blog không có "thùng rác"**: bấm Xóa là **xóa thẳng luôn, không khôi phục lại được** qua giao diện quản trị. Cân nhắc kỹ, hoặc chuyển bài sang **Archived** nếu chỉ muốn tạm gỡ khỏi web mà vẫn giữ lại dữ liệu.
- Không có thao tác duyệt/xuất bản hàng loạt, chỉ có **Xóa hàng loạt** cho nhiều bài được chọn cùng lúc.

---

## Quản lý Danh mục Blog (Blog Category)

Form Danh mục Blog là **1 trang cuộn dài** (không chia tab lớn như Sản phẩm/Bài viết), gồm các khối sau.

### Thông tin cơ bản

- Có 2 cặp tên/slug: **"Tên/Slug nội bộ"** (chỉ nhân viên thấy, có cảnh báo màu vàng nhắc không hiển thị công khai, dùng trong JSON-LD và API nội bộ) và **tên/slug hiển thị công khai** nằm ở khối **Translations** (theo VI/EN). Đừng nhầm sửa nhầm chỗ.
- **Mô tả nội bộ**: chỉ để gợi nhớ nội dung, không hiển thị công khai.
- **Thứ tự** (sort_order): số nhỏ hơn hiện trước.
- **Kích hoạt** (is_active, mặc định bật): tắt để ẩn danh mục này khỏi storefront.
- Công tắc **"Khóa nội dung + SEO khỏi AI/MCP"**: giống các mục khác, xem `00-phan-quyen.md` mục 6.

### Translations (VI / EN, thu gọn sẵn)

- **Tên hiển thị**, **URL Slug**, **Mô tả** (ngắn, Google đọc để hiểu nội dung), **Nội dung phong phú** (rich text, hiển thị ở phần dưới trang danh mục).
- Slug cũng chỉ tự sinh lúc tạo mới, sửa tay sau khi đã lưu, và cũng **chỉ kiểm tra trùng với URL cũ đang redirect**, chưa kiểm tra trùng slug giữa 2 danh mục khác nhau trên form, giống lưu ý ⚠️ ở Bước 2 của Bài viết.

### GEO / AI (VI / EN, thu gọn sẵn)

Giống Bài viết: AI Summary, Use Cases, Target Audience, LLM Context Hint, Key Facts. **Câu hỏi thường gặp (FAQ) của Danh mục Blog nằm lồng bên trong khối GEO/AI này**, không phải 1 tab riêng như ở Bài viết.

### SEO (VI / EN, thu gọn sẵn)

Đầy đủ như Sản phẩm: Meta Tags (Title/Description có bộ đếm ký tự tối ưu 50-60 và 120-155, Meta Keywords, Canonical URL tự điền thẳng vào ô nếu để trống, Robots), **Open Graph** (Title/Description/Image/**OG Type**, ảnh chọn qua Thư viện Media), và **Twitter Card** (Card Type, Title, Description). Khác với Bài viết, Danh mục Blog **có đầy đủ khối Twitter Card riêng**.

### JSON-LD (VI / EN)

Cùng cơ chế Auto/Manual + công tắc Active như mọi nơi khác, chỉ hiện sau khi đã lưu lần đầu. **Danh mục Blog không có tab LLMs** (khác với Sản phẩm và Bài viết), đây không phải lỗi thiếu sót.

### Lưu ý khác

- **Không có thùng rác**: Xóa danh mục blog là xóa thẳng, không khôi phục được.

---

## Quản lý Bình luận (Blog Comment)

- Đây là bình luận **do khách hàng gửi lên**, admin **không tạo bình luận mới** được từ đây.
- Nội dung bình luận **chỉ xem, không sửa được** trực tiếp (ô nội dung bị khóa trong form sửa). Admin chỉ có thể:
  - **Duyệt (Approve)**: bình luận chỉ hiển thị công khai sau khi được duyệt, có thể bật/tắt riêng từng bình luận trong form sửa, hoặc dùng nút **duyệt hàng loạt** cho nhiều bình luận cùng lúc.
  - **Xóa** nếu là spam/vi phạm.
- Menu "Bình luận Blog" ở thanh điều hướng bên trái hiện số đếm **bình luận đang chờ duyệt** (chưa được approve).

## Quản lý Thẻ (Blog Tag)

Đơn giản, chỉ có tên/slug, kèm số đếm bài viết đang dùng thẻ đó.

## Quản lý Tác giả (Author)

Đây là hồ sơ tác giả đầy đủ, tách biệt với việc tạo nhanh tên/slug/chức danh ngay trong Bài viết (Bước 3). Có 4 khối:

| Khối | Trường | Ghi chú |
|---|---|---|
| **Thông tin cơ bản** | Ảnh đại diện, Tên, Slug, Chức danh | Slug tự sinh từ Tên, nếu tự gõ tay phải đúng định dạng chữ thường/số, phân cách bằng 1 dấu gạch ngang (không hoa, không gạch đôi, không gạch đầu/cuối), nếu không sẽ báo lỗi |
| **Giới thiệu** | Giới thiệu ngắn, Lĩnh vực chuyên môn (nhập từng thẻ, Enter để thêm) | Đưa vào JSON-LD Person làm mô tả tác giả và độ uy tín chuyên môn |
| **Mạng xã hội & Web** | Website, LinkedIn, X/Twitter, Facebook | Dùng trong JSON-LD Person (sameAs), giúp Google liên kết tác giả với danh tính đã xác minh |
| **Tài khoản Admin** | Tài khoản liên kết (tùy chọn), Kích hoạt | Liên kết với 1 tài khoản nhân viên đang có trong hệ thống, không bắt buộc (tác giả khách/cộng tác viên ngoài không cần). Tắt "Kích hoạt" sẽ ẩn tác giả này khỏi storefront |

---

*Tiếp theo: `04-duyet-danh-gia.md`.*
