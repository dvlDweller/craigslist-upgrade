
import Image from "next/image";
import Link from "next/link";
import type { Listing } from "@/lib/api/types";

interface ListingCardProps {
  listing: Listing;
}

function formatPrice(listing: Listing): string {
  if (listing.price_type === "free") return "Free";
  if (listing.price_type === "contact") return "Contact for price";

  if (listing.price === null) return "Price unavailable";

  const amount = Number(listing.price);

  if (!Number.isFinite(amount)) return "Price unavailable";

  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: listing.currency || "PHP",
  }).format(amount);
}

export default function ListingCard({ listing }: ListingCardProps) {
  const location = [listing.municipality, listing.province]
    .filter(Boolean)
    .join(", ");

  return (
    <Link
      href={`/listings/${listing.id}`}
      className="group block overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-1 hover:shadow-lg"
    >
      <div className="relative aspect-[4/3] bg-gray-100">
        {listing.primary_image_url ? (
          <Image
            src={listing.primary_image_url}
            alt={listing.title}
            fill
            unoptimized
            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
            className="object-cover transition group-hover:scale-105"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-gray-400">
            No image available
          </div>
        )}
      </div>

      <div className="p-4">
        <p className="text-lg font-bold text-orange-600">
          {formatPrice(listing)}
        </p>

        <h2 className="mt-2 line-clamp-2 font-semibold text-gray-900 group-hover:text-orange-600">
          {listing.title}
        </h2>

        <p className="mt-2 text-sm text-gray-500">
          {listing.category.name}
        </p>

        {location && (
          <p className="mt-1 text-sm text-gray-400">{location}</p>
        )}
      </div>
    </Link>
  );
}
