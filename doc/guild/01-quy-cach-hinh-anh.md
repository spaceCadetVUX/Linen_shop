# 01 — Quy cách ảnh cho từng vị trí

> Số liệu trong bài này lấy trực tiếp từ code hiển thị thật của web (không phải khuyến nghị chung chung), nên cứ theo đúng bảng này là ảnh sẽ lên đẹp, không bị vỡ hay crop xấu.

**Nguyên tắc chung:** hệ thống dùng kiểu hiển thị "lấp đầy khung, cắt phần thừa, giữ phần trên/giữa" (giống Instagram crop ảnh vuông). Nếu ảnh gốc không đúng tỷ lệ khung, phần **thừa hai bên hoặc phía dưới sẽ bị cắt bỏ** — vì vậy nên đặt chủ thể chính (mặt sản phẩm, logo, người mẫu...) ở **giữa hoặc nửa trên** của ảnh.

---

## 1. Ảnh sản phẩm

Sản phẩm hiển thị ảnh ở **2 nơi khác tỷ lệ nhau**:

| Vị trí | Tỷ lệ khung | Ghi chú |
|---|---|---|
| Card sản phẩm (trang danh sách, trang chủ, "sản phẩm liên quan") | **3 : 4** (dọc) | |
| Trang chi tiết sản phẩm (ảnh lớn/gallery) | **2 : 3** (dọc, hơi cao hơn 3:4) | |

**Khuyến nghị:** chụp ảnh gốc theo tỷ lệ **2:3**, vì hệ thống sẽ tự cắt bớt 1 chút ở card 3:4 (crop giữ phần trên) — ảnh vẫn đẹp. Nếu chụp theo 3:4 thì lên trang chi tiết ảnh sẽ bị "thêm viền"/kéo dãn nhẹ.

- Định dạng: mọi định dạng ảnh (jpg, png, webp...).
- Dung lượng: không giới hạn cứng trong hệ thống, nhưng nên nén ảnh dưới **2–3MB** để trang tải nhanh.
- Mỗi sản phẩm chỉ được đánh dấu **tối đa 2 ảnh "ưu tiên"** (ảnh chính + ảnh hover khi rê chuột) — tick ảnh thứ 3 hệ thống sẽ tự bỏ tick lại.

## 2. Ảnh danh mục (Category)

| Vị trí | Tỷ lệ khung | Ghi chú |
|---|---|---|
| Trang danh sách danh mục (`/category`) | **4 : 5** (dọc) | |
| Khối "nổi bật" trên trang chủ (khi bật `show_on_landing`) | Không ép tỷ lệ cứng — khung gần như **full màn hình** | Dùng ảnh dọc, độ phân giải cao, chủ thể ở giữa |

## 3. Logo Thương hiệu (Brand) / Nhà sản xuất (Manufacturer)

Logo hiện **chưa được hiển thị công khai** trên web bán hàng — chỉ dùng nội bộ trong danh sách quản trị (hiển thị cao 40px). Vì vậy:
- Không có tỷ lệ bắt buộc.
- Khuyến nghị: dùng ảnh **nền trong suốt (PNG)**, logo gần vuông hoặc ngang, để hiển thị đẹp trong bảng danh sách và khi dùng làm ảnh chia sẻ mạng xã hội (og_image) của trang thương hiệu.

## 4. Ảnh đại diện bài viết Blog (`featured_image`)

Ảnh này hiển thị ở **3 nơi khác tỷ lệ nhau** — đây là ảnh có nhiều ràng buộc nhất hệ thống:

| Vị trí | Tỷ lệ khung |
|---|---|
| Card danh sách blog — máy tính | **3 : 4** (dọc) |
| Card danh sách blog — điện thoại/tablet | **4 : 5** (dọc) |
| Trang chi tiết bài viết (ảnh hero đầu bài) | **16 : 9** (ngang, rộng) |

**Khuyến nghị:** vì ảnh vừa phải đẹp ở khung dọc (card) vừa phải đẹp ở khung ngang (hero), hãy chọn ảnh có **chủ thể chính nằm chính giữa khung hình**, tránh để chủ thể sát viền trên/dưới hoặc trái/phải — như vậy dù bị cắt kiểu nào ảnh cũng không mất nội dung quan trọng.

## 5. Ảnh banner khuyến mãi (Promotion)

- Không ép tỷ lệ cứng — banner nằm trong khung **khổ ngang rộng**, chiều cao co giãn theo màn hình (trên máy tính khung cao gần 70% màn hình).
- **Khuyến nghị:** dùng ảnh khổ ngang, độ phân giải tối thiểu **1600 × 900px**.
- Ảnh bị cắt giữ phần trên trước — **không đặt chữ/thông tin quan trọng ở nửa dưới ảnh**, vì phần này dễ bị cắt mất trên màn hình nhỏ.
- Vị trí banner (Trái/Phải) chỉ đổi thứ tự hiển thị 2 cột, không đổi kích thước ảnh.

## 6. Ảnh/File trong Thư viện Media chung

- Chấp nhận: **ảnh, video, và file PDF**.
- Giới hạn dung lượng: **20MB / file** — đây là giới hạn cứng, upload file lớn hơn sẽ báo lỗi.
- Upload lại đúng 1 file ảnh giống hệt trước đó sẽ không tạo bản trùng (hệ thống tự nhận diện).

## 7. Ảnh chia sẻ mạng xã hội (OG Image / SEO)

| Vị trí | Định dạng | Dung lượng tối đa | Tỷ lệ khuyến nghị |
|---|---|---|---|
| Ảnh mặc định toàn site (mục **Cài đặt Analytics**) | JPG, PNG, WebP | **2MB** | **1.91 : 1** (chuẩn Facebook/Zalo — khoảng 1200 × 630px) |
| Ảnh chia sẻ riêng từng Sản phẩm/Danh mục | *(tự động lấy từ ảnh sản phẩm/danh mục đã upload)* | — | — |

⚠️ **Lưu ý:** ảnh chia sẻ (og_image) của từng Sản phẩm/Danh mục **tự động lấy ảnh đầu tiên đã upload** (vốn là ảnh dọc 2:3 hoặc 4:5) — khi dán link sản phẩm vào Zalo/Facebook, ảnh preview có thể bị cắt xấu vì khung chia sẻ chuẩn là khổ ngang 1.91:1. Nếu cần ảnh chia sẻ đẹp cho 1 sản phẩm quan trọng, nên chọn ảnh đầu tiên có bố cục ở giữa, tránh chủ thể sát 2 bên.

## 8. Ảnh Hero (trang chủ / trang Shop / trang Blog)

| Vị trí | Định dạng | Dung lượng tối đa | Khung hiển thị |
|---|---|---|---|
| Hero trang chủ (Landing Setup) | JPG, PNG, WebP | Không giới hạn cứng — **tự nén dưới 3MB** | Gần full màn hình theo chiều cao |
| Hero trang Shop (Shop Setting) | JPG, PNG, WebP | Không giới hạn cứng — **tự nén dưới 3MB** | Cao bằng nửa/toàn màn hình tùy thiết bị |
| Hero trang Blog (Blog Setting) | JPG, PNG, WebP | **5MB** (giới hạn cứng) | Dải ngang, cao vừa phải |

- Cả 3 ảnh hero đều **không ép tỷ lệ cố định** — chỉ cần dùng ảnh khổ ngang, độ phân giải cao (khuyến nghị chiều rộng ≥ 1920px) để không bị vỡ nét khi hiển thị full màn hình.
- Không dùng ảnh dọc cho các vị trí này — ảnh dọc sẽ bị cắt bỏ rất nhiều ở 2 bên.

---

## Tóm tắt nhanh (bảng tra cứu)

| Vị trí | Tỷ lệ | Định dạng | Giới hạn dung lượng |
|---|---|---|---|
| Card sản phẩm | 3:4 | Bất kỳ | Không giới hạn (nên <3MB) |
| Chi tiết sản phẩm | 2:3 | Bất kỳ | Không giới hạn (nên <3MB) |
| Danh mục (list) | 4:5 | Bất kỳ | Không giới hạn |
| Danh mục (trang chủ) | Full màn hình | Bất kỳ | Không giới hạn |
| Logo Brand/Manufacturer | Tự do (nên vuông, nền trong suốt) | Bất kỳ | Không giới hạn |
| Blog card (PC) | 3:4 | Bất kỳ | Không giới hạn |
| Blog card (mobile) | 4:5 | Bất kỳ | Không giới hạn |
| Blog chi tiết (hero) | 16:9 | Bất kỳ | Không giới hạn |
| Banner khuyến mãi | Khổ ngang tự do (≥1600×900) | Bất kỳ | Không giới hạn |
| Media Library | — | Ảnh/Video/PDF | **20MB** |
| OG Image mặc định site | 1.91:1 (~1200×630) | JPG/PNG/WebP | **2MB** |
| Hero trang chủ/Shop | Khổ ngang, ≥1920px rộng | JPG/PNG/WebP | Không giới hạn (nên <3MB) |
| Hero Blog | Khổ ngang, ≥1920px rộng | JPG/PNG/WebP | **5MB** |

---

*Tiếp theo: `02-quy-trinh-san-pham.md`.*
