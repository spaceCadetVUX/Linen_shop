import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { api, ok } from "../client.js";

export function registerSprint8Tools(server: McpServer) {

  server.tool(
    "list_product_variants",
    "Liệt kê toàn bộ variant của 1 product — combination, giá VND/USD, tồn kho, trạng thái active.",
    {
      slug: z.string().describe("Product slug"),
    },
    async ({ slug }) => ok(await api("GET", `/mcp/products/${slug}/variants`)),
  );

  server.tool(
    "save_product_variant",
    "Sửa 1 variant đã tồn tại (giá VND/USD, tồn kho, trạng thái availability) — chỉ đụng đúng variant này qua sku, không ảnh hưởng variant khác. Không đổi được combination (Color/Size) — việc đó chỉ làm qua admin.",
    {
      slug: z.string().describe("Product slug"),
      sku: z.string().describe("SKU của variant cần sửa"),
      price_vnd: z.number().optional().describe("Giá bán VND"),
      sale_price_vnd: z.number().nullable().optional().describe("Giá khuyến mãi VND — null = xoá KM"),
      price_usd: z.number().nullable().optional().describe("Giá bán USD"),
      sale_price_usd: z.number().nullable().optional().describe("Giá khuyến mãi USD — null = xoá KM"),
      stock_quantity: z.number().optional().describe("Số lượng tồn kho"),
      availability_status: z.enum(["auto", "out_of_stock", "pre_order"]).optional().describe("auto = suy ra từ stock_quantity, 2 giá trị còn lại là ghi đè thủ công"),
      is_active: z.literal(false).optional().describe("Chỉ nhận false (tắt) — muốn bật lại dùng activate_product_variant để qua điều kiện price > 0"),
    },
    async ({ slug, sku, ...body }) => ok(await api("PUT", `/mcp/products/${slug}/variants/${sku}`, body)),
  );

  server.tool(
    "generate_product_variants",
    "Sinh variant từ tổ hợp Cartesian các FilterValue thuộc group is_variant_dimension đã gán cho product (VD: Color × Size). Chỉ TẠO tổ hợp còn thiếu — không sửa/xoá variant đã có. Cần gán filter_values qua save_product trước. Product phải có sku trước (set qua save_product) — thiếu sku sẽ bị từ chối, tránh sinh ra variant sku dạng 'VAR-...' không gắn với product.",
    {
      slug: z.string().describe("Product slug"),
    },
    async ({ slug }) => ok(await api("POST", `/mcp/products/${slug}/generate-variants`, {})),
  );

  server.tool(
    "activate_product_variant",
    "Activate 1 variant — điều kiện: price_vnd phải > 0.",
    {
      slug: z.string().describe("Product slug"),
      sku: z.string().describe("SKU của variant"),
    },
    async ({ slug, sku }) => ok(await api("PATCH", `/mcp/products/${slug}/variants/${sku}/activate`, {})),
  );

  server.tool(
    "deactivate_product_variant",
    "Deactivate 1 variant — không cần điều kiện gì.",
    {
      slug: z.string().describe("Product slug"),
      sku: z.string().describe("SKU của variant"),
    },
    async ({ slug, sku }) => ok(await api("PATCH", `/mcp/products/${slug}/variants/${sku}/deactivate`, {})),
  );
}
