"use client";

import { useEffect, useState, type ReactNode } from "react";
import { useRouter } from "next/navigation";
import MainContainer from "@/components/layout/main-container";
import { ApiError } from "@/lib/api/client";
import { getCurrentUser } from "@/lib/api/auth";

export default function SellerAuthGuard({ children }: { children: ReactNode }) {
  const router = useRouter();
  const [authorized, setAuthorized] = useState(false);
  const [checking, setChecking] = useState(true);
  const [error, setError] = useState("");
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    let active = true;
    getCurrentUser().then(() => {
      if (active) setAuthorized(true);
    }).catch((cause: unknown) => {
      if (!active) return;
      if (cause instanceof ApiError && cause.status === 401) {
        const destination = `${window.location.pathname}${window.location.search}`;
        router.replace(`/login?next=${encodeURIComponent(destination)}`);
        return;
      }
      setError(cause instanceof Error ? cause.message : "Unable to verify your sign-in.");
    }).finally(() => {
      if (active) setChecking(false);
    });
    return () => { active = false; };
  }, [attempt, router]);

  if (authorized) return children;
  if (checking) {
    return <MainContainer><p role="status" className="py-20 text-center text-gray-600">Checking your sign-in…</p></MainContainer>;
  }
  if (error) {
    return <MainContainer><div className="mx-auto max-w-xl py-20 text-center"><p role="alert" className="text-gray-700">{error}</p><button type="button" onClick={() => { setChecking(true); setError(""); setAttempt((value) => value + 1); }} className="mt-5 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">Try again</button></div></MainContainer>;
  }
  return <MainContainer><p role="status" className="py-20 text-center text-gray-600">Taking you to sign in…</p></MainContainer>;
}
