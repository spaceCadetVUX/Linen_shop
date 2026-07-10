# Deploy Linen_shop lên VPS iNET (cacylinen.com) — Docker

**Last Updated:** 2026-07-10

> Ghi lại quy trình đã thực hiện thật để deploy domain `cacylinen.com` lên VPS
> iNET dùng chung với Flowise + n8n. Dùng file này để redeploy hoặc dựng lại
> từ đầu nếu VPS mất. Không giống `doc/deploy-guild/horizon.md` (bare-metal +
> Supervisor) — đây là deploy bằng Docker Compose.

---

## 1. Hạ tầng VPS

| Thứ | Giá trị |
|---|---|
| Provider | iNET (`portal.inet.vn`) |
| IP | `103.166.183.176` |
| SSH | `ssh root@103.166.183.176 -p 24700` (**không phải port 22**) |
| OS | Ubuntu 24.04 LTS |
| RAM | 3.8Gi tổng — dùng chung với Flowise + n8n + vài MCP service khác |
| Reverse proxy | **Nginx hệ thống** (cài qua `apt`, không phải panel nào) — chiếm cổng 80/443, route theo domain qua file trong `/etc/nginx/sites-available/` |
| SSL | Cloudflare Origin CA certs, lưu tại `/etc/ssl/cloudflare/<domain>.pem` + `.key` |

**Domain khác đang chạy trên VPS này** (tham khảo pattern): `flowise.knxstore.vn`, `n8n.knxstore.vn`, `mcp.knxstore.vn`, `sig.knxstore.vn` — tất cả đều theo pattern "1 domain = 1 file nginx config, proxy vào `127.0.0.1:<port nội bộ>`".

⚠️ **Vì Nginx hệ thống đã chiếm cổng 80/443, KHÔNG bao giờ cài thêm panel nào tự quản lý Nginx riêng (aaPanel, Nginx Proxy Manager...) trên VPS này** — sẽ đụng cổng và có thể sập domain khác đang chạy.

---

## 2. Domain + Cloudflare

Domain `cacylinen.com` quản lý trong Cloudflare account `TungVu Space`.

1. **DNS** → 2 record `A`, cả 2 **Proxied** (☁️ cam):
   - `@` → `103.166.183.176`
   - `www` → `103.166.183.176`
2. **SSL/TLS → Origin Server → Create Certificate**:
   - Hostnames: `cacylinen.com`, `*.cacylinen.com`
   - Key type: RSA 2048, Validity: 15 years
   - Lưu 2 khối (Certificate + Private Key) — **chỉ hiện 1 lần**, mất là phải tạo lại.

Trên VPS:
```bash
mkdir -p /etc/ssl/cloudflare
# dán Certificate vào:
nano /etc/ssl/cloudflare/cacylinen.com.pem
# dán Private Key vào:
nano /etc/ssl/cloudflare/cacylinen.com.key

chmod 644 /etc/ssl/cloudflare/cacylinen.com.pem
chmod 600 /etc/ssl/cloudflare/cacylinen.com.key
```

Verify cert/key khớp nhau:
```bash
openssl x509 -noout -modulus -in /etc/ssl/cloudflare/cacylinen.com.pem | openssl md5
openssl rsa  -noout -modulus -in /etc/ssl/cloudflare/cacylinen.com.key | openssl md5
# 2 hash phải giống nhau
```

### Nginx site config (host-level)

`/etc/nginx/sites-available/cacylinen.com.conf`:
```nginx
server {
    listen 80;
    server_name cacylinen.com www.cacylinen.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name cacylinen.com www.cacylinen.com;

    ssl_certificate     /etc/ssl/cloudflare/cacylinen.com.pem;
    ssl_certificate_key /etc/ssl/cloudflare/cacylinen.com.key;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    access_log /var/log/nginx/cacylinen-access.log;
    error_log  /var/log/nginx/cacylinen-error.log warn;

    location / {
        proxy_pass http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/cacylinen.com.conf /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

Port nội bộ `8081` là cố định — khớp với `docker-compose.yml` (service `nginx` bind `127.0.0.1:8081:80`). Nếu đổi port trong compose, phải đổi cả ở đây.

---

## 3. Thay đổi trong `docker-compose.yml` so với bản local dev

| Service | Local dev | Production (VPS) | Lý do |
|---|---|---|---|
| `nginx` | `"80:80"` | `"127.0.0.1:8081:80"` | Cổng 80/443 thật đã bị Nginx hệ thống chiếm |
| `postgres` | `"5432:5432"` | *(không public)* | Không cần lộ DB ra ngoài |
| `redis` | `"6379:6379"` | *(không public)* | Tương tự |
| `meilisearch` | `"7700:7700"` | *(không public)* | Tương tự |
| `postgres` env | `POSTGRES_PASSWORD: secret` (hardcode) | Đọc từ `${DB_PASSWORD:?...}` trong `.env` | Không hardcode password vào file git-tracked |

---

## 4. `.env` production — checklist giá trị phải đổi

**Không copy `.env.example` thẳng ra** — các giá trị sau **phải** khác local dev:

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cacylinen.com
APP_KEY=base64:<generate riêng, xem lệnh dưới>

DB_PASSWORD=<generate riêng — PHẢI khớp với lúc Postgres init lần đầu>

LOG_LEVEL=error

SESSION_DOMAIN=.cacylinen.com
SANCTUM_STATEFUL_DOMAINS=cacylinen.com,www.cacylinen.com
FRONTEND_URL=https://cacylinen.com
GOOGLE_REDIRECT_URI=https://cacylinen.com/api/v1/auth/google/callback
```

Generate `APP_KEY`:
```bash
echo "base64:$(openssl rand -base64 32)"
```

Generate `DB_PASSWORD`:
```bash
openssl rand -base64 24 | tr -d '/+='
```

⚠️ **Postgres chỉ áp dụng `POSTGRES_PASSWORD` lúc `initdb` (volume rỗng lần đầu).** Đổi `DB_PASSWORD` trong `.env` SAU KHI volume `postgres_data` đã tồn tại **không có tác dụng** — phải xoá volume (`docker volume rm <project>_postgres_data`) rồi `up -d` lại để Postgres init lại với password mới. Chỉ an toàn làm khi chưa có data thật.

Việc còn thiếu trong `.env` hiện tại (chưa chặn deploy, nhưng phải làm trước khi bán hàng thật):
- `MAIL_MAILER=log` → email không gửi thật cho khách, cần SMTP thật.
- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` → trống, đăng nhập Google chưa hoạt động.

---

## 5. Quy trình deploy lần đầu (đã chạy thật, theo đúng thứ tự)

```bash
# 1. Clone code
mkdir -p /opt/cacylinen
cd /opt/cacylinen
git clone https://github.com/spaceCadetVUX/Linen_shop.git .

# 2. Tạo .env production (xem mục 4)
nano .env
chmod 600 .env

# 3. Build & up
docker compose build
docker compose up -d
```

### Lỗi thường gặp lần đầu & cách fix (đã gặp thật)

**a) `entrypoint.sh: permission denied`**
Git lưu file thiếu bit `+x` khi tạo trên Windows. Đã fix trong repo (`git update-index --chmod=+x`), nhưng nếu clone bản cũ hơn:
```bash
chmod +x docker/scripts/entrypoint.sh
```

**b) `storage/logs/laravel.log ... Permission denied` / `bootstrap/cache directory must be present and writable` / lỗi tương tự khi publish asset vào `public/`**
Container chạy `USER www-data` (UID 33), nhưng code vừa `git clone` bằng `root` → toàn bộ thư mục root-owned. Fix 1 lần cho cả cây:
```bash
docker compose stop php-fpm horizon scheduler
chown -R 33:33 /opt/cacylinen
docker compose up -d php-fpm horizon scheduler
```

**c) `password authentication failed for user "app"` khi migrate**
Xem cảnh báo ở mục 4 — volume Postgres đã init với password cũ trước khi `.env` được set đúng. Fix (mất data, chỉ an toàn nếu chưa có gì thật):
```bash
docker compose stop postgres php-fpm horizon scheduler
docker compose rm -f postgres
docker volume rm cacylinen_postgres_data
docker compose up -d
```

**d) `git pull` báo "detected dubious ownership"**
Do bước (b) chown cả `.git/` sang UID 33. Fix:
```bash
git config --global --add safe.directory /opt/cacylinen
```

**e) Trang load được nhưng Livewire/Filament báo "Mixed Content ... blocked" (asset load `http://` trên trang `https://`)**
TLS terminate ở Nginx hệ thống, container Laravel chỉ thấy HTTP nội bộ. Cần 2 fix trong code (đã áp dụng, xem `bootstrap/app.php` và `app/Providers/AppServiceProvider.php`):
1. `$middleware->trustProxies(at: '*')` trong `bootstrap/app.php`.
2. `URL::forceScheme('https')` khi `app()->isProduction()` trong `AppServiceProvider::boot()` — bước 1 không đủ vì `url()` helper cache root URL rồi swap scheme không nhất quán trong 1 request; ép cứng scheme là cách chắc chắn nhất.

```bash
# 4. Seed data ban đầu (roles + admin user + SEO scaffolding — KHÔNG seed demo products/categories)
docker compose exec php-fpm php artisan db:seed
```

Admin mặc định sau seed: `admin@example.com` / `password` — **đổi ngay sau lần đăng nhập đầu**, password này là demo public trong `DOCKER.md`.

---

## 6. Redeploy khi có code mới

```bash
cd /opt/cacylinen
git pull origin master
docker compose up -d --build   # rebuild image nếu Dockerfile/composer.lock đổi
docker compose restart php-fpm horizon scheduler   # đủ nếu chỉ đổi code PHP
```

`entrypoint.sh` tự chạy lại `composer install` (nếu `composer.lock` đổi), `migrate --force`, `storage:link`, `filament:assets`, `config:cache` mỗi lần container `php-fpm` khởi động lại — không cần chạy tay các bước này.

---

## 7. Kiểm tra sau deploy

```bash
docker compose ps                          # tất cả phải Up/healthy
curl -I https://cacylinen.com              # phải trả 200/301, KHÔNG phải 502/504
curl -sI https://cacylinen.com | grep -i location   # nếu có redirect, location phải là https://
free -h && docker stats --no-stream        # theo dõi RAM — VPS dùng chung với Flowise/n8n, dễ chật
```

---

## 8. Việc còn thiếu (chưa chặn deploy nhưng cần làm trước khi vận hành thật)

- [ ] Đổi password admin mặc định
- [ ] Cấu hình SMTP thật (đang `MAIL_MAILER=log`, email không gửi thật)
- [ ] Điền `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` nếu dùng đăng nhập Google
- [ ] Setup cron backup Postgres định kỳ + đẩy backup ra ngoài VPS
- [ ] Theo dõi RAM khi traffic tăng — VPS 4GB dùng chung 3 stack (Linen_shop + Flowise + n8n)
- [ ] Test đầy đủ luồng checkout/cart/order trên domain thật (chưa test nghiệp vụ, chỉ xác nhận hạ tầng chạy)
