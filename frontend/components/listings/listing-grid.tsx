
import type { Listing } from "@/lib/api/types";
import ListingCard from "@/components/listings/listing-card";

interface ListingGridProps {
  listings: Listing[];
}

export default function ListingGrid({ listings }: ListingGridProps) {
  if (listings.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center">
        <h2 className="text-lg font-semibold text-gray-900">
          No listings found
        </h2>
        <p className="mt-2 text-sm text-gray-500">
          Try adjusting your search or filters to find something.
        </p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {listings.map((listing) => (
        <ListingCard key={listing.id} listing={listing} />
      ))}
    </div>
  );
}
