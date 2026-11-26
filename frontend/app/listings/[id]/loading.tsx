
import MainContainer from "@/components/layout/main-container";

export default function ListingDetailLoading() {
  return (
    <MainContainer>
      <div className="mb-6 h-5 w-36 animate-pulse rounded bg-gray-200" />

      <div className="grid grid-cols-1 gap-10 lg:grid-cols-2">
        <div className="aspect-[4/3] animate-pulse rounded-xl bg-gray-200" />

        <div className="space-y-6">
          <div className="h-5 w-32 animate-pulse rounded bg-gray-200" />
          <div className="h-10 w-3/4 animate-pulse rounded bg-gray-200" />
          <div className="h-8 w-40 animate-pulse rounded bg-gray-100" />
          <div className="space-y-3 border-t border-gray-200 pt-6">
            <div className="h-6 w-36 animate-pulse rounded bg-gray-200" />
            <div className="h-4 w-full animate-pulse rounded bg-gray-100" />
            <div className="h-4 w-5/6 animate-pulse rounded bg-gray-100" />
            <div className="h-4 w-2/3 animate-pulse rounded bg-gray-100" />
          </div>
          <div className="h-24 animate-pulse rounded-xl bg-gray-100" />
        </div>
      </div>
    </MainContainer>
  );
}
