import { apiFetch, getApiOrigin } from "@/lib/api/client";
import type { ApiResponse } from "@/lib/api/types";

export interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
  updated_at: string;
}

let csrfInitialization: Promise<void> | null = null;

async function initializeCsrfCookie(): Promise<void> {
  if (!csrfInitialization) {
    csrfInitialization = fetch(`${getApiOrigin()}/sanctum/csrf-cookie`, {
      method: "GET",
      credentials: "include",
      headers: { Accept: "application/json" },
      signal: AbortSignal.timeout(10_000),
    }).then((response) => {
      if (!response.ok) throw new Error("Unable to initialize the secure session.");
    }).catch((error) => {
      csrfInitialization = null;
      throw error;
    });
  }
  await csrfInitialization;
}

function getXsrfToken(): string {
  const cookie = document.cookie.split("; ").find((item) => item.startsWith("XSRF-TOKEN="));
  return cookie ? decodeURIComponent(cookie.slice("XSRF-TOKEN=".length)) : "";
}

export async function csrfRequestHeaders(): Promise<Record<string, string>> {
  await initializeCsrfCookie();
  const token = getXsrfToken();
  if (!token) throw new Error("The secure session cookie was not set. Check the API cookie and CORS configuration.");
  return { "X-XSRF-TOKEN": token };
}

export async function getCurrentUser(): Promise<User> {
  const response = await apiFetch<ApiResponse<User>>("auth/user", { credentials: "include" });
  return response.data;
}

export async function login(email: string, password: string): Promise<User> {
  const csrf = await csrfRequestHeaders();
  const response = await apiFetch<ApiResponse<User>>("auth/login", {
    method: "POST", credentials: "include",
    headers: { "Content-Type": "application/json", ...csrf },
    body: JSON.stringify({ email, password }),
  });
  return response.data;
}

export async function register(name: string, email: string, password: string, password_confirmation: string): Promise<User> {
  const csrf = await csrfRequestHeaders();
  const response = await apiFetch<ApiResponse<User>>("auth/register", {
    method: "POST", credentials: "include",
    headers: { "Content-Type": "application/json", ...csrf },
    body: JSON.stringify({ name, email, password, password_confirmation }),
  });
  return response.data;
}

export async function logout(): Promise<void> {
  const csrf = await csrfRequestHeaders();
  await apiFetch<{ message: string }>("auth/logout", {
    method: "POST", credentials: "include", headers: csrf,
  });
  csrfInitialization = null;
}
