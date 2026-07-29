# 02. Quy trình tạo & quản lý Sản phẩm

> Đọc `00-phan-quyen.md` trước nếu chưa đọc. Các khái niệm "Ẩn/Xóa", "Draft" được nhắc lại ở đây.
> File này liệt kê đầy đủ từng trường trong form Sản phẩm, theo đúng thứ tự tab thật trên màn hình.

---

## Bước 0. Chuẩn bị trước khi tạo sản phẩm

Trước khi vào tạo sản phẩm, nên có sẵn (nếu cần dùng):

1. **Danh mục (Category)**: sản phẩm phải thuộc ít nhất 1 danh mục.
2. **Thương hiệu (Brand)**: nếu cần gắn thương hiệu.
3. **Nhà sản xuất (Manufacturer)**: nếu cần.
4. **Hướng dẫn chọn size (Size Guide)**: tùy chọn, quản lý tại menu Content → Size Guides. Nếu gắn vào sản phẩm, trang sản phẩm sẽ hiện link "Hướng dẫn chọn size" kèm modal cho khách xem.
5. **Nhóm thuộc tính (Filter Group)**, ví dụ Size, Màu sắc: **chỉ cần chuẩn bị trước nếu sản phẩm này có nhiều biến thể** (VD: 1 áo có nhiều size/màu). Xem lưu ý quan trọng ở mục "Nhóm thuộc tính" bên dưới.

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

Nếu chưa có Filter Group nào đang hoạt động, tab **Filters** trong form sản phẩm sẽ chỉ hiện dòng nhắc "Chưa có filter group nào. Tạo tại Catalog → Filter Groups."

---

## Bước 1. Tab General (Chung)

| Trường | Bắt buộc? | Ghi chú |
|---|---|---|
| **Danh mục** (categories) | Không, nhưng nên có ít nhất 1 | Chọn nhiều được |
| **Danh mục chính** (primary_category_id) | Không | Danh sách chọn **chỉ hiện các danh mục đã tick ở ô Danh mục phía trên**, chưa tick Danh mục nào thì ô này trống. Dùng để xác định breadcrumb trong JSON-LD. Nếu bỏ tick danh mục đang là "danh mục chính" khỏi ô Danh mục, ô này tự trống lại, nhớ chọn lại |
| **Thương hiệu** (brand_id) | Không | |
| **Nhà sản xuất** (manufacturer_id) | Không | |
| **Hướng dẫn size** (size_guide_id) | Không | Xem Bước 0 |
| **SKU** | ⚠️ Chỉ bắt buộc khi "Kích hoạt" đang bật | Phải **duy nhất trên toàn hệ thống**, không được trùng SKU của sản phẩm khác (kể cả sản phẩm đã ẩn) |
| **Kích hoạt** (is_active) | | Mặc định **BẬT SẴN** khi tạo sản phẩm mới, xem cảnh báo bên dưới |
| **Hiển thị giá trên website** (show_price) | | Mặc định bật. Tắt đi sẽ **ẩn toàn bộ giá** trên web, thay bằng nút "Liên hệ báo giá". Khác với việc Ẩn/Kích hoạt cả sản phẩm |
| **Khóa nội dung + SEO (VI & EN) khỏi AI/MCP** | | Chi tiết ở `00-phan-quyen.md` mục 6. Chỉ bật khi đã viết xong nội dung và không muốn AI ghi đè lại |

> ⚠️ **Lưu ý dễ nhầm nhất khi tạo sản phẩm mới**: công tắc **"Kích hoạt" mặc định đang BẬT SẴN**, không phải tắt sẵn như nhiều người nghĩ. Nếu bạn đang soạn nháp một sản phẩm chưa đầy đủ thông tin, hãy **chủ động tắt "Kích hoạt" trước**, nếu không hệ thống sẽ bắt buộc nhập ngay SKU, Giá, Số lượng tồn kho (xem Bước 3) mới lưu được.

---

## Bước 2. Tab Content (Nội dung, có 2 tab con: VI / EN)

Tiếng Việt là ngôn ngữ **bắt buộc**. Tiếng Anh là **tùy chọn**, có thể để trống toàn bộ nếu web chưa cần bán bằng tiếng Anh.

### Các trường chính (cho mỗi ngôn ngữ)

- **Tên sản phẩm**: bắt buộc với VI, tùy chọn với EN.
- **Slug** (đường dẫn URL): bắt buộc với VI. Với EN, chỉ bắt buộc **nếu đã nhập Tên (EN)**.
  - Slug **tự sinh theo tên** khi bạn gõ tên, nhưng **chỉ trong lần tạo mới**. Sau khi sản phẩm đã được lưu ít nhất 1 lần, việc sửa lại Tên **sẽ không tự đổi Slug nữa** (kể cả tên VI lẫn tên EN), tránh việc URL đang chạy tốt bị đổi âm thầm chỉ vì admin sửa 1 lỗi chính tả trong tên.
  - ⚠️ Muốn đổi URL của sản phẩm đã lưu, phải **tự gõ tay vào ô Slug**.
  - Slug phải duy nhất theo từng ngôn ngữ (VI và EN được trùng nhau, nhưng không được trùng slug VI/EN của sản phẩm khác).
  - Nếu slug bạn định đặt đang trùng với 1 URL cũ (đang được chuyển hướng sang chỗ khác), hệ thống sẽ **báo lỗi và chặn lưu** cho tới khi bạn đổi sang slug khác hoặc xử lý redirect đó trước (xem mục Redirect trong nhóm SEO & GEO). Áp dụng cho cả slug VI lẫn slug EN.
- **Mô tả ngắn**: textarea 3 dòng.
- **Mô tả đầy đủ**: trình soạn thảo rich text.
- **Thông tin chi tiết bổ sung** (Info Sections, có thể thêm nhiều mục, kéo thả sắp xếp): mỗi mục gồm Tiêu đề + Nội dung (rich text), **mỗi mục sẽ hiện thành 1 accordion riêng trên trang sản phẩm**. Ví dụ: "Chất liệu & Thành phần", "Hướng dẫn bảo quản", "Giao hàng & Đổi trả". Mặc định không có mục nào, tự thêm nếu cần.
- **FAQ** (mục riêng, thu gọn sẵn): thêm các cặp Câu hỏi/Trả lời, **tối đa 10 cặp**. Dữ liệu này được đưa vào JSON-LD FAQPage (giúp Google hiện rich snippet hỏi đáp) và vào tài liệu llms.txt cho AI đọc.

---

## Bước 3. Tab Pricing & Stock (Giá & Tồn kho)

⚠️ **SKU** (ở tab General), **Giá**, **Số lượng tồn kho** chỉ bắt buộc nhập khi sản phẩm đang ở trạng thái "Kích hoạt" đang bật. Nếu bạn tắt Kích hoạt để soạn nháp, có thể lưu mà chưa cần điền các trường này, nhưng **nhớ quay lại điền đầy đủ trước khi bật Kích hoạt**, nếu không hệ thống sẽ báo lỗi thiếu trường lúc đó.

### Khối giá: 2 khối riêng biệt theo ngôn ngữ

> ⚠️ Đây **không phải** là "1 sản phẩm bán 2 giá cho 2 loại khách khác nhau". Đây là 2 mức giá hiển thị tương ứng khi khách xem web ở phiên bản tiếng Việt hoặc tiếng Anh, cần điền **cả 2 khối** nếu web phục vụ cả 2 ngôn ngữ.

| Khối | Đơn vị tiền mặc định | Các trường |
|---|---|---|
| 🇻🇳 Giá Việt Nam | VND | Đơn vị tiền, Giá, Giá khuyến mãi |
| 🇬🇧 Giá tiếng Anh | USD | Currency, Price, Sale Price |

- **Đơn vị tiền** (currency) có thể đổi giữa 8 lựa chọn: VND, USD, EUR, SGD, JPY, KRW, CNY, THB. Đổi đơn vị sẽ tự đổi luôn ký hiệu tiền hiển thị trước ô Giá (₫, $, €...). Giá trị này được dùng trong JSON-LD schema (`priceCurrency`), nên chọn đúng loại tiền thực tế sản phẩm đang bán.
- **Giá khuyến mãi** (sale_price): để trống nếu không có khuyến mãi.
- **Hiển thị giá gốc bị gạch** (show_original_price, mặc định BẬT): khi có Giá khuyến mãi, bật công tắc này sẽ hiện thêm giá gốc gạch ngang bên cạnh giá khuyến mãi. Tắt đi thì chỉ hiện giá khuyến mãi, không hiện giá gốc bị gạch.

### Tồn kho

- **Số lượng tồn kho** (stock_quantity): đây là **1 ô dùng chung cho cả sản phẩm**, không tách riêng theo ngôn ngữ như giá. Nếu sản phẩm có nhiều biến thể (size/màu), tồn kho thực tế cần theo dõi ở **từng biến thể riêng** trong tab Variants (Bước 7), ô này chỉ áp dụng cho sản phẩm không có biến thể.

---

## Bước 4. Tab Images (Ảnh)

| Trường | Ghi chú |
|---|---|
| **Ảnh** (upload) | Bắt buộc cho mỗi dòng ảnh. Tên file tự động chuyển về không dấu (Latin), trùng tên tự thêm số thứ tự (VD: `quan-tay-1.jpg`). Khung crop khi upload là **crop tự do**, hệ thống không tự ép đúng tỷ lệ, nên tự crop/chọn ảnh đúng tỷ lệ khuyến nghị trước khi upload (xem `01-quy-cach-hinh-anh.md`) để không bị vỡ hình trên web |
| **Alt Text** | Tùy chọn, nên điền để hỗ trợ SEO ảnh và khả năng tiếp cận (accessibility) |
| **Ảnh ưu tiên** (is_card_priority) | Chọn **tối đa 2 ảnh**, tick ảnh thứ 3 hệ thống tự bỏ tick lại kèm cảnh báo. Trong 2 ảnh được chọn, **ảnh có thứ tự đứng trước** (kéo thả lên trên) sẽ là ảnh chính hiện trên card sản phẩm, ảnh còn lại là ảnh hiện khi khách rê chuột vào |

Có thể **kéo thả để sắp xếp thứ tự** các ảnh. Thứ tự này quyết định luôn ảnh nào là "ảnh đầu tiên", ảnh đầu tiên sẽ tự động được dùng làm **OG Image** mặc định ở tab SEO (xem Bước 8) nếu bạn chưa tự điền OG Image riêng.

---

## Bước 5. Tab Videos

⚠️ **Tab này đang tạm khóa**, chỉ hiện dòng thông báo "tính năng đang tạm khóa", không phải lỗi, chưa dùng được video sản phẩm ở giai đoạn hiện tại.

---

## Bước 6. Tab Filters (Thuộc tính)

Danh sách thuộc tính hiện ra tùy theo các Nhóm thuộc tính đang hoạt động (Size, Màu sắc, Chất liệu...). Tick chọn các giá trị áp dụng cho sản phẩm này (VD: sản phẩm có Size S/M/L, màu Đỏ/Xanh). Nhóm "Màu sắc" sẽ hiện chấm màu tương ứng bên cạnh tên.

---

## Bước 7. Tab Variants (Biến thể): quy trình bắt buộc theo đúng thứ tự

Đây là phần dễ bị "làm sai thứ tự" nhất. Phải làm theo đúng 3 bước:

1. **Lưu sản phẩm trước** (bấm Save ít nhất 1 lần): chưa lưu thì không tạo được biến thể.
2. Ở tab **Filters** (Bước 6), chọn các giá trị thuộc những **Nhóm thuộc tính đã bật "Dùng làm chiều biến thể"** (VD: chọn Size S/M/L và Màu Đỏ/Xanh). Mục "Step 1" ở đầu tab Variants sẽ hiện tóm tắt các giá trị bạn vừa chọn, theo từng nhóm, để bạn kiểm tra lại trước khi tạo.
3. Quay lại tab Variants, bấm nút **"Generate Combinations"**: hệ thống sẽ tự sinh ra toàn bộ tổ hợp còn thiếu (VD: Đỏ-S, Đỏ-M, Đỏ-L, Xanh-S, Xanh-M, Xanh-L). Thao tác này **an toàn để bấm lại nhiều lần**: chỉ tạo thêm tổ hợp còn thiếu, không sửa hay xóa các biến thể đã có sẵn.

### Sửa từng biến thể sau khi sinh

Mỗi biến thể có các trường:

| Trường | Bắt buộc? | Ghi chú |
|---|---|---|
| **Tổ hợp** (optionValues) | Có | Hiện dạng nhãn màu ở phía trên để xem nhanh (VD: Color: Đỏ, Size: M). Bên dưới là ô có thể **sửa lại tổ hợp**: mỗi nhóm (Color, Size...) chỉ được chọn đúng 1 giá trị, chọn 2 giá trị cùng nhóm sẽ báo lỗi "Mỗi nhóm (Color, Size, ...) chỉ được chọn 1 giá trị." Dùng để sửa lại tổ hợp bị sai, hoặc để tạo tay 1 biến thể mới bằng nút **"+ Add variant manually"** (không bắt buộc phải luôn dùng nút Generate) |
| **SKU** | Có | Phải duy nhất trong toàn bộ bảng biến thể (không chỉ riêng sản phẩm này) |
| **Ảnh biến thể** (image_id) | Không | Chọn từ các ảnh đã upload ở tab Images của chính sản phẩm này. Để trống thì hiển thị giống ảnh chính của sản phẩm |
| **Giá** (₫) | Có | |
| **Giá khuyến mãi** (₫) | Không | |
| **Giá (USD)** | Không | |
| **Giá khuyến mãi (USD)** | Không | |
| **Tồn kho** | Có, mặc định 0 | Có gợi ý ngay bên cạnh cho biết Google Merchant sẽ hiển thị gì: hết hàng nếu = 0, hoặc theo đúng override Trạng thái bên dưới |
| **Trạng thái hiển thị** (availability_status) | | Xem cảnh báo bên dưới |
| **Kích hoạt** (is_active) | Mặc định bật | Tắt để ẩn riêng 1 biến thể này mà không cần xóa |

> ⚠️ **Lưu ý về "Trạng thái hiển thị" của biến thể**: đây là cờ **ép cứng** cách Google Merchant hiển thị hàng, **độc lập hoàn toàn với Số lượng tồn kho thực tế**. Có 3 lựa chọn:
> - **Tự động**: hiển thị theo đúng tồn kho thực, tồn kho = 0 thì hiện Hết hàng, còn lại hiện Có sẵn (khuyến nghị dùng mặc định).
> - **Hết hàng (ép)**: ép hiển thị "Hết hàng" dù kho vẫn còn số lượng.
> - **Pre-order**: ép hiển thị "Đặt trước".
>
> Đừng nhầm đây là nơi cập nhật tồn kho. Muốn cập nhật số lượng thực, sửa ô "Tồn kho" riêng của biến thể đó. Mỗi biến thể tương ứng với 1 "Offer" trong dữ liệu JSON-LD của sản phẩm.

Có công cụ **"Đặt trạng thái cho tất cả biến thể"** (bulk availability status): chọn 1 trạng thái rồi bấm "Áp dụng" để **áp dụng hàng loạt** cho mọi biến thể đang hiện trong danh sách. Bản thân ô này chỉ là công cụ thao tác nhanh một lần, không tự lưu trạng thái riêng, vẫn sửa lại được từng biến thể sau khi áp dụng.

---

## Bước 8. Tab SEO (VI / EN)

Mỗi ngôn ngữ có 3 nhóm trường: **Meta Tags**, **Open Graph** (thu gọn sẵn), **Twitter Card** (thu gọn sẵn).

### Meta Tags

| Trường | Ghi chú |
|---|---|
| **Meta Title** | Tự điền 1 lần từ Tên sản phẩm nếu để trống. Có bộ đếm ký tự sống động ngay cạnh ô nhập, đổi màu theo độ dài: xám khi trống, cam khi ngoài khoảng 50-70 ký tự, xanh khi vừa khoảng |
| **Meta Description** | Tự điền 1 lần từ Mô tả ngắn nếu để trống. Bộ đếm ký tự tương tự, khoảng tối ưu 120-160 ký tự |
| **Meta Keywords** | Tùy chọn, phân cách bằng dấu phẩy |
| **Canonical URL** | Để trống thì tự tạo từ Slug hiện tại, chỉ cần tự điền tay trong trường hợp đặc biệt (VD: nội dung syndicate/đăng lại từ nguồn khác muốn trỏ canonical ra ngoài) |
| **Robots** | Mặc định `index,follow` (cho phép Google index bình thường). Đổi sang các lựa chọn `noindex` nếu muốn 1 sản phẩm cụ thể **không xuất hiện trên kết quả tìm kiếm** nhưng vẫn giữ trang chạy bình thường trên web (VD: sản phẩm ngừng bán nhưng vẫn giữ URL cũ cho khách đã có link) |

> ⚠️ Việc tự điền Meta Title/Meta Description **chỉ chạy 1 lần lúc field đang trống**, nếu sau đó bạn đổi Tên sản phẩm, các trường SEO đã điền **sẽ không tự cập nhật lại theo tên mới**. Nếu đổi tên sản phẩm, nhớ vào tab SEO kiểm tra và sửa lại tay nếu cần.

### Open Graph (ảnh/tiêu đề khi chia sẻ lên Facebook, Zalo...)

| Trường | Ghi chú |
|---|---|
| **OG Title** | Tự điền từ Meta Title nếu để trống |
| **OG Description** | Tự điền từ Meta Description nếu để trống |
| **OG Image** | Tự điền theo **ảnh đầu tiên** ở tab Images nếu để trống (xem thứ tự ảnh ở Bước 4). Khuyến nghị kích thước 1200×630px nếu tự điền tay |
| **OG Type** | Mặc định `product` |

### Twitter Card

| Trường | Ghi chú |
|---|---|
| **Card Type** | `summary` hoặc `summary_large_image` (mặc định) |
| **Twitter Title** | Tự điền từ Meta Title nếu để trống |
| **Twitter Description** | Tự điền từ Meta Description nếu để trống |

---

## Bước 9. Tab GEO/AI (VI / EN)

Nhóm trường giúp AI (ChatGPT, Gemini, Perplexity...) hiểu và trả lời đúng về sản phẩm khi khách hỏi ở nơi khác ngoài Google.

| Trường | Ghi chú |
|---|---|
| **AI Summary** | 2-4 câu mô tả sản phẩm, đây là nội dung xuất hiện **đầu tiên** trong tài liệu llms.txt của sản phẩm |
| **Use Cases** | Trường hợp sử dụng, VD: "Chiếu sáng nội thất, trưng bày bảo tàng, kệ bán lẻ" |
| **Target Audience** | Đối tượng khách hàng, VD: "Nhà thiết kế chiếu sáng, nhà thầu điện" |
| **LLM Context Hint** | Gợi ý ngữ cảnh thêm cho AI, VD: "Cạnh tranh với Philips Hue, đạt CE/RoHS, không chống nước" |
| **Key Facts** (mục riêng, thu gọn sẵn) | Các cặp Thông tin/Giá trị, VD: "Chứng nhận: CE / RoHS". Có thể thêm nhiều dòng, kéo thả sắp xếp |

---

## Bước 10. Tab LLMs (VI / EN)

Đây là màn hình **xem trước + 1 công tắc thật**:

- **Preview**: nội dung được **tự động tổng hợp** từ tab GEO/AI (Bước 9). Sửa trực tiếp trong khung xem trước này **sẽ không có tác dụng**, muốn thay đổi nội dung phải sửa ở tab GEO/AI rồi bấm nút **"Regenerate"** để tổng hợp lại.
- **Published to llms.txt** (công tắc thật, có tác dụng ngay khi lưu): tắt đi để **loại sản phẩm này khỏi** tài liệu llms.txt công khai, mà không cần xóa nội dung GEO/AI đã nhập.
- **Last synced**: thời điểm lần gần nhất nội dung được tổng hợp lại.

---

## Bước 11. Tab JSON-LD (VI / EN)

Tab này cho xem trước dữ liệu cấu trúc (structured data, giúp Google hiểu sản phẩm) và 2 điều khiển:

- **Auto/Manual** (nhãn màu ở đầu mỗi schema, chỉ để xem, không sửa được ở đây): mặc định **"⚡ Auto"**, nghĩa là nội dung này **tự động ghi đè lại mỗi lần bạn lưu sản phẩm** theo mẫu có sẵn.
- **Active** (công tắc thật): bật/tắt để **có chèn schema này vào thẻ `<head>` của trang hay không**. Tắt Active không đổi trạng thái Auto/Manual, hai thứ này độc lập với nhau.

Nếu muốn tự sửa tay nội dung JSON-LD:
1. Phải đổi trạng thái từ "Auto" sang "Manual" trước, nhưng **không đổi được ngay tại tab này**.
2. Vào menu ẩn **"JSON-LD Schemas"** (đường dẫn trực tiếp: `/admin/json-ld-schemas`): đây là nơi **duy nhất** chỉnh tay được nội dung JSON-LD của bất kỳ sản phẩm/danh mục/thương hiệu/bài viết nào, và cũng là nơi đổi Auto sang Manual.

---

## Sau khi lưu sản phẩm: các thao tác trên danh sách

### Tìm kiếm & lọc

Trang danh sách sản phẩm hỗ trợ tìm theo Tên hoặc SKU, và lọc theo: Trạng thái Kích hoạt (Bật/Tắt), Danh mục (chọn nhiều), và bộ lọc "Đã xóa" (Trashed) để xem/khôi phục sản phẩm đã xóa mềm.

### Các thao tác trên từng dòng

| Thao tác | Ý nghĩa |
|---|---|
| **Ẩn / Hiện** (toggle) | Tạm ngưng hiển thị công khai, không mất dữ liệu, bật lại được ngay |
| **Xóa** | Chuyển vào thùng rác, lọc "Đã xóa" để khôi phục |
| **Xóa vĩnh viễn** | Mất hoàn toàn, không khôi phục được |
| **Audit** | Tải về báo cáo (file markdown) kiểm tra chất lượng dữ liệu sản phẩm này: còn thiếu SEO, thiếu ảnh, thiếu trường gì... dùng để rà soát trước khi kích hoạt bán |

---

*Tiếp theo: `03-quy-trinh-blog.md`.*
