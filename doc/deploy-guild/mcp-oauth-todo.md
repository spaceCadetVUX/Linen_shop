# TODO — Hoàn tất OAuth cho MCP server (mcp-auth-proxy)

> Đi kèm với `mcp-oauth-claude-ai-connector.md` (kế hoạch) và
> `cacylinen-vps-deploy.md` (deploy chung). Code đã commit (`18df0bb`) và
> deploy lên VPS — `mcp-auth-proxy` đã có trong `docker-compose.yml` nhưng
> **chưa bật** (thiếu OAuth Client thật). File này track 2 việc còn lại.

---

## Phần 1 — Việc A Tùng cần cung cấp

### 1. Google Cloud Console
- [ ] Tạo OAuth Client mới (Web application) — **riêng biệt**, không dùng chung Client Socialite của storefront
- [ ] Redirect URI: `https://mcp.cacylinen.com/.auth/google/callback`
- [ ] Gửi lại: Client ID + Client Secret

### 2. SSH access lên VPS (`103.166.183.176:24700`)
- [ ] Quyết định: dùng chung root như hiện tại, hay tạo user riêng (group `docker`) cho Vũ deploy — an toàn hơn vì VPS dùng chung Flowise/n8n
- [ ] Nếu tạo user riêng: thêm SSH public key của Vũ vào `~/.ssh/authorized_keys` của user đó
- [ ] Nếu dùng chung root: xác nhận cách chia sẻ key/password an toàn (không qua chat thường)

### 3. (Khuyến nghị, không bắt buộc)
- [ ] Bật 2FA cho `thnga.co@gmail.com` và `vvtung84@gmail.com` — 2 Gmail cá nhân giờ là root-of-trust cho quyền ghi production qua Claude

---

## Phần 2 — Việc cần làm sau khi A Tùng cung cấp đủ

### A. Sau khi có Google Client ID/Secret
- [ ] SSH vào VPS, sửa `/opt/cacylinen/.env`:
  - Thay `MCP_AUTH_GOOGLE_CLIENT_ID=pending` → giá trị thật
  - Thay `MCP_AUTH_GOOGLE_CLIENT_SECRET=pending` → giá trị thật
  - Kiểm tra lại `MCP_AUTH_ALLOWED_EMAILS` đúng 3 email, không gõ sai

### B. Nginx hệ thống trên VPS
- [ ] Kiểm tra `/etc/nginx/sites-available/mcp.cacylinen.com.conf` đã tồn tại chưa (nếu chưa, tạo mới theo pattern của `cacylinen.com.conf` trong `cacylinen-vps-deploy.md` mục 2)
- [ ] Đổi `proxy_pass` từ `127.0.0.1:3101` → `127.0.0.1:3102`
- [ ] Đảm bảo có `proxy_set_header X-Forwarded-Proto https;` và `proxy_set_header Host $host;` (mcp-auth-proxy cần 2 header này vì `NO_AUTO_TLS=1`)
- [ ] `nginx -t` rồi `systemctl reload nginx`

### C. Bật container
- [ ] `cd /opt/cacylinen && docker compose up -d mcp-auth-proxy`
- [ ] `docker compose ps` — xác nhận `mcp-auth-proxy` Up

### D. Test rollout (theo đúng thứ tự — traffic thấp, chỉ 3 người dùng)
- [ ] Claude Desktop trước — mở `getMcpConfigJson()` mới, xác nhận popup đăng nhập Google hiện ra, list/gọi tool được
- [ ] claude.ai (web) — Settings → Connectors → Add custom connector → `https://mcp.cacylinen.com/mcp` → login Google → thử 1 tool đọc + 1 tool ghi
- [ ] Claude mobile — cùng tài khoản claude.ai, xác nhận connector xuất hiện sẵn không cần cấu hình thêm
- [ ] Test với 1 tài khoản Google **không** nằm trong allowlist → phải bị từ chối ở bước login

### E. Dọn dẹp sau khi test pass
- [ ] Update mục 8 (`Việc còn thiếu`) trong `cacylinen-vps-deploy.md` — đánh dấu `GOOGLE_CLIENT_ID`/`SECRET` cho MCP đã xong (khác với Socialite, ghi rõ)
- [ ] Xoá file `mcp-oauth-todo.md` này hoặc đánh dấu DONE toàn bộ khi xong
