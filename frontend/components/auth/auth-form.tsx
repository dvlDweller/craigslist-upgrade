"use client";

import Link from "next/link";
import { useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import { ApiError } from "@/lib/api/client";
import { login, register } from "@/lib/api/auth";

export default function AuthForm({ mode }: { mode: "login" | "register" }) {
  const router = useRouter();
  const isRegister = mode === "register";
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError("");
    const data = new FormData(event.currentTarget);
    const name = String(data.get("name") ?? "");
    const email = String(data.get("email") ?? "");
    const password = String(data.get("password") ?? "");
    const confirmation = String(data.get("password_confirmation") ?? "");
    try {
      if (isRegister) await register(name, email, password, confirmation);
      else await login(email, password);
      window.dispatchEvent(new Event("marketplace-auth-changed"));
      const requested = new URLSearchParams(window.location.search).get("next");
      router.replace(requested?.startsWith("/") && !requested.startsWith("//") ? requested : "/seller/listings");
      router.refresh();
    } catch (cause) {
      if (cause instanceof ApiError && cause.errors) {
        setError(Object.values(cause.errors).flat().join(" "));
      } else {
        setError(cause instanceof Error ? cause.message : "Unable to complete your request. Please try again.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <form onSubmit={submit} className="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
      {isRegister && <div><label htmlFor="name" className="mb-1 block text-sm font-medium text-gray-700">Name</label><input id="name" name="name" required maxLength={255} autoComplete="name" className="w-full rounded-lg border border-gray-300 px-3 py-2.5" /></div>}
      <div><label htmlFor="email" className="mb-1 block text-sm font-medium text-gray-700">Email</label><input id="email" name="email" type="email" required autoComplete="email" className="w-full rounded-lg border border-gray-300 px-3 py-2.5" /></div>
      <div><label htmlFor="password" className="mb-1 block text-sm font-medium text-gray-700">Password</label><input id="password" name="password" type="password" required minLength={isRegister ? 8 : undefined} autoComplete={isRegister ? "new-password" : "current-password"} className="w-full rounded-lg border border-gray-300 px-3 py-2.5" /></div>
      {isRegister && <div><label htmlFor="password_confirmation" className="mb-1 block text-sm font-medium text-gray-700">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required minLength={8} autoComplete="new-password" className="w-full rounded-lg border border-gray-300 px-3 py-2.5" /></div>}
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      <button disabled={busy} className="w-full rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-60">{busy ? "Please wait…" : isRegister ? "Create account" : "Sign in"}</button>
      <p className="text-center text-sm text-gray-600">{isRegister ? <>Already have an account? <Link href="/login" className="font-semibold text-orange-600">Sign in</Link></> : <>New here? <Link href="/register" className="font-semibold text-orange-600">Create an account</Link></>}</p>
    </form>
  );
}
