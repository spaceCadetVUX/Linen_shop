---
Báo cáo lỗi — Product Create/Edit (Filament Admin)

🔴 Bug 1 — Thiếu required → crash 500 (NOT NULL violation)

Vị trí:
- app/Filament/Resources/ProductResource.php:131 — field translations.vi.name không có ->required()
- app/Filament/Resources/ProductResource.php:226 — price chỉ required khi is_active = true
- app/Filament/Resources/ProductResource.php:298 — stock_quantity cùng pattern
- app/Filament/Resources/ProductResource/Pages/CreateProduct.php:16-20 — gán thẳng $vi['name'] ?? null, $vi['price'] ?? null vào data

Ràng buộc DB (0007_create_products_table.php):

┌─────────────────────────┬───────────────────────────────────┐
│           Cột           │            Constraint             │
├─────────────────────────┼───────────────────────────────────┤
│ products.name           │ NOT NULL (dòng 22)                │
├─────────────────────────┼───────────────────────────────────┤
│ products.slug           │ NOT NULL + UNIQUE (dòng 23)       │
├─────────────────────────┼───────────────────────────────────┤
│ products.price          │ NOT NULL, decimal(12,2) (dòng 30) │
├─────────────────────────┼───────────────────────────────────┤
│ products.stock_quantity │ NOT NULL, default 0 (dòng 33)     │
└─────────────────────────┴───────────────────────────────────┘

Kịch bản tái hiện:
1. Vào Create Product, tắt toggle is_active (tạo draft)
2. Bỏ trống tên vi / giá / stock → form validation pass (vì required chỉ áp khi active)
3. Bấm Create → Product::create(['name' => null, 'price' => null, ...]) → PostgreSQL SQLSTATE 23502 not-null violation → màn hình lỗi 500, không có thông báo validation nào

Bản chất: default 0 của stock_quantity không cứu được vì Filament gửi null tường minh trong INSERT — default chỉ áp dụng khi cột bị bỏ khỏi câu lệnh.

Fix đề xuất: ->required() vô điều kiện cho translations.vi.name + translations.vi.slug; trong mutateFormDataBeforeCreate coalesce price ?? 0, stock_quantity ?? 0 cho draft.

---
🔴 Bug 2 — Không validate unique slug → crash 500 khi trùng

Vị trí:
- ProductResource.php:141-146 — translations.vi.slug: chỉ có helperText "Must be unique per locale", không có rule nào
- ProductResource.php:170-173 — translations.en.slug: tương tự
- So sánh: sku (dòng 111) có ->unique(..., ignoreRecord: true) — làm đúng, nhưng slug thì không

Ràng buộc DB:
- products.slug → UNIQUE (0007, dòng 23)
- product_translations (locale, slug) → UNIQUE composite (2026_04_26_002, dòng 40)

Kịch bản tái hiện:
1. Đã có sản phẩm "Khăn lụa tơ tằm" (slug khan-lua-to-tam)
2. Tạo sản phẩm mới cùng tên → slug auto-generate y hệt
3. Save → SQLSTATE 23505 unique violation → 500. Với luồng Create, product chưa kịp tạo; với luồng Edit (đổi tên trùng), lỗi nổ giữa chừng sau khi một phần data đã ghi.

Fix đề xuất: unique rule 2 tầng — top-level slug check bảng products (ignore record hiện tại), per-locale slug check product_translations với điều kiện locale + ignore translation row của chính product đó (cần closure rule vì là composite unique).

---
🔴 Bug 3 — Filter chọn lúc Create bị mất im lặng (silent data loss)

Vị trí:
- ProductResource.php:471-503 — Tab Filters render CheckboxList tên filter_group_{id} (field thường, không phải relationship)
- EditProduct.php:79-95 — afterSave() gọi saveFilterValues() → sync vào pivot product_filter_values
- CreateProduct.php:27-47 — afterCreate() chỉ save translations, không có saveFilterValues()

Kịch bản tái hiện:
1. Create Product → tab Filters → tick 5 giá trị filter → Save
2. Không lỗi, không cảnh báo
3. Mở lại Edit → tab Filters trống toàn bộ. Product không xuất hiện trong faceted search trên storefront (Meilisearch filter_value_ids = [])

Bản chất: giá trị filter_group_X nằm trong $this->data nhưng không ai đọc; khi truyền vào Product::create() bị $fillable whitelist lọc bỏ → mất không dấu vết.

Fix đề xuất: copy logic saveFilterValues() sang CreateProduct::afterCreate() (hoặc extract ra trait/method chung cho cả 2 page).

---
🔴 Bug 4 — sale_price mất khi Create, sau đó bị xóa luôn ở lần Edit kế tiếp

Vị trí: CreateProduct.php:42
->only(['name', 'slug', 'short_description', 'description', 'price', 'currency'])
//  ← thiếu 'sale_price', dù cột đã tồn tại từ migration 2026_05_16
So sánh: EditProduct::saveTranslations() dòng 112 có sale_price trong danh sách.

Chuỗi hậu quả (tệ hơn mất đơn thuần):
1. Create: nhập sale_price vi = 450.000₫ → products.sale_price được lưu (qua mutateFormDataBeforeCreate:21) nhưng product_translations.sale_price = NULL
2. Mở Edit: mutateFormDataBeforeFill() (EditProduct.php:43-46) đổ form từ translation row → field "Giá khuyến mãi (vi)" hiển thị trống
3. Admin sửa bất kỳ field nào khác rồi Save → mutateFormDataBeforeSave:72: $data['sale_price'] = $vi['sale_price'] ?? null → products.sale_price bị ghi đè thành NULL
4. Kết quả: giá khuyến mãi biến mất khỏi cả DB lẫn storefront mà không ai chủ động xóa

Fix đề xuất: thêm 'sale_price' vào only() tại CreateProduct.php:42.

---
🟡 Bug 5 — Primary Category bị clear vô điều kiện

Vị trí: ProductResource.php:68-72
->afterStateUpdated(function (Set $set, ?array $state) {
    // Clear primary if it's no longer in the selected list  ← comment nói một đằng
    $set('primary_category_id', null);                        // ← code làm một nẻo
})

Kịch bản: Edit product đã có Primary Category = "Khăn trải bàn". Thêm 1 category phụ vào list → primary lập tức bị null. Nếu admin không để ý chọn lại → primary_category_id = NULL → BreadcrumbList JSON-LD mất cấp danh mục.

Fix đề xuất:
->afterStateUpdated(function (Set $set, Get $get, ?array $state) {
    if (! in_array($get('primary_category_id'), $state ?? [])) {
        $set('primary_category_id', null);
    }
})

---
🟢 Điểm phụ (không khẩn cấp)

┌─────┬──────────────────────────────────────────────────────┬──────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  #  │                        Vị trí                        │                                                    Vấn đề                                                    │
├─────┼──────────────────────────────────────────────────────┼──────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ 6   │ ProductResource.php:406-412 (Videos)                 │ Input sort_order nhập tay + ->orderColumn('sort_order') drag-drop cùng quản lý một cột → drag-drop ghi đè    │
│     │                                                      │ giá trị nhập tay                                                                                             │
├─────┼──────────────────────────────────────────────────────┼──────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ 7   │ EditProduct.php:111-115                              │ Xóa trắng short_description/description không persist — filter loại giá trị rỗng nên updateOrCreate giữ giá  │
│     │                                                      │ trị cũ                                                                                                       │
├─────┼──────────────────────────────────────────────────────┼──────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ 8   │ ProductResource.php:1697-1701                        │ charCounter() nhận $min nhưng không dùng — hint không hiển thị trạng thái "quá ngắn" dù charCounterColor có  │
│     │                                                      │ xử lý                                                                                                        │
├─────┼──────────────────────────────────────────────────────┼──────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ 9   │ SEO tab (Group                                       │ Nghi vấn tạo row seo_meta rỗng (chỉ có locale) ngay cả khi admin không nhập gì lúc Create — cần verify       │
│     │ ->relationship('seoMetaVi'/'seoMetaEn'))             │ runtime, chưa khẳng định                                                                                     │
└─────┴──────────────────────────────────────────────────────┴──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘

---
Tóm tắt mức độ

┌─────────────────────────────┬───────────┬────────────┬────────────────────────┐
│             Bug             │   Loại    │   Mức độ   │   Trang bị ảnh hưởng   │
├─────────────────────────────┼───────────┼────────────┼────────────────────────┤
│ 1. Thiếu required           │ Crash 500 │ Cao        │ Create + Edit          │
├─────────────────────────────┼───────────┼────────────┼────────────────────────┤
│ 2. Slug không unique        │ Crash 500 │ Cao        │ Create + Edit          │
├─────────────────────────────┼───────────┼────────────┼────────────────────────┤
│ 3. Filter mất lúc create    │ Data loss │ Cao        │ Create                 │
├─────────────────────────────┼───────────┼────────────┼────────────────────────┤
│ 4. sale_price mất → bị wipe │ Data loss │ Cao        │ Create → lan sang Edit │
├─────────────────────────────┼───────────┼────────────┼────────────────────────┤
│ 5. Primary category clear   │ UX / SEO  │ Trung bình │ Create + Edit          │
└─────────────────────────────┴───────────┴────────────┴────────────────────────┘



SQLSTATE[42501]: Insufficient privilege: must be owner of table page_translations

  I checked ownership directly: the app connects as Postgres role app, but page_translations is owned by postgres (a different role) — while the pages table I just created is    
  correctly owned by app. This is a pre-existing inconsistency in this DB (that table was created outside the normal app-role migration flow at some point), not something wrong  
  with my migration SQL. I don't have superuser credentials to fix ownership myself, and I didn't want to guess at privilege escalation.

  Current state: pages table exists (empty). page_translations is untouched (still old flat shape, unaffected by my model changes yet). Nothing is broken — this is a clean, safe 
  stopping point.

  To unblock, someone with Postgres superuser access needs to run once:
  ALTER TABLE page_translations OWNER TO app;
  After that, re-run:
  php artisan migrate --path=database/migrations/2026_07_03_120001_restructure_page_translations_table.php --force
  php artisan db:seed --class=PageTranslationSeeder

  Want me to wait for you to fix the ownership and then continue (seed + browser verification), or would you rather run those two commands yourself once unblocked?

✻ Cooked for 16m 9s

❯ /model
  ⎿  Set model to Fable 5 and saved as your default for new sessions

❯ ose two commands yourself once unblocked?

  ✻ Cooked for 16m 9s

    7 tasks (6 done, 1 in progress, 0 open)
    ◼ Run migrate, seed, and verify end-to-end
    ✔ Create pages + restructure page_translations migrations
    ✔ Add Page model, update PageTranslation model
    ✔ Update PageController::show() for new schema
    ✔ Build PageResource Filament CRUD with vi/en tabs
     … +2 completed

● Still paused at the same spot — waiting on your call about the page_translations ownership fix. To recap the two options:

  1. You run it: as a Postgres superuser, execute ALTER TABLE page_translations OWNER TO app;, then tell me to continue and I'll run the remaining migration + seeder +
  verification.
  2. Or if you have superuser credentials you're comfortable sharing into the environment (e.g. a psql command I can run), I can do the whole thing.

  Just let me know how you'd like to proceed.

✻ Crunched for 14s

  7 tasks (6 done, 1 in progress, 0 open)
  ◼ Run migrate, seed, and verify end-to-end
  ✔ Create pages + restructure page_translations migrations
  ✔ Add Page model, update PageTranslation model
  ✔ Update PageController::show() for new schema
  ✔ Build PageResource Filament CRUD with vi/en tabs
   … +2 completed
