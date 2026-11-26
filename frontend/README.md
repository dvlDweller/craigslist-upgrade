This is a [Next.js](https://nextjs.org) project bootstrapped with [`create-next-app`](https://nextjs.org/docs/app/api-reference/cli/create-next-app).

## Getting Started

First, run the development server:

```bash
npm run dev
# or
yarn dev
# or
pnpm dev
# or
bun dev
```

Open [http://localhost:3000](http://localhost:3000) with your browser to see the result.

You can start editing the page by modifying `app/page.tsx`. The page auto-updates as you edit the file.

This project uses [`next/font`](https://nextjs.org/docs/app/building-your-application/optimizing/fonts) to automatically optimize and load [Geist](https://vercel.com/font), a new font family for Vercel.

## Marketplace API configuration

- Local development defaults to `http://localhost:8000/api/v1` when `NEXT_PUBLIC_API_URL` is not set.
- Production defaults to the same-origin path `/api/v1`. Configure `MARKETPLACE_API_PROXY_ORIGIN` to the backend origin (for example, the Render service origin) to enable Next.js rewrites for both `/api/v1/*` and `/sanctum/*`.
- Set `NEXT_PUBLIC_API_URL` to an absolute API base URL such as `https://backend.example.com/api/v1` only when calling public endpoints directly. Direct calls across separate Vercel and Render domains are not compatible with our current cookie-based Sanctum SPA authentication; the same-origin proxy is required for the complete authenticated marketplace workflow.

For Server Components, the client uses an absolute backend URL from the server-only `MARKETPLACE_API_PROXY_ORIGIN`; this avoids passing relative paths to Node fetch. Browser requests continue to use the same-origin rewrite.

For Vercel Preview with the same-origin proxy, set `NEXT_PUBLIC_API_URL=/api/v1` and `MARKETPLACE_API_PROXY_ORIGIN` to the backend origin. The origin must not include a path, query, or fragment. Cookie-based Sanctum authentication also requires the backend to trust the Preview origin and issue session/CSRF cookies that the Preview hostname can use.

## Learn More

To learn more about Next.js, take a look at the following resources:

- [Next.js Documentation](https://nextjs.org/docs) - learn about Next.js features and API.
- [Learn Next.js](https://nextjs.org/learn) - an interactive Next.js tutorial.

You can check out [the Next.js GitHub repository](https://github.com/vercel/next.js) - your feedback and contributions are welcome!

## Deploy on Vercel

The easiest way to deploy your Next.js app is to use the [Vercel Platform](https://vercel.com/new?utm_medium=default-template&filter=next.js&utm_source=create-next-app&utm_campaign=create-next-app-readme) from the creators of Next.js.

Check out our [Next.js deployment documentation](https://nextjs.org/docs/app/building-your-application/deploying) for more details.
