# 02. Quy trình tạo & quản lý Sản phẩm

> Đọc `00-phan-quyen.md` trước nếu chưa đọc. Các khái niệm "Ẩn/Xóa", "Draft" được nhắc lại ở đây.

---

## Bước 0. Chuẩn bị trước khi tạo sản phẩm

Trước khi vào tạo sản phẩm, nên có sẵn (nếu cần dùng):

1. **Danh mục (Category)**: sản phẩm phải thuộc ít nhất 1 danh mục.
2. **Thương hiệu (Brand)**: nếu cần gắn thương hiệu.
3. **Nhà sản xuất (Manufacturer)**: nếu cần.
4. **Nhóm thuộc tính (Filter Group)**, ví dụ Size, Màu sắc: **chỉ cần chuẩn bị trước nếu sản phẩm này có nhiều biến thể** (VD: 1 áo có nhiều size/màu). Xem lưu ý quan trọng ở mục "Nhóm thuộc tính" bên dưới.

### Tạo Danh mục (Category)

- Danh mục **tối đa 2 cấp**: 1 danh mục cha, nhiều danh mục con. Danh mục con **không thể** làm cha của danh mục khác.
- "Tên nội bộ" chỉ để nhân viên dễ nhận biết trong danh sách, **không hiển thị công khai**. Tên/slug hiển thị công khai cho khách nằm ở tab **Content** (theo từng ngôn ngữ VI/EN).
- Bật `show_on_landing` nếu muốn danh mục này xuất hiện ở khối nổi bật trên trang chủ.
- Slug tự sinh từ tên tiếng Việt, có thể sửa tay nếu cần.

### Tạo Thương hiệu (Brand) / Nhà sản xuất (Manufacturer)

- Khác với Sản phẩm/Danh mục, **Brand và Manufacturer chỉ có 1 tên/mô tả dùng chung**, không có tab riêng cho tiếng Việt/tiếng Anh (chỉ phần SEO mới tách VI/EN).
- Manufacturer có ô "Quốc gia", phải nhập đúng **mã 2 ký tự viết hoa** theo chuẩn ISO (VD: Việt Nam = `VN`, Đức = `DE`, Trung Quốc = `CN`).
- ⚠️ **Brand không có "thùng rác"**: nếu bấm Xóa là mất luôn, không khôi phục được. Cân nhắc kỹ trước khi xóa.

### Nhóm thuộc tính (Filter Group): quan trọng nếu sản phẩm có nhiều biến thể

Khi tạo 1 Filter Group (VD: "Size", "Màu sắc"), có 1 công tắc tên **"Dùng làm chiều biến thể" (`is_variant_dimension`)**.

⚠️ **Nếu quên bật công tắc này**, nhóm thuộc tính đó sẽ hiển thị bình thường trong sản phẩm (khách vẫn lọc được) nhưng **sẽ không dùng được để sinh ra các biến thể sản phẩm** (VD: không tạo được "Áo màu Đỏ size M", "Áo màu Đỏ size L" riêng biệt). Nhớ bật công tắc này cho các nhóm như Size, Màu sắc trước khi qua bước tạo biến thể.

---

## Bước 1. Tab General

- Chọn **Danh mục** (có thể chọn nhiều).
- Chọn **Danh mục chính**: chỉ được chọn trong số các danh mục đã tick ở trên. Nếu bỏ tick danh mục đang là "danh mục chính", ô này sẽ tự trống lại, nhớ chọn lại.
- Bật **"Kích hoạt" (is_active)** khi sẵn sàng bán, xem lưu ý bên dưới về các trường bắt buộc.
- Công tắc **"Hiển thị giá" (show_price)**: nếu tắt, giá sẽ bị ẩn hoàn toàn trên web (dùng cho sản phẩm dạng "Liên hệ báo giá"). Đây khác với việc Ẩn/Kích hoạt cả sản phẩm.

## Bước 2. Tab Content (có 2 tab con: VI / EN)

- Nhập **Tên sản phẩm**, **Mô tả ngắn**, **Mô tả chi tiết** (trình soạn thảo rich text) cho từng ngôn ngữ.
- **Slug** (đường dẫn URL) sẽ **tự sinh theo tên** khi bạn gõ tên, nhưng **chỉ trong lần tạo mới**. Sau khi sản phẩm đã được lưu ít nhất 1 lần, việc sửa lại Tên **sẽ không tự đổi Slug nữa** (kể cả tên VI lẫn tên EN), tránh việc URL đang chạy tốt bị đổi âm thầm chỉ vì admin sửa 1 lỗi chính tả trong tên.
  - ⚠️ Muốn đổi URL của sản phẩm đã lưu, phải **tự gõ tay vào ô Slug**.
  - Nếu slug bạn định đặt đang trùng với 1 URL cũ (đang được chuyển hướng sang chỗ khác), hệ thống sẽ **báo lỗi và chặn lưu** cho tới khi bạn đổi sang slug khác hoặc xử lý redirect đó trước (xem mục Redirect trong nhóm SEO & GEO).
- Có thể thêm các khối nội dung tùy ý (Info Sections) và câu hỏi thường gặp (FAQ) riêng cho từng ngôn ngữ.

## Bước 3. Tab Pricing & Stock (Giá & Tồn kho)

⚠️ **SKU, Giá, Số lượng tồn kho chỉ bắt buộc nhập khi sản phẩm đang ở trạng thái "Kích hoạt" (is_active = bật).** Nếu bạn để sản phẩm chưa kích hoạt (đang soạn nháp), có thể lưu mà chưa cần điền các trường này, nhưng **nhớ quay lại điền đầy đủ trước khi bật Kích hoạt**, nếu không hệ thống sẽ báo lỗi thiếu trường lúc đó.

Có **2 khối giá riêng biệt**: một khối cho tiếng Việt (mặc định tiền **VNĐ**) và một khối cho tiếng Anh (mặc định tiền **USD**).

> ⚠️ Đây **không phải** là "1 sản phẩm bán 2 giá cho 2 loại khách khác nhau". Đây là 2 mức giá hiển thị tương ứng khi khách xem web ở phiên bản tiếng Việt hoặc tiếng Anh, cần điền **cả 2 khối** nếu web phục vụ cả 2 ngôn ngữ.

## Bước 4. Tab Images (Ảnh)

- Upload nhiều ảnh vào đây. Xem quy cách tỷ lệ ảnh chi tiết ở file `01-quy-cach-hinh-anh.md`.
- Mỗi ảnh có công tắc **"Ảnh ưu tiên" (is_card_priority)**: dùng để chọn ảnh nào hiện trên card sản phẩm (ảnh chính + ảnh hiện khi rê chuột). **Chỉ được chọn tối đa 2 ảnh**, nếu tick ảnh thứ 3, hệ thống tự bỏ tick lại kèm cảnh báo.
- Khung crop ảnh khi upload là **crop tự do**, hệ thống không tự ép đúng tỷ lệ, nên tự crop/chọn ảnh đúng tỷ lệ khuyến nghị trước khi upload để không bị vỡ hình trên web.

## Bước 5. Tab Videos

⚠️ **Tab này đang tạm khóa**, chỉ hiện dòng thông báo "tính năng đang tạm khóa", không phải lỗi, chưa dùng được video sản phẩm ở giai đoạn hiện tại.

## Bước 6. Tab Filters (Thuộc tính)

Danh sách thuộc tính hiện ra tùy theo các Nhóm thuộc tính đang hoạt động (Size, Màu sắc, Chất liệu...). Tick chọn các giá trị áp dụng cho sản phẩm này (VD: sản phẩm có Size S/M/L, màu Đỏ/Xanh). Nhóm "Màu sắc" sẽ hiện chấm màu tương ứng.

## Bước 7. Tab Variants (Biến thể): quy trình bắt buộc theo đúng thứ tự

Đây là phần dễ bị "làm sai thứ tự" nhất. Phải làm theo đúng 3 bước:

1. **Lưu sản phẩm trước** (bấm Save ít nhất 1 lần): chưa lưu thì không tạo được biến thể.
2. Ở tab **Filters** (bước 6), chọn các giá trị thuộc những **Nhóm thuộc tính đã bật "Dùng làm chiều biến thể"** (VD: chọn Size S/M/L và Màu Đỏ/Xanh).
3. Quay lại tab Variants, bấm nút **"Generate variants" (Tạo biến thể)**: hệ thống sẽ tự sinh ra toàn bộ tổ hợp (VD: Đỏ-S, Đỏ-M, Đỏ-L, Xanh-S, Xanh-M, Xanh-L).

Sau khi sinh xong, với **mỗi biến thể** có thể sửa riêng: SKU riêng, ảnh riêng, giá riêng (cả VNĐ và USD), tồn kho riêng, và **Trạng thái hiển thị** (`availability_status`).

> ⚠️ **Lưu ý về "Trạng thái hiển thị" của biến thể**: đây là cờ **ép cứng** cách Google Merchant hiển thị hàng (VD: "Còn hàng", "Hết hàng", "Đặt trước"), **độc lập hoàn toàn với Số lượng tồn kho thực tế**. Có 3 lựa chọn:
> - `auto`: hiển thị theo đúng tồn kho thực (khuyến nghị dùng mặc định).
> - `out_of_stock`: ép hiển thị "Hết hàng" dù kho vẫn còn số lượng.
> - `pre_order`: ép hiển thị "Đặt trước".
>
> Đừng nhầm đây là nơi cập nhật tồn kho. Muốn cập nhật số lượng thực, sửa ô "Tồn kho" riêng của biến thể đó.

Có công cụ **"Bulk availability status"**: chọn 1 trạng thái rồi bấm Apply để **áp dụng hàng loạt** cho nhiều biến thể cùng lúc (bản thân ô này chỉ là công cụ thao tác nhanh, không tự lưu trạng thái riêng).

## Bước 8. Tab SEO (VI / EN)

Khi mở form lần đầu, hệ thống **tự động điền sẵn** 1 lần: Tiêu đề SEO lấy theo Tên sản phẩm, Mô tả SEO lấy theo Mô tả ngắn, ảnh chia sẻ (og_image) lấy theo ảnh đầu tiên trong tab Images.

⚠️ Việc tự điền này **chỉ chạy 1 lần lúc field đang trống**, nếu sau đó bạn đổi Tên sản phẩm, các trường SEO đã điền **sẽ không tự cập nhật lại theo tên mới**. Nếu đổi tên sản phẩm, nhớ vào tab SEO kiểm tra và sửa lại tay nếu cần.

## Bước 9. Tab GEO/AI (VI / EN)

Điền các trường hỗ trợ AI/tìm kiếm hiểu sản phẩm tốt hơn: Tóm tắt AI, Trường hợp sử dụng, Đối tượng khách hàng, Gợi ý ngữ cảnh cho AI, Thông tin chính (key facts).

## Bước 10. Tab LLMs (VI / EN)

Đây chỉ là màn hình **xem trước**, nội dung được **tự động tổng hợp** từ tab GEO/AI ở bước 9. Sửa trực tiếp trong tab này **sẽ không có tác dụng**, muốn thay đổi nội dung phải sửa ở tab GEO/AI rồi bấm nút **"Regenerate"** để tổng hợp lại.

## Bước 11. Tab JSON-LD (VI / EN)

Tab này chỉ cho xem trước dữ liệu cấu trúc (structured data, giúp Google hiểu sản phẩm) và công tắc Bật/Tắt.

⚠️ Mặc định công tắc **"Tự động sinh" (is_auto_generated) đang bật**, nghĩa là nội dung này **tự động ghi đè lại mỗi lần bạn lưu sản phẩm** theo mẫu có sẵn. Nếu muốn tự sửa tay nội dung JSON-LD:
1. Phải tắt công tắc "Tự động sinh" trước, nhưng **không tắt được ngay tại tab này**.
2. Vào menu ẩn **"JSON-LD Schemas"** (đường dẫn trực tiếp: `/admin/json-ld-schemas`): đây là nơi **duy nhất** chỉnh tay được nội dung JSON-LD của bất kỳ sản phẩm/danh mục/thương hiệu/bài viết nào.

---

## Sau khi lưu sản phẩm: các thao tác trên danh sách

| Thao tác | Ý nghĩa |
|---|---|
| **Ẩn / Hiện** (toggle) | Tạm ngưng hiển thị công khai, không mất dữ liệu, bật lại được ngay |
| **Xóa** | Chuyển vào thùng rác, lọc "Đã xóa" để khôi phục |
| **Xóa vĩnh viễn** | Mất hoàn toàn, không khôi phục được |
| **Audit** | Tải về báo cáo (file markdown) kiểm tra chất lượng dữ liệu sản phẩm này: còn thiếu SEO, thiếu ảnh, thiếu trường gì... dùng để rà soát trước khi kích hoạt bán |

---

*Tiếp theo: `03-quy-trinh-blog.md`.*
