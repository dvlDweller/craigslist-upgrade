"use client";

export default function ListingsError({ reset }: { error: Error & { digest?: string }; reset: () => void }) {
  return <main className="mx-auto w-full max-w-6xl px-4 py-20 text-center sm:px-6 lg:px-8"><h1 className="text-2xl font-bold text-gray-900">Listings are temporarily unavailable</h1><p className="mt-3 text-gray-600">Please try again in a moment.</p><button onClick={reset} className="mt-6 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">Try again</button></main>;
}
