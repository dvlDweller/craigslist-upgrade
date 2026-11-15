
import Link from "next/link";
import MainContainer from "@/components/layout/main-container";

export default function ListingNotFound() {
  return (
    <MainContainer>
      <div className="mx-auto max-w-xl py-20 text-center">
        <h1 className="text-3xl font-bold text-gray-900">
          Listing not found
        </h1>

        <p className="mt-4 text-gray-500">
          Sorry, this listing may have been removed or is no longer available.
        </p>

        <Link
          href="/listings"
          className="mt-8 inline-block rounded-lg bg-orange-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-orange-700"
        >
          Browse other listings
        </Link>
      </div>
    </MainContainer>
  );
}
