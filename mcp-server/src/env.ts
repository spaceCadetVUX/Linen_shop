import { config } from "dotenv";
import { fileURLToPath } from "url";
import { dirname, join } from "path";

// Side-effect only module — must be the FIRST import in index.ts so process.env
// is populated before client.ts (imported transitively via the tool modules)
// reads KNXSTORE_API_BASE/KNXSTORE_API_TOKEN at its own module top-level.
// Resolved relative to this file, not process.cwd(), so it works regardless
// of the directory the MCP client spawns this process from.
config({ path: join(dirname(fileURLToPath(import.meta.url)), "..", ".env") });
