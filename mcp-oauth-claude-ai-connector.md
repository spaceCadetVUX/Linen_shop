# Thêm OAuth cho MCP server — dùng được qua claude.ai (web) + mobile

## Context

`mcp-server/` (repo `cacylinen`, dùng cho cửa hàng KNXStore.vn/cacylinen.com) hiện đã public tại `https://mcp.cacylinen.com/mcp` nhưng chỉ xác thực bằng **1 static API key** (`MCP_API_KEY`) dùng chung cho mọi client. Claude Desktop kết nối được nhờ gói `mcp-remote` nhét key này vào header. Các tool trên server có quyền **ghi/publish trực tiếp vào catalog production** (product, category, blog, brand, bulk SEO/translate).

claude.ai (web) và app mobile **không có ô nhập API key** cho custom connector — chỉ hỗ trợ OAuth (tự discovery + Dynamic Client Registration) hoặc không auth. Với server có quyền ghi production, "không auth" không chấp nhận được → bắt buộc phải có OAuth thật.

Đã quyết định với user:
- Allowlist đúng 3 email: `tung.vu@knxstore.vn`, `thnga.co@gmail.com` (vợ), `vvtung84@gmail.com` (marketing test) — 3 domain khác nhau, không dùng domain restriction.
- Dùng Google làm identity provider, OAuth Client **mới, riêng biệt** với client Socialite của storefront.

**Đổi hướng so với bản plan đầu tiên:** bản đầu tự viết OAuth server (subclass `ProxyOAuthServerProvider` của `@modelcontextprotocol/sdk`, migrate `mcp-server` sang Express, tự verify token qua Google tokeninfo, tự làm Dynamic Client Registration store bằng Redis...). Khi rà kỹ lại phát hiện 1 lỗi thiết kế thật (dùng nhầm `client_id` khi nói chuyện với Google → Google trả `invalid_client`, chết flow ngay bước đầu) — và về tổng thể, tự viết 1 authorization server OAuth 2.1 đúng chuẩn là việc rủi ro cao, nhiều chi tiết dễ sai (PKCE, redirect_uri, DCR...).

**Quyết định mới: dùng [`mcp-auth-proxy`](https://github.com/sigbit/mcp-auth-proxy)** (MIT, ~146★, tạo 8/2025, vẫn update đều tới 5/2026) — một reverse-proxy chuyên biệt, chạy trước `mcp-server` hiện có, tự lo toàn bộ OAuth 2.1 + DCR + đăng nhập Google + allowlist email. **`mcp-server` không đổi 1 dòng code nào** — vẫn giữ nguyên static `MCP_API_KEY` như hiện tại, chỉ không còn lộ trực tiếp ra internet nữa (proxy đứng giữa, tự nhét key đó khi forward request đã xác thực). Bảng "Verified MCP Client" của project này ghi rõ **Claude - Web ✅, Claude - Desktop ✅** — họ đã xử lý sẵn các quirk tương thích với claude.ai.

Đánh đổi cần biết: đây là project nhỏ (bên thứ ba), không phải code tự viết/tự kiểm soát — nhưng bù lại loại bỏ gần như toàn bộ rủi ro implement OAuth sai, và scope thay đổi trong repo giảm mạnh.

---

## 1. Deploy `mcp-auth-proxy` trước `mcp-server` (không sửa `mcp-server`)

Thêm service mới vào `docker-compose.yml`:

```yaml
mcp-auth-proxy:
  image: ghcr.io/sigbit/mcp-auth-proxy:latest
  ports:
    - "127.0.0.1:3102:3102"
  environment:
    EXTERNAL_URL: "https://mcp.cacylinen.com"
    LISTEN: ":3102"
    NO_AUTO_TLS: "1"                 # TLS đã terminate ở nginx VPS + Cloudflare, proxy chỉ chạy HTTP nội bộ
    GOOGLE_CLIENT_ID: ${MCP_AUTH_GOOGLE_CLIENT_ID:?required}
    GOOGLE_CLIENT_SECRET: ${MCP_AUTH_GOOGLE_CLIENT_SECRET:?required}
    GOOGLE_ALLOWED_USERS: ${MCP_AUTH_ALLOWED_EMAILS:?required}
    PROXY_BEARER_TOKEN: ${MCP_API_KEY:?required}   # forward key hiện có xuống mcp-server sau khi đã xác thực người dùng thật
    DATA_PATH: "/data"
  volumes:
    - mcp_auth_proxy_data:/data       # BoltDB local — lưu OAuth client (DCR)/session state, sống qua restart
  depends_on:
    - mcp-server
  restart: unless-stopped
```
Backend target: mcp-server chạy HTTP/SSE transport hiện có (`http://mcp-server:3101/mcp`) — theo docs, mcp-auth-proxy proxy nguyên path cho backend HTTP/SSE (không đổi path), nên client vẫn gọi đúng `/mcp` như cũ.

Thêm volume `mcp_auth_proxy_data` vào phần `volumes:` gốc của compose.

**`mcp-server` giữ nguyên hoàn toàn** — không sửa `index.ts`, không thêm Express/Redis/ioredis, `MCP_API_KEY` vẫn hoạt động y như hôm nay, chỉ đổi vai trò: từ "key user phải tự nhập" thành "secret nội bộ giữa 2 container, mcp-auth-proxy tự nhét vào, user không cần biết".

## 2. Env vars mới (chỉ 3 cái, không đụng gì của mcp-server)

Thêm vào root `.env.example`:
```
MCP_AUTH_GOOGLE_CLIENT_ID=
MCP_AUTH_GOOGLE_CLIENT_SECRET=
MCP_AUTH_ALLOWED_EMAILS=tung.vu@knxstore.vn,thnga.co@gmail.com,vvtung84@gmail.com
```
`MCP_API_KEY`/`MCP_SANCTUM_TOKEN` trong `mcp-server/.env.example` và root `.env.example`: **giữ nguyên, không xóa** (vẫn cần, chỉ đổi cách dùng như trên).

## 3. Laravel — `DeveloperPage.php` / `config/services.php`

`getMcpConfig()`: URL **không đổi** (`https://mcp.{host}/mcp` — proxy forward nguyên path). Bỏ field `api_key` khỏi output — key giờ là bí mật nội bộ, không hiển thị cho user nữa.

`getMcpConfigJson()`: bỏ `--header "X-API-Key: ..."`, chỉ còn `npx -y mcp-remote <url> --transport http-only` — `mcp-remote` tự phát hiện OAuth và mở trình duyệt đăng nhập Google.

Thêm khối "Add to claude.ai" trong `resources/views/filament/pages/developer.blade.php`: Settings → Connectors → Add custom connector → dán `https://mcp.{host}/mcp` → đăng nhập Google (tài khoản trong allowlist).

`config/services.php`: xóa block `'mcp' => ['api_key' => ...]` — không còn nơi nào trong Laravel cần đọc giá trị này nữa.

## 4. `SystemHealthWidget.php` — không cần sửa

Widget hiện tại gọi thẳng `http://mcp-server:3101/mcp` qua **docker network nội bộ** (không qua `mcp-auth-proxy`, không qua internet) với `X-Api-Key` — cơ chế này không đổi vì `mcp-server` không đổi. Đây là điểm cộng của phương án này so với bản plan đầu tiên: **không cần thêm route `/health` hay viết lại widget.**

(Tùy chọn, không bắt buộc: có thể thêm 1 check tồn tại/sống của container `mcp-auth-proxy` — nhưng vì đây chỉ là lớp proxy phía trước, không phải nguồn dữ liệu, không bắt buộc phải hiện trên System Health.)

## 5. Việc Tùng cần tự làm ngoài code

1. **Google Cloud Console**: tạo OAuth Client mới (Web application), **redirect URI = `https://mcp.cacylinen.com/.auth/google/callback`** (đây là callback của chính `mcp-auth-proxy`, khác với redirect_uri của claude.ai — proxy tự lo phần OAuth với claude.ai riêng, không liên quan tới URI này).
2. **Sửa nginx hệ thống trên VPS** (file `/etc/nginx/sites-available/mcp.cacylinen.com.conf`, ngoài repo): đổi `proxy_pass` từ `127.0.0.1:3101` (mcp-server) sang `127.0.0.1:3102` (mcp-auth-proxy); đảm bảo forward đúng `X-Forwarded-Proto: https` và `Host` — mcp-auth-proxy cần các header này để tự build đúng URL tuyệt đối (vì nó không tự terminate TLS, `NO_AUTO_TLS=1`).
3. Thêm 3 env var mới vào `.env` trên VPS, `docker compose up -d mcp-auth-proxy`, rồi `nginx -s reload` (hoặc tương đương) sau khi sửa xong nginx.
4. (Khuyến nghị, không bắt buộc) Bật 2FA cho `thnga.co@gmail.com` và `vvtung84@gmail.com` — 2 Gmail cá nhân này giờ là root-of-trust cho quyền ghi production qua Claude.

## 6. Rollout & kiểm thử

1. Google OAuth cần redirect URI là domain thật (HTTPS) — khó test hoàn toàn local. Nếu muốn test an toàn trước khi cắt hẳn: dùng 1 subdomain phụ tạm (ví dụ `mcp-test.cacylinen.com` trỏ tạm vào 1 container `mcp-auth-proxy` test) hoặc chấp nhận downtime ngắn khi cutover thật (traffic thấp, chỉ 3 người dùng).
2. Sau khi trỏ domain thật vào `mcp-auth-proxy`: test Claude Desktop trước (qua `getMcpConfigJson()` mới, không còn static header) — xác nhận popup đăng nhập Google, list/gọi tool được.
3. Thêm `https://mcp.cacylinen.com/mcp` làm Custom Connector trên claude.ai thật, đăng nhập Google, thử 1 tool đọc + 1 tool ghi. Vì proxy đã verified với "Claude - Web", kỳ vọng ít phải debug hơn bản tự-viết-OAuth, nhưng vẫn nên thử thật trước khi coi là xong.
4. Mở app Claude mobile cùng tài khoản claude.ai — connector phải xuất hiện sẵn.
5. Test với tài khoản Google **không** nằm trong `MCP_AUTH_ALLOWED_EMAILS` — phải bị từ chối.

### File chính sẽ tạo/sửa
- `docker-compose.yml` (thêm service `mcp-auth-proxy` + volume — sửa)
- root `.env.example` (thêm 3 var mới — sửa)
- `app/Filament/Pages/DeveloperPage.php`, `resources/views/filament/pages/developer.blade.php` (sửa)
- `config/services.php` (sửa — xóa block `mcp.api_key`)
- **`mcp-server/` — không đổi gì**
- **`app/Filament/Widgets/SystemHealthWidget.php` — không đổi gì**
- Ngoài repo: nginx hệ thống trên VPS (Tùng tự sửa, xem mục 5)
