
import Link from "next/link";
import MainContainer from "@/components/layout/main-container";
import ListingGrid from "@/components/listings/listing-grid";
import { getCategories } from "@/lib/api/categories";
import { getListings } from "@/lib/api/listings";
import type { Category, Listing } from "@/lib/api/types";

export default async function Home() {
  let categories: Category[] = [];
  let listings: Listing[] = [];
  let categoriesUnavailable = false;
  let listingsUnavailable = false;

  const [categoriesResult, listingsResult] = await Promise.allSettled([
    getCategories(),
    getListings({ sort: "newest", per_page: 8 }),
  ]);

  if (categoriesResult.status === "fulfilled") categories = categoriesResult.value;
  else categoriesUnavailable = true;
  if (listingsResult.status === "fulfilled") listings = listingsResult.value.data;
  else listingsUnavailable = true;

  return (
    <MainContainer className="flex flex-1 flex-col">
      <section className="mx-auto flex w-full max-w-4xl flex-col items-center py-16 text-center sm:py-20">
        <span className="mb-6 rounded-full bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700">
          Your Local Marketplace
        </span>

        <h1 className="max-w-3xl text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
          Buy and sell with your{" "}
          <span className="text-orange-600">community.</span>
        </h1>

        <p className="mt-6 max-w-2xl text-base leading-8 text-gray-500 sm:text-lg">
          A simple place to discover local opportunities and connect with
          people in your community. Find what you need and make room for
          something new.
        </p>

        <div className="mt-10">
          <Link
            href="/listings"
            className="inline-flex items-center justify-center rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-orange-700"
          >
            Browse Listings
          </Link>
        </div>

        <p className="mt-4 text-sm text-gray-400">
          Discover something new in your local community.
        </p>
      </section>
      <section className="border-t border-gray-200 py-10">
        <div className="mb-5 flex items-center justify-between gap-4">
          <h2 className="text-2xl font-bold text-gray-900">Explore categories</h2>
          <Link href="/listings" className="text-sm font-semibold text-orange-600 hover:text-orange-700">All listings</Link>
        </div>
        {categoriesUnavailable ? (
          <p role="status" className="rounded-lg bg-amber-50 p-4 text-sm text-amber-800">Categories are temporarily unavailable. You can still browse all listings.</p>
        ) : categories.length === 0 ? (
          <p className="text-sm text-gray-500">No categories are available yet.</p>
        ) : (
          <div className="flex flex-wrap gap-3">
            {categories.map((category) => <Link key={category.id} href={`/listings?category=${encodeURIComponent(category.slug)}`} className="rounded-full border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:border-orange-300 hover:text-orange-700">{category.name}</Link>)}
          </div>
        )}
      </section>
      <section className="border-t border-gray-200 py-10">
        <div className="mb-5 flex items-center justify-between gap-4">
          <h2 className="text-2xl font-bold text-gray-900">Recent listings</h2>
          <Link href="/listings" className="text-sm font-semibold text-orange-600 hover:text-orange-700">Browse all</Link>
        </div>
        {listingsUnavailable ? (
          <p role="status" className="rounded-lg bg-red-50 p-4 text-sm text-red-800">Recent listings could not be loaded. Please try again later.</p>
        ) : <ListingGrid listings={listings} />}
      </section>
    </MainContainer>
  );
}
