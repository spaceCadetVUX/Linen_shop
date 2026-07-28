import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { api, ok } from "../client.js";

export function registerSprint7Tools(server: McpServer) {

  server.tool(
    "list_filter_groups",
    "Liệt kê toàn bộ filter group + values. Gọi trước khi gán filter_values cho product để biết slug hợp lệ.",
    {},
    async () => ok(await api("GET", "/mcp/filter-groups")),
  );

  server.tool(
    "get_filter_group_context",
    "Load full filter group context (kèm values) trước khi sửa.",
    {
      slug: z.string().describe("Filter group slug"),
    },
    async ({ slug }) => ok(await api("GET", `/mcp/filter-groups/${slug}/context`)),
  );

  server.tool(
    "check_filter_group_readiness",
    "Kiểm tra filter group đủ điều kiện activate chưa (có value active, value màu có color_hex...). Gọi trước activate_filter_group.",
    {
      slug: z.string().describe("Filter group slug"),
    },
    async ({ slug }) => ok(await api("GET", `/mcp/filter-groups/${slug}/readiness`)),
  );

  server.tool(
    "save_filter_group",
    "Upsert filter group (nhóm thuộc tính lọc/biến thể, VD: Color, Size) — tạo mới hoặc update, kèm values con.",
    {
      slug: z.string().describe("Filter group slug — dùng làm định danh"),
      name: z.string().optional().describe("Tên group (vi)"),
      name_en: z.string().optional().describe("Tên group (en)"),
      type: z.enum(["text", "color"]).optional().describe("text = thuộc tính thường, color = swatch màu"),
      is_variant_dimension: z.boolean().optional().describe("true = values thuộc group này dùng để sinh Product Variant (VD: Color, Size)"),
      sort_order: z.number().optional().describe("Thứ tự sắp xếp"),
      overwrite_existing: z.boolean().default(false).describe("true = ghi đè field đã có"),
      dry_run: z.boolean().default(false).describe("true = preview, không lưu DB"),
      values: z.array(z.object({
        name: z.string().describe("Tên value (vi) — bắt buộc"),
        name_en: z.string().optional().describe("Tên value (en)"),
        slug: z.string().optional().describe("Slug value — để trống sẽ tự sinh từ name_en/name"),
        color_hex: z.string().regex(/^#[0-9A-Fa-f]{6}$/).optional().describe("Chỉ áp dụng khi group type=color, dạng #RRGGBB"),
        sort_order: z.number().optional().describe("Thứ tự sắp xếp trong group"),
        is_active: z.boolean().optional().describe("Mặc định true khi tạo mới"),
      })).optional().describe("Danh sách value con — upsert theo slug/name, KHÔNG xoá value không có trong mảng này"),
    },
    async ({ slug, ...body }) => ok(await api("PUT", `/mcp/filter-groups/${slug}`, body)),
  );

  server.tool(
    "activate_filter_group",
    "Activate filter group sau khi readiness pass — value bắt đầu hiện trên storefront (facet filter / variant option).",
    {
      slug: z.string().describe("Filter group slug"),
    },
    async ({ slug }) => ok(await api("PATCH", `/mcp/filter-groups/${slug}/activate`, {})),
  );

  server.tool(
    "deactivate_filter_group",
    "Deactivate filter group — ẩn khỏi storefront (facet filter / variant option) ngay lập tức, không cần điều kiện gì. Để deactivate 1 value riêng lẻ, dùng save_filter_group với values: [{ name, is_active: false }].",
    {
      slug: z.string().describe("Filter group slug"),
    },
    async ({ slug }) => ok(await api("PATCH", `/mcp/filter-groups/${slug}/deactivate`, {})),
  );
}
