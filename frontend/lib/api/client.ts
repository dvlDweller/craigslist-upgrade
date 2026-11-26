
const defaultApiBaseUrl =
  process.env.NODE_ENV === "development"
    ? "http://localhost:8000/api/v1"
    : "/api/v1";

export const API_BASE_URL = (
  process.env.NEXT_PUBLIC_API_URL || defaultApiBaseUrl
).replace(/\/$/, "");

export function getApiOrigin(): string {
  if (API_BASE_URL.startsWith("/")) {
    return typeof window === "undefined" ? "" : window.location.origin;
  }

  return new URL(API_BASE_URL).origin;
}

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

export async function apiFetchWithBase<T>(
  baseUrl: string,
  path: string,
  init?: RequestInit,
): Promise<T> {
  const normalizedBaseUrl = baseUrl.replace(/\/$/, "");
  const cleanPath = path.replace(/^\//, "");

  let response: Response;

  try {
    response = await fetch(`${normalizedBaseUrl}/${cleanPath}`, {
      ...init,
      signal: init?.signal ?? AbortSignal.timeout(10_000),
      credentials: init?.credentials,
      headers: {
        Accept: "application/json",
        ...init?.headers,
      },
    });
  } catch {
    throw new ApiError(
      "Unable to connect to the marketplace API. Please try again later.",
      0,
    );
  }

  const payload: unknown = await response.json().catch(() => null);

  if (!response.ok) {
    const errorPayload = payload as {
      message?: string;
      errors?: Record<string, string[]>;
    } | null;

    throw new ApiError(
      errorPayload?.message ??
        "Something went wrong while contacting the server.",
      response.status,
      errorPayload?.errors,
    );
  }

  return payload as T;
}

export async function apiFetch<T>(
  path: string,
  init?: RequestInit,
): Promise<T> {
  return apiFetchWithBase(API_BASE_URL, path, init);
}
