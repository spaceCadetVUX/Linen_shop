# Phân quyền Admin Panel

Cập nhật: 2026-07-24

## Có mấy quyền?

3 role, không phải 2: `admin`, `manager`, `customer`. Định nghĩa ở `app/Enums/UserRole.php`.

| Role | Vào được `/admin`? | Quyền |
|---|---|---|
| `admin` | Có | Full quyền, không giới hạn gì |
| `manager` | Có | Bị chặn vài chỗ (xem bên dưới) |
| `customer` | Không | `canAccessPanel()` trong `app/Models/User.php` chặn thẳng |

Doc gốc (`doc/databse.md`, `doc/Backend Build Plan.md`) chỉ ghi 2 role (admin/customer) — chưa cập nhật.
Role `manager` mới thêm sau, ngày 2026-07-13 (xem `doc/todo.md`).

## Toàn bộ tính năng, chia theo quyền

Không dùng Policy, cũng không dùng permission gì cả — chỉ check thẳng `role === UserRole::Admin` ở vài chỗ, còn lại mặc định mở cho cả Admin lẫn Manager.

| Nhóm | Tính năng | Admin | Manager |
|---|---|---|---|
| — | Dashboard | ✅ | ✅ (không thấy nút "Xem API Docs") |
| Catalog | Products | ✅ | ✅ |
| Catalog | Categories | ✅ | ✅ |
| Catalog | Brands | ✅ | ✅ |
| Catalog | Manufacturers | ✅ | ✅ |
| Catalog | Filter Groups | ✅ | ✅ |
| Catalog | Reviews | ✅ | ✅ |
| Catalog | Order Inquiries | ✅ | ✅ |
| Commerce | Orders | ✅ | ✅ |
| Marketing | Promotions | ✅ | ✅ |
| Blog | Blog Posts | ✅ | ✅ |
| Blog | Blog Categories | ✅ | ✅ |
| Blog | Blog Tags | ✅ | ✅ |
| Blog | Authors | ✅ | ✅ |
| Blog | Blog Comments | ✅ | ✅ |
| Content | Media Library | ✅ | ✅ |
| Content | Pages | ✅ | ✅ |
| Content | Size Guides | ✅ | ✅ |
| SEO & GEO | Sitemaps | ✅ | ✅ |
| SEO & GEO | Redirects | ✅ | ✅ |
| Setting | Business Profile | ✅ | ✅ |
| Setting | Pages Setting | ✅ | ✅ |
| Setting | Mega Menu | ✅ | ✅ |
| Setting | Analytics & Search Console | ✅ | ❌ |
| System | Users | ✅ toàn bộ | ⚠️ chỉ xem/sửa/xóa được tài khoản `customer`, không tự gán role admin/manager cho ai |
| System | API Tokens (MCP) | ✅ | ❌ |
| System | Activity Log | ✅ | ❌ |
| System | Developer | ✅ | ❌ |

`customer` không có dòng nào ở trên vì không vào được `/admin` luôn.

## Lưu ý quan trọng: có 2 chỗ lưu "role" nhưng chỉ 1 chỗ được dùng

Project có cài `spatie/laravel-permission`, có bảng `permissions`, `roles`, `model_has_roles`... nhưng
**không dùng permission** — bảng permission rỗng, không ai gọi `givePermissionTo()` hay `hasPermissionTo()`.

Toàn bộ phân quyền thực tế chỉ đọc **cột `role` trên bảng `users`** (enum admin/manager/customer). Bảng
`model_has_roles` của Spatie có dữ liệu song song nhưng không ảnh hưởng gì — đừng tin nó.

## Sự cố cũ cần nhớ

Lúc thêm role `manager`, migration đổi enum Postgres viết kiểu thường (`enum()->change()`) sinh SQL sai
cú pháp Postgres → **crash-loop php-fpm production** một lúc. Phải fix bằng raw SQL (drop/add lại CHECK
constraint). Nếu sau này thêm role mới, nhớ làm y như migration
`2026_07_13_141150_add_manager_role_to_users_table.php` — đừng dùng cú pháp `enum()->change()` thẳng.

## Nếu cần thêm role hoặc quyền chi tiết hơn

- Thêm role: thêm case vào `UserRole` enum + gán level (đang là admin=20, manager=10, customer=0) + sửa
  CHECK constraint bằng raw SQL như trên.
- Cần phân quyền chi tiết hơn theo role (kiểu "sửa được Product nhưng không xóa được") — chưa có, phải
  làm mới bằng permission thật của Spatie (`Permission::create()` + Policy), vì hạ tầng đang có nhưng
  chưa dùng.
