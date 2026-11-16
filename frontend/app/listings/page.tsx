import MainContainer from "@/components/layout/main-container";
import CategoryBrowser from "@/components/listings/category-browser";
import ListingFilters from "@/components/listings/listing-filters";
import ListingGrid from "@/components/listings/listing-grid";
import ListingPagination from "@/components/listings/listing-pagination";
import { getCategories } from "@/lib/api/categories";
import { getListings } from "@/lib/api/listings";
import type {
  Category,
  Listing,
  PaginatedResponse,
  PriceType,
} from "@/lib/api/types";

type SearchParams = {
  [key: string]: string | string[] | undefined;
};

interface ListingsPageProps {
  searchParams: Promise<SearchParams>;
}

function getValue(value: string | string[] | undefined): string {
  return Array.isArray(value) ? value[0] ?? "" : value ?? "";
}

export default async function ListingsPage({
  searchParams,
}: ListingsPageProps) {
  const params = await searchParams;

  const q = getValue(params.q);
  const category = getValue(params.category);
  const minPrice = getValue(params.min_price);
  const maxPrice = getValue(params.max_price);
  const priceType = getValue(params.price_type);
  const sort = getValue(params.sort);

  const municipality = Array.isArray(params["municipality[]"])
    ? params["municipality[]"]
    : params["municipality[]"]
      ? [params["municipality[]"]]
      : [];

  const page = Math.max(1, Number(getValue(params.page)) || 1);

  const validPriceType: PriceType | undefined =
    ["fixed", "free", "contact"].includes(priceType)
      ? (priceType as PriceType)
      : undefined;

  const validSort =
    ["newest", "price_asc", "price_desc"].includes(sort)
      ? (sort as "newest" | "price_asc" | "price_desc")
      : "newest";

  const min = minPrice !== "" ? Number(minPrice) : undefined;
  const max = maxPrice !== "" ? Number(maxPrice) : undefined;

  let listingsData: PaginatedResponse<Listing> | null = null;
  let categories: Category[] = [];
  let listingsError = false;

  try {
    listingsData = await getListings({
      q,
      category,
      municipality,
      min_price:
        min !== undefined && Number.isFinite(min) ? min : undefined,
      max_price:
        max !== undefined && Number.isFinite(max) ? max : undefined,
      price_type: validPriceType,
      sort: validSort,
      page,
    });
  } catch {
    listingsError = true;
  }

  try {
    categories = await getCategories();
  } catch {
    categories = [];
  }

  const queryParams = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (key === "page" || value === undefined) return;

    if (Array.isArray(value)) {
      value.forEach((item) => queryParams.append(key, item));
    } else {
      queryParams.set(key, value);
    }
  });

  const currentQueryString = queryParams.toString();

  return (
    <MainContainer>
      <section className="mb-8">
        <h1 className="text-3xl font-bold tracking-tight text-gray-900">
          Browse Listings
        </h1>

        <p className="mt-2 text-gray-500">
          Discover items and opportunities in your local community.
        </p>
      </section>

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-4">
        <aside className="space-y-6 lg:col-span-1">
          <CategoryBrowser
            categories={categories}
            selectedCategory={category}
            queryString={currentQueryString}
          />

          <ListingFilters
            q={q}
            category={category}
            municipality={municipality[0] ?? ""}
            min_price={minPrice}
            max_price={maxPrice}
            price_type={validPriceType}
            sort={validSort}
          />
        </aside>

        <div className="space-y-8 lg:col-span-3">
          {listingsError ? (
            <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-center">
              <h2 className="font-semibold text-red-800">
                Unable to load listings
              </h2>
              <p className="mt-2 text-sm text-red-700">
                Please check your connection and try again later.
              </p>
            </div>
          ) : (
            <>
              <p className="text-sm text-gray-500">
                {listingsData?.meta.total ?? 0} listings found
              </p>

              <ListingGrid listings={listingsData?.data ?? []} />

              {listingsData && (
                <ListingPagination
                  currentPage={listingsData.meta.current_page}
                  lastPage={listingsData.meta.last_page}
                  queryString={currentQueryString}
                />
              )}
            </>
          )}
        </div>
      </div>
    </MainContainer>
  );
}
