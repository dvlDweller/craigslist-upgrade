
import Link from "next/link";
import type { Category } from "@/lib/api/types";

interface CategoryBrowserProps {
  categories: Category[];
  selectedCategory?: string;
  queryString?: string;
}

export default function CategoryBrowser({
  categories,
  selectedCategory,
  queryString = "",
}: CategoryBrowserProps) {
  function getCategoryHref(slug?: string) {
    const params = new URLSearchParams(queryString);

    params.delete("page");
    params.delete("category");

    if (slug) {
      params.set("category", slug);
    }

    const query = params.toString();

    return `/listings${query ? `?${query}` : ""}`;
  }

  return (
    <nav aria-label="Browse categories" className="space-y-3">
      <h2 className="text-lg font-bold text-gray-900">Categories</h2>

      <Link
        href={getCategoryHref()}
        className={`block rounded-lg px-3 py-2 text-sm font-medium ${
          !selectedCategory
            ? "bg-orange-50 text-orange-700"
            : "text-gray-600 hover:bg-gray-50"
        }`}
      >
        All Categories
      </Link>

      {categories.map((category) => (
        <div key={category.id} className="space-y-1">
          <Link
            href={getCategoryHref(category.slug)}
            className={`block rounded-lg px-3 py-2 text-sm font-semibold ${
              selectedCategory === category.slug
                ? "bg-orange-50 text-orange-700"
                : "text-gray-700 hover:bg-gray-50"
            }`}
          >
            {category.name}
          </Link>

          {category.children.length > 0 && (
            <div className="ml-4 space-y-1 border-l border-gray-200 pl-3">
              {category.children.map((child) => (
                <Link
                  key={child.id}
                  href={getCategoryHref(child.slug)}
                  className={`block rounded-lg px-3 py-2 text-sm ${
                    selectedCategory === child.slug
                      ? "bg-orange-50 text-orange-700"
                      : "text-gray-500 hover:bg-gray-50"
                  }`}
                >
                  {child.name}
                </Link>
              ))}
            </div>
          )}
        </div>
      ))}
    </nav>
  );
}
