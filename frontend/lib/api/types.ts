
export type PriceType = "fixed" | "free" | "contact";

export interface Category {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  parent_id: number | null;
  sort_order: number;
  children: Category[];
}

export interface MarketplaceLocation {
  municipality: string;
  official_name: string;
  psgc_code: string | null;
  barangays: string[];
}

export interface ListingCategory {
  id: number;
  name: string;
  slug: string;
}

export interface Listing {
  id: number;
  title: string;
  price_type: PriceType;
  price: string | null;
  currency: string;
  municipality: string | null;
  province: string | null;
  status: string;
  primary_image_url: string | null;
  created_at: string;
  category: ListingCategory;
}

export interface ListingImage {
  id: number;
  url: string;
  sort_order: number;
  is_primary: boolean;
}

export interface ListingDetail {
  id: number;
  title: string;
  description: string;
  price_type: PriceType;
  price: string | null;
  currency: string;
  municipality: string | null;
  barangay: string | null;
  province: string | null;
  status: string;
  published_at: string | null;
  created_at: string;
  updated_at: string;
  category: ListingCategory;
  user: {
    id: number;
    name: string;
  };
  images: ListingImage[];
}

export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  path: string;
  per_page: number;
  to: number | null;
  total: number;
}

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: PaginationLinks;
  meta: PaginationMeta;
}

export interface ApiResponse<T> {
  data: T;
}
