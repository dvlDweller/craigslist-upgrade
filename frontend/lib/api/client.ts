
const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

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

export async function apiFetch<T>(
  path: string,
  init?: RequestInit,
): Promise<T> {
  const baseUrl = API_BASE_URL.replace(/\/$/, "");
  const cleanPath = path.replace(/^\//, "");

  const response = await fetch(`${baseUrl}/${cleanPath}`, {
    ...init,
    headers: {
      Accept: "application/json",
      ...init?.headers,
    },
  });

  const payload: unknown = await response.json().catch(() => null);

  if (!response.ok) {
    const errorPayload = payload as {
      message?: string;
      errors?: Record<string, string[]>;
    } | null;

    throw new ApiError(
      errorPayload?.message ?? "Something went wrong while contacting the server.",
      response.status,
      errorPayload?.errors,
    );
  }

  return payload as T;
}
