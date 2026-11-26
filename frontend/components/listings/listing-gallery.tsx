
import Image from "next/image";
import type { ListingImage } from "@/lib/api/types";

interface ListingGalleryProps {
  images: ListingImage[];
  title: string;
}

export default function ListingGallery({
  images,
  title,
}: ListingGalleryProps) {
  if (images.length === 0) {
    return (
      <div className="flex aspect-[4/3] items-center justify-center rounded-xl bg-gray-100 text-gray-400">
        No photos available
      </div>
    );
  }

  const sortedImages = [...images].sort(
    (a, b) =>
      Number(b.is_primary) - Number(a.is_primary) ||
      a.sort_order - b.sort_order,
  );

  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      {sortedImages.map((image, index) => (
        <div
          key={image.id}
          className={`overflow-hidden rounded-xl bg-gray-100 ${
            index === 0 ? "col-span-2 row-span-2" : ""
          }`}
        >
          <Image
            src={image.url}
            alt={`${title} - photo ${index + 1}`}
            width={1200}
            height={900}
            unoptimized
            className="h-full min-h-32 w-full object-cover"
            loading={index === 0 ? "eager" : "lazy"}
          />
        </div>
      ))}
    </div>
  );
}
