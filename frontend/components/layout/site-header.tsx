
import Link from "next/link";
import Logo from "@/components/ui/logo";

export default function SiteHeader() {
  return (
    <header className="sticky top-0 z-50 border-b border-gray-200 bg-white">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <Logo />

        <nav className="hidden items-center gap-8 text-sm font-medium text-gray-600 md:flex">
          <Link href="/" className="transition hover:text-orange-600">
            Home
          </Link>

          <Link
            href="/listings"
            className="transition hover:text-orange-600"
          >
            Browse Listings
          </Link>
        </nav>

        <div className="flex items-center gap-3">
          <span className="cursor-not-allowed text-sm font-medium text-gray-400">
            Sign In
          </span>

          <span className="cursor-not-allowed text-sm font-medium text-gray-400">
            Register
          </span>

          <button
            type="button"
            disabled
            className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white opacity-50"
          >
            Sell Listing
          </button>
        </div>
      </div>
    </header>
  );
}
