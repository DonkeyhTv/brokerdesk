// Shown to people evaluating the application. The back office keeps the same list in src/utils/stack.ts.
// Names and explanations are in the language catalogs, under about.layers.

export const SOURCE_URL = "https://github.com/DonkeyhTv/brokerdesk";

export type StackLayerId =
  "broker" | "portal" | "api" | "database" | "infrastructure" | "quality";

export const STACK: { id: StackLayerId; tools: string[] }[] = [
  {
    id: "broker",
    tools: [
      "Vue 3",
      "TypeScript",
      "Pinia",
      "Vue Router",
      "Tailwind CSS",
      "Vite",
    ],
  },
  {
    id: "portal",
    tools: ["Next.js 16", "React 19", "TypeScript", "Tailwind CSS"],
  },
  { id: "api", tools: ["Laravel 13", "PHP 8.4", "Sanctum"] },
  { id: "database", tools: ["PostgreSQL 17"] },
  { id: "infrastructure", tools: ["Docker", "Caddy", "nginx"] },
  {
    id: "quality",
    tools: ["Pest", "Vitest", "Playwright", "Larastan", "GitHub Actions"],
  },
];
