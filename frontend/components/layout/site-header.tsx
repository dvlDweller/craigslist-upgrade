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
      <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <Logo />
        <nav className="hidden items-center gap-8 text-sm font-medium text-gray-600 md:flex">
          <Link href="/" className="transition hover:text-orange-600">Home</Link>
          <Link href="/listings" className="transition hover:text-orange-600">Browse Listings</Link>
        </nav>
        <div className="flex items-center gap-3">
          {user ? (
            <>
              <Link href="/seller/listings" className="text-sm font-medium text-gray-700 hover:text-orange-600">My listings</Link>
              <span className="hidden text-sm text-gray-500 sm:inline">{user.name}</span>
              <button onClick={handleLogout} className="text-sm font-medium text-gray-600 hover:text-orange-600">Sign out</button>
            </>
          ) : checking ? (
            <span className="text-sm text-gray-400">Checking session…</span>
          ) : (
            <>
              <Link href="/login" className="text-sm font-medium text-gray-600 hover:text-orange-600">Sign In</Link>
              <Link href="/register" className="text-sm font-medium text-gray-600 hover:text-orange-600">Register</Link>
              <Link href="/login?next=%2Fseller%2Flistings%2Fnew" className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Sell Listing</Link>
            </>
          )}
        </div>
      </div>
    </header>
  );
}
