import type { NextConfig } from "next";
import { fileURLToPath } from "node:url";

const frontendRoot = fileURLToPath(new URL(".", import.meta.url));
const marketplaceApiProxyOrigin = process.env.MARKETPLACE_API_PROXY_ORIGIN;

function getMarketplaceApiProxyOrigin(): string | null {
  if (!marketplaceApiProxyOrigin) return null;

  const url = new URL(marketplaceApiProxyOrigin);
  if (
    !["http:", "https:"].includes(url.protocol) ||
    (url.pathname !== "/" && url.pathname !== "") ||
    url.search ||
    url.hash
  ) {
    throw new Error(
      "MARKETPLACE_API_PROXY_ORIGIN must be an HTTP(S) origin without a path, query, or fragment.",
    );
  }

  return url.origin;
}

const nextConfig: NextConfig = {
  /* config options here */
  experimental: {
    agentFeedback: true,
  },
  cacheComponents: true,
  partialPrefetching: true,
  turbopack: {
    root: frontendRoot,
    rules: {
      "*.css": {
        loaders: ["@tailwindcss/turbopack"],
        as: "*.css",
      },
    },
  },
  async rewrites() {
    const apiOrigin = getMarketplaceApiProxyOrigin();
    if (!apiOrigin) return [];

    return [
      {
        source: "/api/v1/:path*",
        destination: `${apiOrigin}/api/v1/:path*`,
      },
      {
        source: "/sanctum/:path*",
        destination: `${apiOrigin}/sanctum/:path*`,
      },
    ];
  },
};

export default nextConfig;
