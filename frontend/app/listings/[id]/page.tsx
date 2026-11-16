import Link from "next/link";
import { notFound } from "next/navigation";
import MainContainer from "@/components/layout/main-container";
import ListingGallery from "@/components/listings/listing-gallery";
import { ApiError } from "@/lib/api/client";
import { getListingById } from "@/lib/api/listings";
import type { ListingDetail } from "@/lib/api/types";

interface ListingDetailPageProps {
  params: Promise<{ id: string }>;
}

function formatPrice(listing: ListingDetail): string {
  if (listing.price_type === "free") return "Free";
  if (listing.price_type === "contact") return "Contact for price";
  if (listing.price === null) return "Price unavailable";

  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: listing.currency || "PHP",
  }).format(Number(listing.price));
}

export default async function ListingDetailPage({
  params,
}: ListingDetailPageProps) {
  const { id } = await params;

  let listing: ListingDetail;

  try {
    listing = await getListingById(id);
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      notFound();
    }

    throw error;
  }

  const location = [listing.barangay, listing.municipality, listing.province]
    .filter(Boolean)
    .join(", ");

  return (
    <MainContainer>
      <div className="mb-6">
        <Link
          href="/listings"
          className="text-sm font-medium text-orange-600 hover:text-orange-700"
        >
          ← Back to listings
        </Link>
      </div>

      <div className="grid grid-cols-1 gap-10 lg:grid-cols-2">
        <section>
          <ListingGallery images={listing.images} title={listing.title} />
        </section>

        <section className="space-y-6">
          <div>
            <p className="text-sm font-medium text-orange-600">
              {listing.category.name}
            </p>

            <h1 className="mt-2 text-3xl font-bold tracking-tight text-gray-900">
              {listing.title}
            </h1>

            <p className="mt-4 text-2xl font-bold text-orange-600">
              {formatPrice(listing)}
            </p>

            {location && (
              <p className="mt-3 text-sm text-gray-500">📍 {location}</p>
            )}
          </div>

          <div className="border-t border-gray-200 pt-6">
            <h2 className="text-lg font-bold text-gray-900">Description</h2>

            <p className="mt-3 whitespace-pre-wrap leading-7 text-gray-600">
              {listing.description || "No description provided."}
            </p>
          </div>

          <div className="border-t border-gray-200 pt-6">
            <h2 className="text-lg font-bold text-gray-900">
              Seller information
            </h2>

            <p className="mt-3 text-gray-700">{listing.user.name}</p>
          </div>

          <div className="border-t border-gray-200 pt-6">
            <p className="text-xs text-gray-400">Listing ID: {listing.id}</p>

            <p className="mt-1 text-xs text-gray-400">
              Posted:{" "}
              {new Date(listing.created_at).toLocaleDateString("en-PH", {
                year: "numeric",
                month: "long",
                day: "numeric",
              })}
            </p>
          </div>
        </section>
      </div>
    </MainContainer>
  );
}
