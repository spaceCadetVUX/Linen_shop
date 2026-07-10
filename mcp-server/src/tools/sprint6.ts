import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { api, ok } from "../client.js";

export function registerSprint6Tools(server: McpServer) {

  server.tool(
    "import_from_specs",
    "Parse datasheet/spec text thành attributes thô + tra cứu manufacturer/category. KHÔNG tự sinh content, KHÔNG tự lưu — Claude đọc response, tự viết translations/SEO/FAQ rồi gọi save_product để lưu chính thức.",
    {
      slug:              z.string().describe("Product slug — entity đích để import vào"),
      manufacturer_slug: z.string().optional().describe("Slug của manufacturer"),
      category_slug:     z.string().optional().describe("Slug của category"),
      specs_text:        z.string().describe("Raw text từ datasheet, catalog, hoặc spec sheet"),
      locales:           z.array(z.string()).default(["vi","en"]).describe('Locales Claude sẽ generate content, e.g. ["vi","en"]'),
    },
    async (body) => ok(await api("POST", "/mcp/import/product-from-specs", body)),
  );
}
