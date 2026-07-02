# Docker — Quick Reference

## Prerequisites

- Docker Desktop must be running before any command below.

---

## Daily Usage

**Start:**
```bash
docker compose up -d
```

**Stop:**
```bash
docker compose down
```

**Access:**
- App: http://localhost
- Admin: http://localhost/admin
- Meilisearch: http://localhost:7700

**Admin credentials:**
- Email: `admin@example.com`
- Password: `password`

---

## First Time Setup

Run once after cloning the repo or resetting the database:

```bash
docker compose up -d
docker compose exec php-fpm php artisan db:seed
```

---

## Useful Commands

```bash
# View logs
docker compose logs php-fpm
docker compose logs nginx

# Run artisan commands
docker compose exec php-fpm php artisan <command>

# Open a shell inside the container
docker compose exec php-fpm bash

# Clear caches
docker compose exec php-fpm php artisan optimize:clear

# Reset database (wipes all data)
docker compose down -v
docker compose up -d
docker compose exec php-fpm php artisan db:seed
```

---

## Gotchas

**Sửa code liên quan Scout/Meilisearch (`toSearchableArray()`, `makeAllSearchableUsing()`, `config/scout.php`) → PHẢI restart `horizon`:**

```bash
docker compose restart horizon
```

Lý do: `config/scout.php` bật `'queue' => [...]` (truthy) nên mọi lần đồng bộ search index (`scout:import`, `$model->searchable()`, save model) đều bị đẩy qua Redis queue `seo`, xử lý bởi container `horizon` — container này chạy liên tục (`php artisan horizon`), không tự nạp lại code PHP đã đổi (PHP không re-declare class đã load trong cùng process, khác với `php-fpm` vốn xử lý request-per-request nên luôn đọc code mới). Nếu quên restart, `scout:import` vẫn báo "imported" bình thường nhưng dữ liệu lên Meilisearch âm thầm dùng schema **cũ** — không có lỗi nào hiện ra, rất dễ bỏ sót.

Áp dụng tương tự cho bất kỳ code nào chạy qua queue: job, listener, mail — restart `horizon` sau khi sửa.

---

## Services

| Service      | Port  | Description          |
|--------------|-------|----------------------|
| nginx        | 80    | Web server           |
| php-fpm      | 9000  | Laravel app          |
| postgres     | 5432  | Database             |
| redis        | 6379  | Cache / Queue        |
| meilisearch  | 7700  | Search engine        |
| horizon      | —     | Queue worker         |
| scheduler    | —     | Laravel scheduler    |



docker compose up -d

Sau đó chờ khoảng 1-2 phút để php-fpm chạy xong entrypoint (composer install + artisan commands). Khi nào muốn biết đã ready chưa thì kiểm tra:

docker logs laravel13vux-php-fpm-1 2>&1 | Select-String "ready to handle"

Thấy ready to handle connections là vào được.