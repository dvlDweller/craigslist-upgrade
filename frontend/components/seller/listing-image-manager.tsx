"use client";

import Image from "next/image";
import { useRef, useState } from "react";
import { ApiError } from "@/lib/api/client";
import {
  deleteSellerListingImage,
  getSellerListing,
  reorderSellerListingImages,
  setSellerListingPrimaryImage,
  uploadSellerListingImages,
} from "@/lib/api/seller-listings";
import type { ListingDetail } from "@/lib/api/types";

interface ListingImageManagerProps {
  listing: ListingDetail;
  onChange: (listing: ListingDetail) => void;
}

export default function ListingImageManager({ listing, onChange }: ListingImageManagerProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const images = [...listing.images].sort((a, b) => a.sort_order - b.sort_order);

  function showError(cause: unknown) {
    if (cause instanceof ApiError && cause.errors) {
      setError(Object.values(cause.errors).flat().join(" "));
    } else {
      setError(cause instanceof Error ? cause.message : "Unable to update listing images.");
    }
  }

  async function upload(files: FileList | null) {
    if (!files?.length) return;
    const selected = Array.from(files);
    if (selected.length + listing.images.length > 8) {
      setError("A listing can have up to 8 images.");
      return;
    }
    const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
    const invalid = selected.find((file) => !allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024);
    if (invalid) {
      setError("Choose JPEG, PNG, or WebP images up to 5 MB each.");
      return;
    }
    setBusy(true);
    setError("");
    try {
      onChange(await uploadSellerListingImages(String(listing.id), selected));
    } catch (cause) {
      showError(cause);
    } finally {
      setBusy(false);
      if (inputRef.current) inputRef.current.value = "";
    }
  }

  async function makePrimary(imageId: number) {
    setBusy(true);
    setError("");
    try {
      onChange(await setSellerListingPrimaryImage(String(listing.id), imageId));
    } catch (cause) {
      showError(cause);
    } finally {
      setBusy(false);
    }
  }

  async function remove(imageId: number) {
    if (!window.confirm("Delete this image from the listing?")) return;
    setBusy(true);
    setError("");
    try {
      await deleteSellerListingImage(String(listing.id), imageId);
      onChange(await reloadListing());
    } catch (cause) {
      showError(cause);
    } finally {
      setBusy(false);
    }
  }

  async function move(imageId: number, offset: -1 | 1) {
    const currentIndex = images.findIndex((image) => image.id === imageId);
    const targetIndex = currentIndex + offset;
    if (targetIndex < 0 || targetIndex >= images.length) return;
    const reordered = [...images];
    [reordered[currentIndex], reordered[targetIndex]] = [reordered[targetIndex], reordered[currentIndex]];
    setBusy(true);
    setError("");
    try {
      onChange(await reorderSellerListingImages(String(listing.id), reordered.map((image) => image.id)));
    } catch (cause) {
      showError(cause);
    } finally {
      setBusy(false);
    }
  }

  async function reloadListing(): Promise<ListingDetail> {
    return getSellerListing(String(listing.id));
  }

  return (
    <section className="space-y-4 border-t border-gray-200 pt-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="font-semibold text-gray-900">Listing images</h2>
          <p className="text-xs text-gray-500">{listing.images.length} of 8 images · JPEG, PNG, or WebP up to 5 MB each</p>
        </div>
        <button type="button" disabled={busy || listing.images.length >= 8} onClick={() => inputRef.current?.click()} className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
          {busy ? "Updating…" : "Add images"}
        </button>
        <input ref={inputRef} type="file" accept="image/jpeg,image/png,image/webp" multiple className="hidden" onChange={(event) => void upload(event.target.files)} />
      </div>
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      {images.length === 0 ? <p className="rounded-lg bg-gray-50 p-4 text-sm text-gray-500">No images added yet.</p> : (
        <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          {images.map((image, index) => (
            <li key={image.id} className="flex gap-3 rounded-lg border border-gray-200 p-3">
              <div className="relative h-24 w-24 shrink-0 overflow-hidden rounded-md bg-gray-100">
                <Image src={image.url} alt={`${listing.title} image ${index + 1}`} fill unoptimized sizes="96px" className="object-cover" />
              </div>
              <div className="flex min-w-0 flex-1 flex-col items-start gap-2">
                {image.is_primary && <span className="rounded-full bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700">Primary</span>}
                <div className="flex flex-wrap gap-2">
                  {!image.is_primary && <button type="button" disabled={busy} onClick={() => void makePrimary(image.id)} className="text-xs font-medium text-orange-700 hover:underline">Set primary</button>}
                  <button type="button" disabled={busy || index === 0} onClick={() => void move(image.id, -1)} className="text-xs font-medium text-gray-600 hover:underline">Move up</button>
                  <button type="button" disabled={busy || index === images.length - 1} onClick={() => void move(image.id, 1)} className="text-xs font-medium text-gray-600 hover:underline">Move down</button>
                  <button type="button" disabled={busy} onClick={() => void remove(image.id)} className="text-xs font-medium text-red-700 hover:underline">Delete</button>
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
