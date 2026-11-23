import { apiFetch } from "@/lib/api/client";
import type { ApiResponse, MarketplaceLocation } from "@/lib/api/types";

export async function getLocations(): Promise<MarketplaceLocation[]> {
  const response = await apiFetch<ApiResponse<MarketplaceLocation[]>>("locations");
  return response.data;
}
