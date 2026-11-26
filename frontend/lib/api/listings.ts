
import { apiFetch } from "@/lib/api/client";
import type {
  ApiResponse,
  ListingDetail,
  PaginatedResponse,
  Listing,
  PriceType,
} from "@/lib/api/types";

export interface ListingQuery {
  q?: string;
  category?: string;
  municipality?: string[];
  barangay?: string;
  min_price?: number;
  max_price?: number;
  price_type?: PriceType;
  sort?: "newest" | "price_asc" | "price_desc";
  page?: number;
  per_page?: number;
}

export function getListingsPath(filters: ListingQuery = {}): string {
  const params = new URLSearchParams();

  if (filters.q) params.set("q", filters.q);
  if (filters.category) params.set("category", filters.category);
  if (filters.barangay) params.set("barangay", filters.barangay);

  if (filters.min_price !== undefined) {
    params.set("min_price", String(filters.min_price));
  }

  if (filters.max_price !== undefined) {
    params.set("max_price", String(filters.max_price));
  }

  if (filters.price_type) params.set("price_type", filters.price_type);
  if (filters.sort) params.set("sort", filters.sort);
  if (filters.page !== undefined) params.set("page", String(filters.page));
  if (filters.per_page !== undefined) {
    params.set("per_page", String(filters.per_page));
  }

  filters.municipality?.forEach((municipality) => {
    params.append("municipality[]", municipality);
  });

  const query = params.toString();

  return `listings${query ? `?${query}` : ""}`;
}

export async function getListings(
  filters: ListingQuery = {},
): Promise<PaginatedResponse<Listing>> {
  return apiFetch<PaginatedResponse<Listing>>(getListingsPath(filters));
}

export function getListingByIdPath(id: string): string {
  return `listings/${encodeURIComponent(id)}`;
}

export async function getListingById(
  id: string,
): Promise<ListingDetail> {
  const response = await apiFetch<ApiResponse<ListingDetail>>(
    getListingByIdPath(id),
  );

  return response.data;
}
