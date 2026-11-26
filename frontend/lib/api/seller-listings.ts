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

export async function uploadSellerListingImages(id: string, files: File[]): Promise<ListingDetail> {
  const csrf = await csrfRequestHeaders();
  const formData = new FormData();
  files.forEach((file) => formData.append("images[]", file));
  const response = await apiFetch<ApiResponse<ListingDetail>>(`seller/listings/${encodeURIComponent(id)}/images`, {
    method: "POST",
    credentials: "include",
    headers: csrf,
    body: formData,
  });
  return response.data;
}

export async function deleteSellerListingImage(id: string, imageId: number): Promise<void> {
  const csrf = await csrfRequestHeaders();
  await apiFetch<null>(`seller/listings/${encodeURIComponent(id)}/images/${imageId}`, {
    method: "DELETE",
    credentials: "include",
    headers: csrf,
  });
}

export async function setSellerListingPrimaryImage(id: string, imageId: number): Promise<ListingDetail> {
  const csrf = await csrfRequestHeaders();
  const response = await apiFetch<ApiResponse<ListingDetail>>(`seller/listings/${encodeURIComponent(id)}/images/${imageId}/primary`, {
    method: "POST",
    credentials: "include",
    headers: csrf,
  });
  return response.data;
}

export async function reorderSellerListingImages(id: string, imageIds: number[]): Promise<ListingDetail> {
  const csrf = await csrfRequestHeaders();
  const response = await apiFetch<ApiResponse<ListingDetail>>(`seller/listings/${encodeURIComponent(id)}/images/reorder`, {
    method: "PATCH",
    credentials: "include",
    headers: { "Content-Type": "application/json", ...csrf },
    body: JSON.stringify({ image_ids: imageIds }),
  });
  return response.data;
}

export async function markSellerListingSold(id: string): Promise<ListingDetail> {
  const csrf = await csrfRequestHeaders();
  const response = await apiFetch<ApiResponse<ListingDetail>>(`seller/listings/${encodeURIComponent(id)}/mark-sold`, {
    method: "POST",
    credentials: "include",
    headers: csrf,
  });
  return response.data;
}
