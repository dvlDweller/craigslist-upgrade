
import Link from "next/link";
import MainContainer from "@/components/layout/main-container";

export default function NotFound() {
  return (
    <MainContainer>
      <div className="mx-auto max-w-xl py-20 text-center">
        <p className="text-sm font-semibold uppercase tracking-wider text-orange-600">
          404 Error
        </p>

        <h1 className="mt-3 text-4xl font-bold text-gray-900">
          Page not found
        </h1>

        <p className="mt-4 text-gray-500">
          Sorry, the page you&apos;re looking for doesn&apos;t exist or may have been moved.
        </p>

        <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
          <Link
            href="/"
            className="rounded-lg bg-orange-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-orange-700"
          >
            Go to Homepage
          </Link>

          <Link
            href="/listings"
            className="rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
          >
            Browse Listings
          </Link>
        </div>
      </div>
    </MainContainer>
  );
}
