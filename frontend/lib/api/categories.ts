
import { apiFetch } from "@/lib/api/client";
import type { ApiResponse, Category } from "@/lib/api/types";

export async function getCategories(): Promise<Category[]> {
  const response = await apiFetch<ApiResponse<Category[]>>("categories");

  return response.data;
}
