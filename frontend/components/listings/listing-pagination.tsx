
import Link from "next/link";

interface ListingPaginationProps {
  currentPage: number;
  lastPage: number;
  queryString?: string;
}

export default function ListingPagination({
  currentPage,
  lastPage,
  queryString = "",
}: ListingPaginationProps) {
  if (lastPage <= 1) return null;

  function pageHref(page: number) {
    const params = new URLSearchParams(queryString);
    params.set("page", String(page));
    return `/listings?${params.toString()}`;
  }

  return (
    <nav
      aria-label="Listing pagination"
      className="flex items-center justify-center gap-4"
    >
      {currentPage > 1 ? (
        <Link
          href={pageHref(currentPage - 1)}
          className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Previous
        </Link>
      ) : (
        <span className="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-400">
          Previous
        </span>
      )}

      <span className="text-sm text-gray-600">
        Page {currentPage} of {lastPage}
      </span>

      {currentPage < lastPage ? (
        <Link
          href={pageHref(currentPage + 1)}
          className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Next
        </Link>
      ) : (
        <span className="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-400">
          Next
        </span>
      )}
    </nav>
  );
}
