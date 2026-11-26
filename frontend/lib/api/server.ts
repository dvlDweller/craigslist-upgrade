import "server-only";
import { API_BASE_URL, ApiError, apiFetchWithBase } from "@/lib/api/client";
import {
  getListingByIdPath,
  getListingsPath,
  type ListingQuery,
} from "@/lib/api/listings";
import type {
  ApiResponse,
  Category,
  ListingDetail,
  Listing,
  PaginatedResponse,
} from "@/lib/api/types";

function getServerApiBaseUrl(): string {
  if (/^https?:\/\//i.test(API_BASE_URL)) {
    return new URL(API_BASE_URL).toString().replace(/\/$/, "");
  }

  const proxyOrigin = process.env.MARKETPLACE_API_PROXY_ORIGIN;
  if (!proxyOrigin) {
    throw new ApiError(
      "The server-side marketplace API origin is not configured.",
      0,
    );
  }

  const origin = new URL(proxyOrigin);
  if (
    !["http:", "https:"].includes(origin.protocol) ||
    (origin.pathname !== "/" && origin.pathname !== "") ||
    origin.search ||
    origin.hash
  ) {
    throw new ApiError(
      "MARKETPLACE_API_PROXY_ORIGIN must be an HTTP(S) origin without a path, query, or fragment.",
      0,
    );
  }

  return new URL(API_BASE_URL, origin.origin).toString().replace(/\/$/, "");
}

export async function getServerCategories(): Promise<Category[]> {
  const response = await apiFetchWithBase<ApiResponse<Category[]>>(
    getServerApiBaseUrl(),
    "categories",
  );
  return response.data;
}

export function getServerListings(
  filters: ListingQuery = {},
): Promise<PaginatedResponse<Listing>> {
  return apiFetchWithBase<PaginatedResponse<Listing>>(
    getServerApiBaseUrl(),
    getListingsPath(filters),
  );
}

export async function getServerListingById(
  id: string,
): Promise<ListingDetail> {
  const response = await apiFetchWithBase<ApiResponse<ListingDetail>>(
    getServerApiBaseUrl(),
    getListingByIdPath(id),
  );
  return response.data;
}
