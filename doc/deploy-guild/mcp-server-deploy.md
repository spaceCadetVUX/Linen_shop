# MCP Server — Deploy lên VPS
**Last Updated:** 2026-07-13

> Host `mcp-server/` (bridge Claude tool-call → `/api/v1/mcp/*` của chính app này)
> trên VPS thay vì chạy local. Xem `doc/apimcp/claudemcp.md` cho spec đầy đủ của
> 31 tool. Không giống `doc/deploy-guild/cacylinen-vps-deploy.md` (app chính) —
> đây chỉ là 1 service phụ thêm vào cùng `docker-compose.yml`.

---

## Kiến trúc

```
Claude Desktop/Code  ──(mcp-remote, Streamable HTTP + X-Api-Key)──▶  mcp.cacylinen.com
                                                                            │
                                                                    nginx hệ thống (VPS)
                                                                            │ proxy_pass 127.0.0.1:3101
                                                                            ▼
                                                                  container mcp-server (Node)
                                                                            │ HTTP fetch, Bearer Sanctum token
                                                                            ▼
                                                        container nginx (cacylinen, docker network nội bộ)
                                                                            │ fastcgi
                                                                            ▼
                                                                  container php-fpm  →  /api/v1/mcp/*
```

`mcp-server` gọi API qua **docker network nội bộ** (`http://nginx/api/v1`), không đi qua internet — không cần domain/cert riêng cho chặng này.

---

## 1. Tạo Sanctum token cho MCP

Trên VPS, trong container `php-fpm`:

```bash
docker compose exec php-fpm php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'admin@example.com')->first(); // đổi thành user admin thật
$token = $user->createToken('mcp-server', ['mcp:read', 'mcp:write', 'mcp:publish']);
echo $token->plainTextToken; // copy giá trị này — chỉ hiện 1 lần
```

Lưu giá trị này vào `.env` (mục 2).

---

## 2. Thêm biến vào `.env` production

```bash
MCP_API_KEY=<openssl rand -base64 32 | tr -d '/+='>   # client (Claude) dùng key này gọi vào mcp-server
MCP_SANCTUM_TOKEN=<token vừa tạo ở bước 1>              # mcp-server dùng token này gọi vào Laravel API
```

Service `mcp-server` đã được khai báo sẵn trong `docker-compose.yml` (đọc 2 biến trên qua `env_file: .env`).

---

## 3. Build & up

```bash
cd /opt/cacylinen
git pull origin master
docker compose up -d --build mcp-server
docker compose ps mcp-server        # phải Up
docker compose logs --tail=30 mcp-server   # phải thấy "MCP Streamable HTTP listening on :3101"
```

Test nội bộ trên VPS (chưa qua nginx hệ thống):
```bash
curl -s -X POST http://127.0.0.1:3101/mcp \
  -H "X-Api-Key: $MCP_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"curl","version":"0"}}}'
```
Kỳ vọng: JSON-RPC response có `result.serverInfo.name = "knxstore-mcp"`, không phải lỗi 401/500.

---

## 4. DNS + nginx cho `mcp.cacylinen.com`

Đây là subdomain **mới hoàn toàn** (khác với giả định ban đầu là `mcp.knxstore.vn` — domain đó thuộc
zone Cloudflare khác, không liên quan tới catalog CacyLinen mà tool này thực sự quản lý, xem thảo luận
đã chốt lại dùng domain của chính cacylinen.com).

### 4.1 Thêm DNS record
Cloudflare dashboard → zone `cacylinen.com` → DNS → thêm record `A`, **Proxied** (☁️ cam):
```
mcp  →  103.166.183.176
```

### 4.2 Cert — KHÔNG cần tạo mới
Cert Cloudflare Origin hiện có cho `cacylinen.com` đã được tạo với hostname `cacylinen.com` **và**
`*.cacylinen.com` (xem mục 2 của `cacylinen-vps-deploy.md`) → dùng lại đúng file cert/key đang có sẵn
tại `/etc/ssl/cloudflare/cacylinen.com.pem` + `.key`, không phải tạo gì thêm.

### 4.3 Tạo file nginx mới
`/etc/nginx/sites-available/mcp.cacylinen.com.conf` (file mới, chưa tồn tại):

```nginx
server {
    listen 80;
    server_name mcp.cacylinen.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name mcp.cacylinen.com;

    ssl_certificate     /etc/ssl/cloudflare/cacylinen.com.pem;
    ssl_certificate_key /etc/ssl/cloudflare/cacylinen.com.key;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    access_log /var/log/nginx/mcp-cacylinen-access.log;
    error_log  /var/log/nginx/mcp-cacylinen-error.log warn;

    location / {
        proxy_pass http://127.0.0.1:3101;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        # Streamable HTTP giữ connection mở lâu hơn request thường — timeout dài hơn mặc định
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
        proxy_buffering off;   # quan trọng: MCP session dùng chunked/streaming, buffer sẽ làm hỏng response
    }
}
```

```bash
ln -s /etc/nginx/sites-available/mcp.cacylinen.com.conf /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

---

## 5. Test từ ngoài

```bash
curl -sI https://mcp.cacylinen.com/mcp -X POST -H "X-Api-Key: <sai key>"   # kỳ vọng 401
```

---

## 6. Cấu hình Claude Desktop

UI "Add custom connector" gốc **không hỗ trợ static API key** (chỉ URL + OAuth) — dùng bridge `mcp-remote`
qua `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "knxstore": {
      "command": "npx",
      "args": [
        "mcp-remote",
        "https://mcp.cacylinen.com/mcp",
        "--transport", "http-only",
        "--header", "X-Api-Key:${MCP_API_KEY}"
      ],
      "env": {
        "MCP_API_KEY": "<giá trị MCP_API_KEY ở bước 2>"
      }
    }
  }
}
```

Restart Claude Desktop sau khi sửa file.

---

## Bảo mật — cân nhắc thêm

31 tool có quyền `mcp:write`/`mcp:publish` — ghi/publish thẳng vào catalog production. Chỉ dựa vào
`MCP_API_KEY` tĩnh là hơi mỏng cho 1 endpoint public. Nên cân nhắc thêm **Cloudflare Access** (Zero Trust)
chặn theo email trước `mcp.cacylinen.com`, đặc biệt nếu domain này lộ ra ngoài phạm vi đội ngũ 5 người.

---

## Việc còn thiếu / rủi ro đã biết

- [x] Container `mcp-server` chạy healthy trên VPS, đã verify JSON-RPC `initialize` trả đúng `serverInfo.name: "knxstore-mcp"` (2026-07-13)
- [ ] Chưa có command/seeder tự động tạo Sanctum token — hiện phải chạy tay qua tinker (mục 1)
- [ ] Chưa thêm DNS record + nginx cho `mcp.cacylinen.com` (mục 4) — cert dùng lại wildcard `*.cacylinen.com` đã có sẵn, không cần tạo mới
- [ ] Chưa test thật `mcp-remote` với Claude Desktop trên máy Windows của team — chỉ verify qua doc chính thức, chưa chạy tay end-to-end
- [ ] Chưa bật Cloudflare Access — endpoint hiện chỉ có 1 lớp bảo vệ (API key)
- [ ] Package/env var naming (`knxstore-mcp-server`, `KNXSTORE_API_BASE/TOKEN`) không khớp catalog thật (CacyLinen) — chỉ là tên kế thừa lúc scaffold, không đổi vì không ảnh hưởng chức năng, nhưng dễ gây nhầm lẫn sau này
