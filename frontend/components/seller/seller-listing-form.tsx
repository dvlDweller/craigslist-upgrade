"use client";

import { useEffect, useMemo, useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import MainContainer from "@/components/layout/main-container";
import { ApiError } from "@/lib/api/client";
import { getCategories } from "@/lib/api/categories";
import { createSellerListing, getSellerListing, publishSellerListing, updateSellerListing, type SellerListingInput } from "@/lib/api/seller-listings";
import type { Category, ListingDetail, PriceType } from "@/lib/api/types";

export default function SellerListingForm({ id }: { id?: string }) {
  const router = useRouter();
  const [categories, setCategories] = useState<Category[]>([]);
  const [listing, setListing] = useState<ListingDetail | null>(null);
  const [loading, setLoading] = useState(Boolean(id));
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [priceType, setPriceType] = useState<PriceType>("fixed");

  useEffect(() => {
    let active = true;
    const categoryRequest = getCategories().then((items) => { if (active) setCategories(items); }).catch(() => { if (active) setError("Categories could not be loaded. Please try again later."); });
    const listingRequest = id ? getSellerListing(id).then((item) => { if (active) { setListing(item); setPriceType(item.price_type); } }).catch((cause) => { if (active) setError(cause instanceof ApiError && cause.status === 404 ? "Listing not found." : cause instanceof Error ? cause.message : "Unable to load this listing."); }) : Promise.resolve();
    Promise.all([categoryRequest, listingRequest]).finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [id]);

  const categoryOptions = useMemo(() => categories.flatMap((category) => [category, ...category.children]), [categories]);

  function inputFromForm(form: HTMLFormElement): SellerListingInput {
    const formData = new FormData(form);
    const type = String(formData.get("price_type")) as PriceType;
    const input: SellerListingInput = {
      category_id: Number(formData.get("category_id")),
      title: String(formData.get("title") ?? "").trim(),
      description: String(formData.get("description") ?? "").trim(),
      price_type: type,
      municipality: String(formData.get("municipality") ?? "").trim() || null,
      barangay: String(formData.get("barangay") ?? "").trim() || null,
    };
    if (type === "fixed") input.price = String(formData.get("price") ?? "");
    return input;
  }

  async function save(event: FormEvent<HTMLFormElement>): Promise<ListingDetail | null> {
    event.preventDefault();
    setBusy(true);
    setError("");
    try {
      const input = inputFromForm(event.currentTarget);
      const saved = id ? await updateSellerListing(id, input) : await createSellerListing(input);
      setListing(saved);
      if (!id) router.replace(`/seller/listings/${saved.id}`);
      return saved;
    } catch (cause) {
      setError(cause instanceof ApiError && cause.errors ? Object.values(cause.errors).flat().join(" ") : cause instanceof Error ? cause.message : "Unable to save this listing.");
      return null;
    } finally {
      setBusy(false);
    }
  }

  async function publish(form: HTMLFormElement) {
    setBusy(true);
    setError("");
    try {
      const input = inputFromForm(form);
      if (id) await updateSellerListing(id, input);
      const published = await publishSellerListing(id!);
      setListing(published);
    } catch (cause) {
      setError(cause instanceof ApiError && cause.errors ? Object.values(cause.errors).flat().join(" ") : cause instanceof Error ? cause.message : "Unable to publish this listing.");
    } finally {
      setBusy(false);
    }
  }

  if (loading) return <MainContainer><p role="status" className="py-20 text-center text-gray-500">Loading listing…</p></MainContainer>;
  if (id && !listing && error) return <MainContainer><div role="alert" className="mx-auto max-w-xl py-20 text-center"><h1 className="text-2xl font-bold text-gray-900">{error === "Listing not found." ? "Listing not found" : "Unable to load listing"}</h1><p className="mt-3 text-gray-600">{error === "Listing not found." ? "It may have been removed or you may not have access." : error}</p></div></MainContainer>;

  return <MainContainer><section className="mx-auto max-w-2xl py-10"><h1 className="text-3xl font-bold text-gray-900">{id ? "Edit listing" : "Create listing"}</h1><p className="mt-2 text-gray-600">Save your listing as a draft, then publish it when it is ready.</p>
    <form onSubmit={save} className="mt-6 space-y-5 rounded-xl border border-gray-200 bg-white p-6">
      <div><label htmlFor="category_id" className="mb-1 block text-sm font-medium">Category</label><select id="category_id" name="category_id" required defaultValue={listing?.category.id ?? ""} className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5"><option value="" disabled>Select a category</option>{categoryOptions.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select></div>
      <div><label htmlFor="title" className="mb-1 block text-sm font-medium">Title</label><input id="title" name="title" required maxLength={120} defaultValue={listing?.title ?? ""} className="w-full rounded-lg border border-gray-300 px-3 py-2.5" /></div>
      <div><label htmlFor="description" className="mb-1 block text-sm font-medium">Description</label><textarea id="description" name="description" required maxLength={5000} rows={6} defaultValue={listing?.description ?? ""} className="w-full rounded-lg border border-gray-300 px-3 py-2.5" /></div>
      <div><label htmlFor="price_type" className="mb-1 block text-sm font-medium">Price type</label><select id="price_type" name="price_type" value={priceType} onChange={(event) => setPriceType(event.target.value as PriceType)} className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5"><option value="fixed">Fixed price</option><option value="free">Free</option><option value="contact">Contact for price</option></select></div>
      {priceType === "fixed" && <div><label htmlFor="price" className="mb-1 block text-sm font-medium">Price (PHP)</label><input id="price" name="price" type="number" min="0.01" max="99999999.99" step="0.01" required defaultValue={listing?.price ?? ""} className="w-full rounded-lg border border-gray-300 px-3 py-2.5" /></div>}
      <div><label htmlFor="municipality" className="mb-1 block text-sm font-medium">Municipality</label><input id="municipality" name="municipality" maxLength={100} defaultValue={listing?.municipality ?? ""} className="w-full rounded-lg border border-gray-300 px-3 py-2.5" /><p className="mt-1 text-xs text-gray-500">Location choices will be connected to the public locations endpoint after it is merged.</p></div>
      <div><label htmlFor="barangay" className="mb-1 block text-sm font-medium">Barangay</label><input id="barangay" name="barangay" maxLength={100} defaultValue={listing?.barangay ?? ""} className="w-full rounded-lg border border-gray-300 px-3 py-2.5" /></div>
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      <div className="flex flex-wrap gap-3"><button type="submit" disabled={busy} className="rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-60">{busy ? "Saving…" : listing?.status === "active" ? "Save changes" : "Save draft"}</button>{id && listing?.status === "draft" && <button type="button" disabled={busy} onClick={(event) => { const form = event.currentTarget.form; if (form) void publish(form); }} className="rounded-lg border border-orange-600 px-5 py-2.5 text-sm font-semibold text-orange-700 hover:bg-orange-50 disabled:opacity-60">{busy ? "Publishing…" : "Save and publish"}</button>}</div>
      {listing?.status === "active" && <p className="text-sm font-medium text-green-700">Published</p>}
    </form>
  </section></MainContainer>;
}
