"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import Logo from "@/components/ui/logo";
import { getCurrentUser, logout, type User } from "@/lib/api/auth";

export default function SiteHeader() {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [checking, setChecking] = useState(true);

  useEffect(() => {
    let active = true;
    const refreshSession = () => {
      getCurrentUser().then((currentUser) => { if (active) setUser(currentUser); })
      .catch(() => { if (active) setUser(null); })
      .finally(() => { if (active) setChecking(false); });
    };
    refreshSession();
    window.addEventListener("marketplace-auth-changed", refreshSession);
    return () => { active = false; window.removeEventListener("marketplace-auth-changed", refreshSession); };
  }, []);

  async function handleLogout() {
    try {
      await logout();
      setUser(null);
      window.dispatchEvent(new Event("marketplace-auth-changed"));
      router.replace("/");
      router.refresh();
    } catch {
      setUser(null);
    }
  }

  return (
    <header className="sticky top-0 z-50 border-b border-gray-200 bg-white">
      <div className="mx-auto flex w-full max-w-7xl items-center justify-between gap-2 px-3 py-3 sm:px-6 sm:py-4">
        <Logo className="shrink-0 text-xl sm:text-2xl" />
        <nav className="hidden items-center gap-8 text-sm font-medium text-gray-600 md:flex">
          <Link href="/" className="transition hover:text-orange-600">Home</Link>
          <Link href="/listings" className="transition hover:text-orange-600">Browse Listings</Link>
        </nav>
        <div className="flex shrink-0 flex-nowrap items-center gap-2 sm:gap-3">
          {user ? (
            <>
              <Link href="/seller/listings" className="whitespace-nowrap text-xs font-medium text-gray-700 hover:text-orange-600 sm:text-sm">My listings</Link>
              <Link href="/seller/listings/new" className="whitespace-nowrap rounded-lg bg-orange-600 px-3 py-2 text-xs font-semibold text-white hover:bg-orange-700 sm:px-4 sm:text-sm">Create Listing</Link>
              <span className="hidden text-sm text-gray-500 lg:inline">{user.name}</span>
              <button onClick={handleLogout} className="whitespace-nowrap text-xs font-medium text-gray-600 hover:text-orange-600 sm:text-sm">Sign out</button>
            </>
          ) : checking ? (
            <span className="text-sm text-gray-400">Checking session…</span>
          ) : (
            <>
              <Link href="/login" className="whitespace-nowrap text-xs font-medium text-gray-600 hover:text-orange-600 sm:text-sm">Sign In</Link>
              <Link href="/register" className="whitespace-nowrap text-xs font-medium text-gray-600 hover:text-orange-600 sm:text-sm">Register</Link>
            </>
          )}
        </div>
      </div>
    </header>
  );
}
