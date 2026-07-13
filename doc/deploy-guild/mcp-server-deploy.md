# MCP Server — Deploy lên VPS
**Last Updated:** 2026-07-13

> Host `mcp-server/` (bridge Claude tool-call → `/api/v1/mcp/*` của chính app này)
> trên VPS thay vì chạy local. Xem `doc/apimcp/claudemcp.md` cho spec đầy đủ của
> 31 tool. Không giống `doc/deploy-guild/cacylinen-vps-deploy.md` (app chính) —
> đây chỉ là 1 service phụ thêm vào cùng `docker-compose.yml`.

---

## Kiến trúc

```
Claude Desktop/Code  ──(mcp-remote, Streamable HTTP + X-Api-Key)──▶  mcp.knxstore.vn
                                                                            │
                                                                    nginx hệ thống (VPS)
                                                                            │ proxy_pass 127.0.0.1:3100
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
docker compose logs --tail=30 mcp-server   # phải thấy "MCP Streamable HTTP listening on :3100"
```

Test nội bộ trên VPS (chưa qua nginx hệ thống):
```bash
curl -s -X POST http://127.0.0.1:3100/mcp \
  -H "X-Api-Key: $MCP_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"curl","version":"0"}}}'
```
Kỳ vọng: JSON-RPC response có `result.serverInfo.name = "knxstore-mcp"`, không phải lỗi 401/500.

---

## 4. Sửa nginx hệ thống cho `mcp.knxstore.vn`

Domain này **đã tồn tại** trên VPS nhưng hiện chỉ redirect placeholder về `knxstore.vn` — sửa lại file
`/etc/nginx/sites-available/mcp.knxstore.vn.conf` (tên file thật tùy theo cách đặt hiện tại, kiểm tra bằng
`ls /etc/nginx/sites-available/ | grep mcp` trước khi sửa) thành:

```nginx
server {
    listen 80;
    server_name mcp.knxstore.vn;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name mcp.knxstore.vn;

    ssl_certificate     /etc/ssl/cloudflare/knxstore.vn.pem;   # đổi đúng path cert hiện có cho knxstore.vn
    ssl_certificate_key /etc/ssl/cloudflare/knxstore.vn.key;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    access_log /var/log/nginx/mcp-knxstore-access.log;
    error_log  /var/log/nginx/mcp-knxstore-error.log warn;

    location / {
        proxy_pass http://127.0.0.1:3100;
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
nginx -t
systemctl reload nginx
```

⚠️ Nếu `knxstore.vn` chưa có Cloudflare Origin Cert lưu trên VPS này (mới chỉ có cert cho `cacylinen.com` —
xem mục 2 của `cacylinen-vps-deploy.md`), phải tạo cert mới cho `knxstore.vn` / `*.knxstore.vn` trước
(Cloudflare dashboard → SSL/TLS → Origin Server → Create Certificate), theo đúng quy trình đã làm cho cacylinen.com.

---

## 5. Test từ ngoài

```bash
curl -sI https://mcp.knxstore.vn/mcp -X POST -H "X-Api-Key: <sai key>"   # kỳ vọng 401
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
        "https://mcp.knxstore.vn/mcp",
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
chặn theo email trước `mcp.knxstore.vn`, đặc biệt nếu domain này lộ ra ngoài phạm vi đội ngũ 5 người.

---

## Việc còn thiếu / rủi ro đã biết

- [ ] Chưa có command/seeder tự động tạo Sanctum token — hiện phải chạy tay qua tinker (mục 1)
- [ ] Chưa xác nhận `knxstore.vn` đã có Cloudflare Origin Cert trên VPS này chưa (mục 4)
- [ ] Chưa test thật `mcp-remote` với Claude Desktop trên máy Windows của team — chỉ verify qua doc chính thức, chưa chạy tay end-to-end
- [ ] Chưa bật Cloudflare Access — endpoint hiện chỉ có 1 lớp bảo vệ (API key)
