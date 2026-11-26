"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import MainContainer from "@/components/layout/main-container";
import { ApiError } from "@/lib/api/client";
import { getSellerListings } from "@/lib/api/seller-listings";
import type { Listing } from "@/lib/api/types";

export default function SellerListings() {
  const [listings, setListings] = useState<Listing[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [unauthorized, setUnauthorized] = useState(false);

  useEffect(() => {
    getSellerListings().then((result) => setListings(result.data)).catch((cause) => {
      if (cause instanceof ApiError && cause.status === 401) setUnauthorized(true);
      else setError(cause instanceof Error ? cause.message : "Unable to load your listings.");
    }).finally(() => setLoading(false));
  }, []);

  return <MainContainer><section className="py-10"><div className="mb-8 flex flex-wrap items-center justify-between gap-4"><div><h1 className="text-3xl font-bold text-gray-900">My listings</h1><p className="mt-2 text-gray-600">Create, edit, and publish your marketplace listings.</p></div><Link href="/seller/listings/new" className="rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">Create listing</Link></div>
    {loading ? <p role="status" className="py-12 text-center text-gray-500">Loading your listings…</p> : unauthorized ? <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center"><p className="text-amber-900">Sign in to view and manage your listings.</p><Link className="mt-3 inline-block font-semibold text-orange-700" href="/login?next=%2Fseller%2Flistings">Sign in</Link></div> : error ? <p role="alert" className="rounded-xl bg-red-50 p-6 text-red-800">{error}</p> : listings.length === 0 ? <div className="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center"><h2 className="text-lg font-semibold text-gray-900">No listings yet</h2><p className="mt-2 text-sm text-gray-500">Create a draft to get started.</p></div> : <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{listings.map((listing) => <Link key={listing.id} href={`/seller/listings/${listing.id}`} className="rounded-xl border border-gray-200 bg-white p-5 hover:border-orange-300"><div className="flex items-start justify-between gap-3"><h2 className="font-semibold text-gray-900">{listing.title}</h2><span className="rounded-full bg-gray-100 px-2.5 py-1 text-xs capitalize text-gray-600">{listing.status}</span></div><p className="mt-2 text-sm text-gray-500">{listing.category.name}{listing.municipality ? ` · ${listing.municipality}` : ""}</p></Link>)}</div>}
  </section></MainContainer>;
}
