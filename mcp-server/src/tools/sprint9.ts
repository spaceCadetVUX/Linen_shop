import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { api, ok } from "../client.js";

export function registerSprint9Tools(server: McpServer) {

  server.tool(
    "list_pages",
    "Liệt kê toàn bộ trang tĩnh (chính sách, điều khoản...) — page_key, trạng thái active, translations.",
    {},
    async () => ok(await api("GET", "/mcp/pages")),
  );

  server.tool(
    "get_page_context",
    "Load full context 1 trang tĩnh trước khi sửa.",
    {
      page_key: z.string().describe("Page key, e.g. 'privacy-policy', 'terms'"),
    },
    async ({ page_key }) => ok(await api("GET", `/mcp/pages/${page_key}/context`)),
  );

  server.tool(
    "check_page_readiness",
    "Kiểm tra trang đủ điều kiện activate chưa (title/body/meta_title/meta_description — vi bắt buộc, en chỉ cảnh báo). Gọi trước activate_page.",
    {
      page_key: z.string().describe("Page key"),
    },
    async ({ page_key }) => ok(await api("GET", `/mcp/pages/${page_key}/readiness`)),
  );

  server.tool(
    "save_page",
    "Upsert trang tĩnh (chính sách/điều khoản) — tạo mới hoặc update content theo locale. Slug KHÔNG sửa được qua tool này — luôn tự sinh từ title.",
    {
      page_key: z.string().describe("Page key — định danh, dùng làm URL, e.g. 'shipping-policy'"),
      overwrite_existing: z.boolean().default(false).describe("true = ghi đè content đã có"),
      dry_run: z.boolean().default(false).describe("true = preview, không lưu DB"),
      translations: z.record(z.object({
        title:             z.string().optional().describe("Tiêu đề trang theo locale — slug tự sinh từ đây, không set trực tiếp được"),
        body:              z.string().optional().describe("Nội dung đầy đủ (HTML ok)"),
        meta_title:        z.string().optional().describe("SEO title (≤60 ký tự)"),
        meta_description:  z.string().optional().describe("SEO description (≤160 ký tự)"),
      })).optional().describe('{"vi": {title, body, ...}, "en": {title, body, ...}}'),
    },
    async ({ page_key, ...body }) => ok(await api("PUT", `/mcp/pages/${page_key}`, body)),
  );

  server.tool(
    "activate_page",
    "Activate trang sau khi readiness pass — trang bắt đầu hiện public (cả 2 locale cùng lúc, is_active nằm ở cấp page, không phải per-locale).",
    {
      page_key: z.string().describe("Page key"),
    },
    async ({ page_key }) => ok(await api("PATCH", `/mcp/pages/${page_key}/activate`, {})),
  );

  server.tool(
    "deactivate_page",
    "Deactivate trang — ẩn khỏi public ngay lập tức, không cần điều kiện gì. Không có tool xoá trang — chỉ deactivate.",
    {
      page_key: z.string().describe("Page key"),
    },
    async ({ page_key }) => ok(await api("PATCH", `/mcp/pages/${page_key}/deactivate`, {})),
  );
}
