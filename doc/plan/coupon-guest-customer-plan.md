# Phương án: Coupon (mã giảm giá) + Quản lý khách hàng + Quản lý đơn hàng

> **Status:** Draft — chờ xác nhận, chưa code.
> **Liên quan:** `doc/improvement-backlog.md` #13 (Coupon/Discount — gap đã biết), `order_inquiries` (luồng checkout guest hiện tại), roadmap thanh toán online (dự kiến sau, chưa làm đợt này).
> **Cập nhật 2026-07-10:** Đã chốt roadmap — cần quản lý đơn hàng + khách hàng (xem lịch sử mua, gán coupon riêng cho từng khách) + thanh toán online về sau. Roadmap này đổi khuyến nghị từ "Phương án A" sang **Phương án B mở rộng, gắn với customer identity** (mục 4–6).
> **Cập nhật tiếp (cùng ngày):** Đã chốt phạm vi đợt này **chỉ ở chế độ guest/`order_inquiries`** — **Bước 6 (mở thanh toán online, sửa `OrderService`/`OrderPolicy`) tạm hoãn, không nằm trong scope hiện tại.** Xem mục 6 và mục 10.

---

## 1. Hiện trạng (audit)

### Coupon
Chưa tồn tại. `doc/improvement-backlog.md` mục 13 đã ghi nhận gap này — ô "Mã giảm giá" đã bị gỡ khỏi trang `/gio-hang` vì chưa có backend, tránh dựng nút giả.

### "Guest mode" — checkout thật vs checkout hiện tại
- `POST /api/v1/orders` (`OrderController`, S49) — **checkout thật có snapshot đơn hàng** — đang bọc `auth:sanctum` (`routes/api.php:111`). **Guest không đặt được đơn qua route này.**
- Luồng guest đang chạy thật là **`order_inquiries`** ("Liên hệ đặt hàng" — stand-in cho checkout, `routes/api.php:97`, migration `2026_07_09_130000_create_order_inquiries_table.php`):
  - Khách điền `name`, `phone`, `email` (nullable), `message`
  - Cart summary được build server-side từ cart đã resolve (không tin dữ liệu client)
  - Không thanh toán online — sale gọi lại qua `channel` (`zalo` | `phone` | `email`)
  - `user_id` nullable + `session_id` — cùng pattern ownership với `carts`/`wishlists`

→ **"Lưu thông tin khách hàng đơn giản" thực ra đã có sẵn** trong `order_inquiries`. Không cần bảng `customers` mới nếu mục đích chỉ là ghi nhận ai đã hỏi mua — thêm bảng lúc này là dư thừa so với nhu cầu.

---

## 2. Roadmap đã chốt (2026-07-10)

- Thanh toán online: **có trong roadmap tương lai, nhưng đợt này chưa làm** — hiện tại chủ động **giữ nguyên chế độ guest** qua `order_inquiries`, không mở khoá route `orders` (route này vẫn khoá `auth:sanctum` như hiện tại, không đổi).
- Cần **quản lý đơn hàng** (đã có nền: `OrderResource` Filament + `OrderController` API, S49).
- Cần **quản lý khách hàng**: xem 1 khách đã mua/đặt gì, tổng chi tiêu — **chưa có Filament resource nào cho việc này** (chỉ có `OrderResource`, không có `CustomerResource`).
- Cần **gán coupon riêng cho từng khách cụ thể** (không chỉ mã công khai ai cũng nhập được).

→ Vì thanh toán online sắp lên và cần identity khách ổn định (không chỉ session_id), quyết định kiến trúc quan trọng nhất là: **khách hàng — kể cả khách vãng lai — cần được định danh bằng 1 bản ghi ổn định để tra cứu lịch sử mua + gán coupon**, không phải hai bảng rời rạc `order_inquiries`/`orders` mỗi cái một identity riêng.

---

## 3. Phương án Coupon

> **Đã chốt roadmap (mục 2) → dùng Phương án B**, mở rộng thêm ở mục 4/5 để gắn với customer identity thay vì chỉ `phone`/`session_id` rời rạc. Phương án A giữ lại bên dưới chỉ để tham khảo lý do vì sao không chọn.

### Phương án A — Nhẹ, gắn thẳng vào `order_inquiries` *(không chọn — giữ lại để đối chiếu)*

**Migration mới:**
```php
// database/migrations/{n}_create_coupons_table.php
Schema::create('coupons', function (Blueprint $table) {
    $table->id();
    $table->string('code', 50)->unique();
    $table->enum('type', ['percent', 'fixed']);          // App\Enums\CouponType
    $table->decimal('value', 12, 2);
    $table->decimal('min_order_amount', 12, 2)->nullable();
    $table->unsignedInteger('max_uses')->nullable();      // null = không giới hạn
    $table->unsignedInteger('used_count')->default(0);
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index('is_active');
    $table->index('expires_at');
});
```

**Sửa `order_inquiries` (migration mới, không sửa migration cũ):**
```php
// database/migrations/{n}_add_coupon_fields_to_order_inquiries_table.php
Schema::table('order_inquiries', function (Blueprint $table) {
    $table->string('coupon_code', 50)->nullable()->after('message');
    $table->decimal('discount_amount', 12, 2)->nullable()->after('coupon_code');
});
```
Snapshot đơn giản — cùng triết lý với `orders.shipping_address` (ghi lại tại thời điểm submit, không FK sống).

**API:**
```
POST /api/v1/cart/coupon        → validate code, server tự tính discount_amount, trả về cho FE hiển thị
DELETE /api/v1/cart/coupon      → bỏ coupon đang áp trên cart hiện tại (session/user scoped)
```
Khi submit `POST /api/v1/order-inquiries`: backend **re-validate** `coupon_code` (không tin số đã tính ở bước trước), tự tính lại `discount_amount`, ghi vào record.

**Kiến trúc theo đúng pattern CLAUDE.md:**
```
Controller (CartCouponController) → CouponService (validate + tính discount)
                                   → CouponRepository (query coupons)
FormRequest: ApplyCouponRequest
Resource: CouponResource (trả code, type, value, discount_amount)
Enum: CouponType (percent|fixed)
Filament: CouponResource (CRUD quản lý mã)
```

**Hạn chế:** không chặn được 1 khách dùng cùng 1 code nhiều lần qua nhiều session/thiết bị khác nhau — guest không có identity ổn định ngoài `phone` nhập tay ở bước cuối.

---

### Phương án B — Đầy đủ, có bảng redemption riêng

Thêm mọi thứ ở Phương án A, **cộng thêm**:

```php
// database/migrations/{n}_create_coupon_redemptions_table.php
Schema::create('coupon_redemptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
    $table->uuid('user_id')->nullable();
    $table->string('phone', 30)->nullable();              // định danh guest ổn định hơn session_id
    $table->string('session_id', 255)->nullable();
    $table->foreignId('order_inquiry_id')->nullable()->constrained()->nullOnDelete();
    $table->decimal('discount_amount', 12, 2);
    $table->timestamps();

    $table->index('coupon_id');
    $table->index('phone');
    $table->index('user_id');

    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
});
```
Thêm cột `coupons.max_uses_per_customer` (nullable) → `CouponService` check số lần `phone` (hoặc `user_id`) đã redeem trước khi cho áp mã.

**Lợi ích:** enforce được giới hạn dùng/khách, sẵn nền để gắn thẳng vào `orders` thật (đổi `order_inquiry_id` → thêm `order_id` nullable) khi thanh toán online lên — không phải làm lại từ đầu.

**Đánh đổi:** build thêm 1 bảng + logic tracking cho một luồng checkout (`order_inquiries`) có khả năng bị thay thế khi lên thanh toán thật — rủi ro effort bỏ ra cho thứ tạm.

---

## 4. Kiến trúc "khách hàng" — dùng `users`, không tạo bảng `customers` mới

**Quyết định:** `users` (đã có `role` = `admin`/`customer`, `addresses`, `orders` relation) chính là bảng khách hàng. Không tạo `customers` song song — sẽ trùng lặp identity với `users` và phải đồng bộ 2 chiều mãi mãi.

**Vấn đề cần giải:** guest hiện tại không có `user_id` ổn định (`order_inquiries.user_id` nullable, chỉ có `session_id`/`phone` nhập tay). Để "xem khách đã mua/đặt gì" và "gán coupon cho khách cụ thể", guest phải được **resolve về đúng 1 `users` record** — kể cả khi họ chưa từng đăng ký mật khẩu.

**Cách làm — theo đúng pattern đã có sẵn trong `User` model** (`app/Models/User.php:66-77`): `email` đã có `email_hash` (sha256, deterministic) làm blind index để tra cứu vì cột thật bị mã hoá. Áp dụng y hệt cho `phone`:

```php
// database/migrations/{n}_add_phone_hash_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->string('phone_hash', 64)->nullable()->after('phone');
    $table->index('phone_hash');
});
```
```php
// User.php — mirror phone() accessor theo đúng cách email() đang làm
protected function phone(): Attribute
{
    return Attribute::make(
        get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
        set: fn (?string $value) => $value ? [
            'phone'      => Crypt::encryptString($value),
            'phone_hash' => hash('sha256', preg_replace('/\D/', '', $value)),
        ] : ['phone' => null, 'phone_hash' => null],
    );
}
```

**Luồng resolve khách tại checkout (guest hoặc khi thanh toán online lên):**
1. Khách nhập `phone` (+ `email` optional) → `CustomerResolverService` tra theo `phone_hash` (rồi `email_hash` nếu có) trong `users`.
2. Có → dùng `user_id` đó, gắn vào `order_inquiries.user_id` / `orders.user_id`. Không → tạo `users` record mới với `role = customer`, `password = null` (giống cơ chế Google-only account đã có sẵn — cột `password` vốn đã nullable cho case này).
3. Từ lúc này mọi đơn/inquiry của khách đó đều có `user_id` — lịch sử mua hàng là `User::orders()` + `User::hasMany(OrderInquiry::class)` (cần thêm relation này vào `User.php`), không cần bảng tổng hợp riêng.

> Đây là điểm khác biệt so với mục 1: `order_inquiries` vẫn giữ `name/phone/email` như snapshot tại thời điểm hỏi mua (khách có thể đặt hộ người khác), nhưng **thêm `user_id` được resolve chuẩn** để truy vấn theo khách thay vì theo từng đơn rời rạc.

---

## 5. Coupon gắn với customer identity

Cập nhật Phương án B (mục 3) để dùng `user_id` đã resolve ở mục 4 làm khoá chính, không phải `phone` thô:

```php
// coupon_redemptions — sửa lại so với bản nháp ở mục 3
$table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
$table->uuid('user_id');                                    // NOT NULL — resolve xong mới redeem được
$table->foreignId('order_inquiry_id')->nullable()->constrained()->nullOnDelete();
$table->uuid('order_id')->nullable();                       // gắn khi thanh toán online lên, FK → orders.id
$table->decimal('discount_amount', 12, 2);
$table->timestamps();
```

**Coupon cá nhân hoá — bảng mới, đúng nhu cầu "gán mã cho khách cụ thể":**
```php
// database/migrations/{n}_create_coupon_customer_table.php
Schema::create('coupon_customer', function (Blueprint $table) {
    $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
    $table->uuid('user_id');
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->timestamp('assigned_at')->useCurrent();
    $table->timestamp('used_at')->nullable();

    $table->primary(['coupon_id', 'user_id']);
});
```
`coupons` thêm cột `scope` enum (`App\Enums\CouponScope`: `public` | `targeted`) — `targeted` bắt buộc phải có mặt trong `coupon_customer` mới áp được, `CouponService::validate()` check thêm điều kiện này khi `scope = targeted`.

**Filament — resource mới cần có:**
- `CustomerResource` (dựa trên `User` model, filter `role = customer`) — hiện **chưa tồn tại**, chỉ có `OrderResource`. Cần relation manager hiển thị: danh sách đơn hàng, tổng chi tiêu (`orders.total_amount` sum theo `payment_status = paid`), danh sách `order_inquiries`, và action "Gán coupon" → ghi vào `coupon_customer`.
- `OrderResource` (đã có) — thêm cột/filter theo khách hàng.

---

## 6. Trình tự triển khai đề xuất (để không phải làm lại khi thanh toán online lên)

```
1. users.phone_hash + CustomerResolverService (mục 4)     ← nền tảng, làm trước tiên
2. coupons + coupon_redemptions + coupon_customer (mục 5)  ← phụ thuộc bước 1 (cần user_id)
3. order_inquiries.user_id resolve qua CustomerResolverService khi submit
4. Filament CustomerResource (xem lịch sử mua/hỏi, gán coupon)
5. Filament OrderResource — bổ sung filter/hiển thị theo khách hàng

── HOÃN, không thuộc scope đợt này (xem mục 10) ──────────────────────────────
6. Mở thanh toán online: bỏ auth:sanctum bắt buộc trên POST /api/v1/orders
   (guest checkout → CustomerResolverService resolve user_id y hệt bước 1,
   không cần đăng ký/đăng nhập) — coupon_redemptions.order_id bắt đầu được dùng
```

**Scope đợt này = bước 1–5.** Toàn bộ vẫn xoay quanh `order_inquiries` (chế độ guest hiện tại) — không đụng vào `OrderService`/`OrderPolicy`/`PlaceOrderRequest` (route `orders` giữ nguyên khoá `auth:sanctum`).

---

## 7. Việc cần làm theo `CLAUDE.md` checklist khi triển khai

```
□ Enum CouponType (percent|fixed), CouponScope (public|targeted) — không raw string
□ FormRequest cho apply coupon / gán coupon cho khách — không $request->validate() trong controller
□ CouponService tự tính lại discount server-side ở cả bước validate lẫn bước submit — không tin số từ client
□ phone_hash / email_hash — không bao giờ query trực tiếp cột encrypted (phone/email)
□ ApiResponse envelope cho mọi response
□ Migration mới, không sửa migration cũ đã chạy (đúng convention project)
□ Filament CustomerResource + OrderResource cập nhật
□ Feature test: resolve khách mới/khách cũ theo phone, áp mã public, áp mã targeted đúng/sai khách, hết hạn, hết lượt
□ Cập nhật doc/API_ROUTE_MAP.md + doc/databse.md sau khi có migration thật
□ users.marketing_consent_at + checkbox consent riêng cho mục đích tiếp thị/coupon cá nhân hoá (mục 8)
□ phone_hash / email_hash đổi sang HMAC-SHA256 + secret key trong .env (mục 8)
```

---

## 8. Compliance & Consent (Nghị định 13/2023/NĐ-CP — bảo vệ dữ liệu cá nhân)

> Không thay thế tư vấn pháp lý — đây là các điểm kỹ thuật cần bổ sung để kiến trúc mục 4/5 không vượt phạm vi đồng ý của khách. Nội dung consent + chính sách bảo mật công khai cần người phụ trách pháp lý/compliance của công ty duyệt trước khi launch.

**Vấn đề cốt lõi:** kiến trúc ở mục 4 gộp guest → `users` record + giữ lịch sử mua để phục vụ mục 5 (coupon cá nhân hoá). Đây là **2 mục đích xử lý dữ liệu khác nhau** (Điều 13 NĐ13):
- **Mục đích A — xử lý đơn hàng:** không cần hỏi đồng ý riêng, khách chủ động cung cấp để được liên hệ/giao hàng.
- **Mục đích B — giữ hồ sơ liên tục để nhận diện lần sau + target coupon:** là profiling/tiếp thị (Điều 17 NĐ13) → **cần opt-in riêng**, phải cho từ chối dễ dàng, và khách phải biết hồ sơ này tồn tại + xoá được (Điều 9, 11 NĐ13).

**Bổ sung schema:**
```php
// database/migrations/{n}_add_marketing_consent_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->timestamp('marketing_consent_at')->nullable()->after('phone_hash');
});
```
- Không tick consent → vẫn tạo được `user_id` (cần cho vận hành đơn hàng) nhưng `CouponService` **không được** dùng lịch sử mua của khách đó để gán coupon `targeted`, và không giữ liên kết lịch sử ngoài mục đích đơn hàng hiện tại đang xử lý.
- Checkbox riêng tại form order-inquiry/checkout, tách khỏi checkbox "đồng ý điều khoản đặt hàng": *"Đồng ý lưu thông tin để nhận diện các lần mua sau và nhận ưu đãi cá nhân hoá"*.
- Trang chính sách bảo mật công khai cần nêu: dữ liệu thu thập gì, mục đích, thời gian lưu, cách yêu cầu xoá/xem dữ liệu.

**Lỗ hổng kỹ thuật đi kèm (không phải yêu cầu luật, nhưng nên sửa cùng đợt):** `phone_hash`/`email_hash` hiện dùng SHA256 trần không salt/pepper. Số điện thoại VN chỉ có ~3×10⁸ tổ hợp thực tế (đầu số cố định + 7 số) → brute-force toàn bộ bằng GPU trong vài phút nếu DB rò rỉ, hash gần như vô tác dụng bảo vệ. `email_hash` hiện có (đã tồn tại trong code, `User.php:66-77`) có cùng vấn đề.
- Đổi sang HMAC-SHA256 với secret key lưu ở `.env` (không lưu DB) — vẫn deterministic để lookup nhưng không dò ngược được nếu chỉ có bản dump DB.

---

## 9. Đăng nhập lại cho khách được auto-tạo từ guest checkout

**Vấn đề:** `users` record tạo tự động ở mục 4 không có mật khẩu (`password = null`, giống cơ chế Google-only account đã có). Khách đó tự đăng nhập lại bằng cách nào để tự xem đơn/coupon của mình (ngoài việc admin tra qua `CustomerResource`)?

**Các hướng đã xét:**

| Hướng | Chi phí/setup | Giới hạn |
|---|---|---|
| **Chưa cho tự đăng nhập** | Không việc gì thêm — khớp hiện trạng (storefront chưa có trang login) | Khách không tự xem được, chỉ admin tra qua Filament |
| **Set mật khẩu sau đơn đầu** | Tái dùng `PasswordResetController` đã có sẵn, gửi link mời đặt mật khẩu | Cần email hợp lệ; tỷ lệ khách thực sự set password thường thấp (~10-20%) |
| **OTP SMS/Zalo ZNS theo số điện thoại** | Cần tích hợp thêm SMS gateway (eSMS/Speedsms) hoặc Zalo ZNS — hạ tầng mới | Tốn phí mỗi lần gửi OTP |
| **Google OAuth** | **Miễn phí, gần như đã sẵn sàng** — `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` đã có trong `.env.example`, `SocialAuthController` (Socialite) đã implement. Chỉ cần: tạo OAuth Client trên Google Cloud Console, khai báo redirect URI domain thật, chuyển consent screen "Testing" → "In production" (scope `email`+`profile` là non-sensitive, không cần Google review dài) | **Chỉ hoạt động nếu khách có email trùng tài khoản Google đã dùng.** Guest hiện tại chủ yếu chỉ để lại `phone` (`order_inquiries.email` là optional) → phần lớn khách **phone-only sẽ không đăng nhập lại được** qua hướng này |

**Đánh giá:** Google OAuth nhẹ và miễn phí nhất về mặt kỹ thuật, nhưng không tự giải quyết được cho nhóm khách chỉ để lại số điện thoại — vốn là đa số trong luồng `order_inquiries` hiện tại. Hai hướng không loại trừ nhau:
- **Google OAuth** phục vụ nhóm khách có email.
- Nhóm **phone-only** tạm thời giữ ở dạng "chưa tự đăng nhập được" (giống hướng đầu tiên) — chỉ admin quản lý qua `CustomerResource`, tự đăng nhập cho nhóm này (OTP SMS/Zalo) làm sau nếu có nhu cầu thật.

**Status:** đang xét kết hợp Google OAuth (khách có email) + phone-only chưa tự login — **chưa chốt cuối cùng**, cần xác nhận trước khi thêm vào trình tự triển khai ở mục 6.

> Vì Bước 6 (thanh toán online) đang hoãn (mục 10), câu hỏi đăng nhập ở mục này **chưa cấp thiết** — chỉ thực sự cần chốt khi bắt đầu mở thanh toán online. Ở scope hiện tại (bước 1–5), `CustomerResolverService` chỉ dùng nội bộ để gộp identity + gán coupon qua Filament, khách không cần tự đăng nhập gì cả.

---

## 10. Phạm vi (scope) đã chốt cho đợt triển khai này — 2026-07-10

**Quyết định:** giữ nguyên chế độ **guest** (`order_inquiries`) làm checkout chính. **Không** mở thanh toán online, **không** đụng vào code đang chạy của luồng `orders` thật ở đợt này.

**Đã audit code thật để xác nhận việc hoãn Bước 6 là hợp lý** — nếu làm ngay sẽ phải sửa các giả định cốt lõi sau (không phải việc nhỏ):
- `OrderService::placeOrder(User $user, ...)` (`app/Services/Order/OrderService.php:25,35`) bắt buộc `$user` không null và bắt buộc đã có `Address` record đã lưu (`$user->addresses()->findOrFail(...)`) — guest chưa từng có `addresses` row (bảng này FK cứng vào `user_id`).
- `PlaceOrderRequest::rules()` bắt `address_id` phải `exists:addresses,id`.
- `OrderPolicy` so sánh thẳng `$order->user_id === $user->id` — không có nhánh cho khách chưa đăng nhập.
- Phần tính tổng tiền trong `placeOrder()` nằm trong transaction có lock chống oversell (đã có comment giải thích kỹ trong code) — thêm discount vào đây phải rất cẩn thận để không phá lock.

→ Đây chính xác là lý do kỹ thuật để **không** làm Bước 6 chung đợt này. **Scope thực tế của đợt này chỉ là mục 1–5**: `phone_hash`, `CustomerResolverService`, hệ thống coupon (áp vào `order_inquiries`, chưa áp vào `orders`), `CustomerResource` + `OrderResource` trong Filament. Không có bảng/route nào của `orders` thật bị sửa.

---

## 11. Thanh toán online — xác nhận điều kiện + đánh giá effort tích hợp cổng

**Đã xác nhận: thanh toán online = chỉ dành cho khách đã đăng nhập.** `POST /api/v1/orders` (nơi có `payment_method`/`payment_status`) bắt buộc `auth:sanctum` (`routes/api.php:111`) — guest không chạm được vào bước thanh toán, vẫn chỉ đi qua `order_inquiries` (sale gọi lại thủ công, không thanh toán online). Điều này khớp đúng với quyết định giữ guest ở mục 10 — **không có mâu thuẫn nào cần xử lý thêm.**

**Đánh giá effort tích hợp cổng thanh toán (VNPay...) — tách 2 trường hợp:**

- **Nếu chỉ tích hợp cho luồng user đã đăng nhập (đang chạy sẵn, S49) — additive, KHÔNG lớn:**
  - `orders.payment_method` (string, nullable) + `payment_status` (enum `unpaid|paid|refunded`) đã được scaffold sẵn (`Order.php:32-33,43`) — chưa có code gateway thật (không có `VNPay`/`momo`/`zalopay` nào trong `app/`), chỉ là field placeholder.
  - Việc cần làm: `PaymentController` mới (return URL + IPN endpoint), `PaymentService` mới (build payment URL, verify checksum HMAC), có thể thêm bảng `payment_transactions` để idempotent hoá IPN (gateway có thể gọi callback nhiều lần).
  - Chỉ cần thêm hàm `markPaid()`/`markFailed()` vào `OrderService` + 1 scheduled command hết hạn đơn `pending` chưa thanh toán (giống pattern `cart:prune` đã có) — **không sửa logic `placeOrder()` hiện tại**.

- **Nếu tích hợp cho GUEST checkout — kéo theo toàn bộ Bước 6 đã hoãn (mục 10):** phải sửa `OrderService::placeOrder()`, `PlaceOrderRequest`, `OrderPolicy` trước, rồi mới gắn cổng thanh toán lên trên — effort cộng dồn của cả 2 việc.

**Vì scope đợt này giữ guest, chưa mở `orders` thật → chưa cần tích hợp thanh toán online lúc này.** Việc này chỉ trở thành cần thiết khi có quyết định làm Bước 6.

---

## 12. Rủi ro dữ liệu khi triển khai (mục 1–5)

**Đánh giá chung: rủi ro THẤP** — toàn bộ thay đổi trong scope đã chốt đều additive (thêm cột nullable / thêm bảng mới), không có `ALTER` đổi kiểu hay `DROP` cột nào, không đụng `orders`/`OrderService`/`OrderPolicy`. Nhưng có 3 điểm cụ thể cần làm đúng cách, không phải "cứ chạy migrate là xong":

**1. Backfill `phone_hash` cho user đã tồn tại — điểm cần cẩn thận nhất**
- Cột mới nullable → an toàn khi migrate, không mất data.
- User cũ (đã có `phone` mã hoá) sẽ có `phone_hash = null` cho tới khi chạy script backfill riêng (decrypt từng `phone`, hash, update). Bỏ qua bước này → `CustomerResolverService` tra không ra user cũ theo phone → **tạo trùng 1 user mới** cho cùng 1 khách, tách lịch sử mua làm 2 nơi (data bị phân mảnh, không mất nhưng sai).
- Thứ tự bắt buộc: thêm cột → chạy backfill (chunk theo batch, không lock bảng lâu) → mới thêm index trên `phone_hash`.

**2. Nhầm identity khi resolve theo phone — rủi ro thật, không phải lý thuyết**
- VN có tình huống chung 1 số điện thoại (vợ/chồng, đổi chủ sim, số bàn gia đình đặt hộ). Resolve cứng theo `phone_hash` có thể gộp nhầm 2 người khác nhau vào chung 1 `user` record → lịch sử mua/coupon của người này lẫn sang người kia.
- Cách giảm rủi ro: chỉ dùng `phone_hash` để **match** `user_id`, không ghi đè `name`/`email` đã lưu trên `users` nếu khác — giữ nguyên các field trên `order_inquiries` làm snapshot riêng của từng đơn (đã đúng theo cách đang lưu hiện tại).

**3. Coupon race condition (rủi ro logic, không phải mất data)**
- Áp mã có `max_uses` giới hạn: 2 request cùng lúc redeem cùng mã có thể vượt `max_uses` nếu không `lockForUpdate()` khi tăng `used_count`. Codebase đã có sẵn pattern này (`CartService::addItem`, `OrderService::placeOrder` đều dùng `lockForUpdate()` cho race tương tự) — chỉ cần áp dụng lại đúng pattern.

**Khuyến nghị vận hành:**
- Chạy `pg_dump` backup trước khi apply migration lên production (thói quen chuẩn, không phải vì rủi ro cao ở đây).
- Rollback (`migrate:rollback`) nếu cần chỉ xoá đúng bảng/cột mới thêm (`coupons`, `coupon_redemptions`, `coupon_customer`, `users.phone_hash`, `users.marketing_consent_at`) — không đụng dữ liệu `users`/`orders`/`order_inquiries` gốc.
- Viết feature test riêng cho case "2 `order_inquiries` khác tên cùng số điện thoại" để chặn sớm rủi ro #2 trước khi có dữ liệu thật.

---

*Đây là tài liệu phương án — chưa có dòng code nào được viết. Scope đã chốt = mục 1–5 (mục 10); thanh toán online yêu cầu đăng nhập, chưa cần làm ở đợt này (mục 11); rủi ro dữ liệu ở mục 12 cần xử lý đúng thứ tự khi triển khai. Xác nhận hướng consent ở mục 8 (hoặc điều chỉnh) rồi bắt đầu code theo đúng convention CLAUDE.md.*
