import { apiFetch } from "@/lib/api/client";
import { csrfRequestHeaders } from "@/lib/api/auth";
import type { ApiResponse, ListingDetail, PaginatedResponse, Listing } from "@/lib/api/types";

export interface SellerListingInput {
  category_id: number;
  title: string;
  description: string;
  price_type: "fixed" | "free" | "contact";
  price?: string;
  municipality?: string | null;
  barangay?: string | null;
}

export async function getSellerListings(): Promise<PaginatedResponse<Listing>> {
  return apiFetch("seller/listings", { credentials: "include" });
}

export async function getSellerListing(id: string): Promise<ListingDetail> {
  const response = await apiFetch<ApiResponse<ListingDetail>>(`seller/listings/${encodeURIComponent(id)}`, { credentials: "include" });
  return response.data;
}

export async function createSellerListing(input: SellerListingInput): Promise<ListingDetail> {
  const csrf = await csrfRequestHeaders();
  const response = await apiFetch<ApiResponse<ListingDetail>>("seller/listings", {
    method: "POST", credentials: "include",
    headers: { "Content-Type": "application/json", ...csrf },
    body: JSON.stringify(input),
  });
  return response.data;
}

export async function updateSellerListing(id: string, input: Partial<SellerListingInput>): Promise<ListingDetail> {
  const csrf = await csrfRequestHeaders();
  const response = await apiFetch<ApiResponse<ListingDetail>>(`seller/listings/${encodeURIComponent(id)}`, {
    method: "PATCH", credentials: "include",
    headers: { "Content-Type": "application/json", ...csrf },
    body: JSON.stringify(input),
  });
  return response.data;
}

export async function publishSellerListing(id: string): Promise<ListingDetail> {
  const csrf = await csrfRequestHeaders();
  const response = await apiFetch<ApiResponse<ListingDetail>>(`seller/listings/${encodeURIComponent(id)}/publish`, {
    method: "POST", credentials: "include", headers: csrf,
  });
  return response.data;
}
