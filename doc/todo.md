# TODO

> Cập nhật: 2026-07-04 — session "search theo thuộc tính trong Meilisearch"

## 🔴 Phải chạy khi bật Docker (search thuộc tính chưa hoạt động nếu thiếu)

Code đã sửa xong (8 file, chưa commit) nhưng Docker Desktop đang tắt nên chưa apply được runtime. Chạy đúng thứ tự:

```bash
docker compose restart horizon
# bắt buộc — Scout sync chạy qua queue `seo`, worker là process dài hạn,
# không tự reload class PHP. Bỏ qua = fail âm thầm, index nhận data schema cũ.

docker compose exec app php artisan meilisearch:configure
# đẩy searchableAttributes mới (filter_value_names_vi/en) lên Meilisearch

docker compose exec app php artisan scout:import "App\Models\Product"
# re-index toàn bộ để document có field filter_value_names_*
```

**Test nhanh sau khi xong:** search PLP bằng tên thuộc tính thuần (vd "trắng", "100% linen") — phải ra sản phẩm gắn value đó kể cả khi từ khoá không có trong tên/mô tả. Match vào tên sản phẩm vẫn rank cao hơn match vào thuộc tính (thứ tự searchableAttributes là ranking rule).

## Nội dung đã sửa trong session này (chờ commit + verify runtime)

| File | Thay đổi |
|---|---|
| `app/Models/Product.php` | `toSearchableArray()`: thêm `filter_value_names_vi/en`; **fix bug** thay guard `relationLoaded()` bằng `loadMissing()` — trước đây mỗi lần admin save, Scout job index đè `category_ids = []`, `filter_value_ids = []` (relations không được serialize vào queue job). Bỏ `filter_value_slugs` (dead data). |
| `config/scout.php` | `searchableAttributes` app_products += `filter_value_names_vi/en` (đặt cuối — ranking) |
| `app/Services/Catalog/ProductSearchService.php` | `attributesToSearchOn` += `filter_value_names_{locale}` |
| `app/Repositories/Eloquent/ProductRepository.php` | SQL fallback: keyword match thêm tên thuộc tính (`orWhereHas`, en dùng `COALESCE(name_en, name)`) |
| `app/Console/Commands/MeilisearchConfigureCommand.php` | Bỏ settings cũ hardcode (lệch config, chạy là phá filter `filter_value_ids`/`effective_price`), giờ đọc `config('scout.meilisearch.index-settings')` |
| `.../ProductResource/Pages/Concerns/ManagesProductRelations.php` + `CreateProduct.php` + `EditProduct.php` | Thêm `syncSearchIndex()` sau khi save translations + filter values — pivot `sync()` và translation `updateOrCreate()` không trigger Scout, attach/detach thuộc tính trước đây không re-index |

## 🟡 Nợ kỹ thuật phát hiện trong session (chưa fix)

1. **Test suite chết trên sqlite** — `php artisan test` fail toàn bộ (0 assertion) từ trước thay đổi này: migration `blog_posts drop column slug` không tương thích sqlite in-memory (`error in index blog_posts_slug_unique after drop column`). CI/pre-commit checklist "php artisan test passes" hiện vô nghĩa cho tới khi fix.
2. **Pint fail có sẵn** ở `ProductResource/Pages/*` (aligned assignment style vs config pint) — HEAD cũng fail y hệt, không phải do session này.
3. **phpstan/larastan chưa cài** dù CLAUDE.md ghi là có (`vendor/bin/phpstan` không tồn tại).
4. **`SCOUT_DRIVER` không được set trong `phpunit.xml`** — test env có thể leak driver meilisearch từ `.env` (nên set `SCOUT_DRIVER=collection` hoặc `null` cho testing).
