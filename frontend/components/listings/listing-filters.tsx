
import type { PriceType } from "@/lib/api/types";

interface ListingFiltersProps {
  q?: string;
  category?: string;
  min_price?: string;
  max_price?: string;
  price_type?: PriceType;
  sort?: "newest" | "price_asc" | "price_desc";
}

export default function ListingFilters({
  q = "",
  category = "",
  min_price = "",
  max_price = "",
  price_type,
  sort = "newest",
}: ListingFiltersProps) {
  return (
    <form
      action="/listings"
      method="GET"
      className="space-y-4 rounded-xl border border-gray-200 bg-white p-4"
    >
      {category && (
        <input type="hidden" name="category" value={category} />
      )}

      <div>
        <label
          htmlFor="listing-search"
          className="mb-1 block text-sm font-medium text-gray-700"
        >
          Search listings
        </label>
        <input
          id="listing-search"
          type="search"
          name="q"
          defaultValue={q}
          placeholder="What are you looking for?"
          className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
        />
      </div>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <label
            htmlFor="min-price"
            className="mb-1 block text-sm font-medium text-gray-700"
          >
            Minimum price
          </label>
          <input
            id="min-price"
            type="number"
            name="min_price"
            min="0"
            step="0.01"
            defaultValue={min_price}
            placeholder="₱ Min"
            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label
            htmlFor="max-price"
            className="mb-1 block text-sm font-medium text-gray-700"
          >
            Maximum price
          </label>
          <input
            id="max-price"
            type="number"
            name="max_price"
            min="0"
            step="0.01"
            defaultValue={max_price}
            placeholder="₱ Max"
            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-orange-500"
          />
        </div>
      </div>

      <div>
        <label
          htmlFor="price-type"
          className="mb-1 block text-sm font-medium text-gray-700"
        >
          Price type
        </label>
        <select
          id="price-type"
          name="price_type"
          defaultValue={price_type ?? ""}
          className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm"
        >
          <option value="">All price types</option>
          <option value="fixed">Fixed price</option>
          <option value="free">Free</option>
          <option value="contact">Contact for price</option>
        </select>
      </div>

      <div>
        <label
          htmlFor="listing-sort"
          className="mb-1 block text-sm font-medium text-gray-700"
        >
          Sort by
        </label>
        <select
          id="listing-sort"
          name="sort"
          defaultValue={sort}
          className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm"
        >
          <option value="newest">Newest first</option>
          <option value="price_asc">Price: Low to high</option>
          <option value="price_desc">Price: High to low</option>
        </select>
      </div>

      <button
        type="submit"
        className="w-full rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-700"
      >
        Apply Filters
      </button>
    </form>
  );
}
