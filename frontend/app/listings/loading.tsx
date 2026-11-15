
import MainContainer from "@/components/layout/main-container";

export default function ListingsLoading() {
  return (
    <MainContainer>
      <div className="mb-8 animate-pulse">
        <div className="h-9 w-64 rounded-lg bg-gray-200" />
        <div className="mt-3 h-5 w-96 max-w-full rounded bg-gray-100" />
      </div>

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-4">
        <aside className="space-y-4">
          <div className="h-8 w-40 rounded bg-gray-200" />
          <div className="h-40 rounded-xl bg-gray-100" />
          <div className="h-64 rounded-xl bg-gray-100" />
        </aside>

        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:col-span-3 xl:grid-cols-3">
          {Array.from({ length: 6 }).map((_, index) => (
            <div key={index} className="animate-pulse">
              <div className="aspect-[4/3] rounded-xl bg-gray-200" />
              <div className="mt-4 h-5 w-2/3 rounded bg-gray-200" />
              <div className="mt-3 h-4 w-1/2 rounded bg-gray-100" />
            </div>
          ))}
        </div>
      </div>
    </MainContainer>
  );
}
