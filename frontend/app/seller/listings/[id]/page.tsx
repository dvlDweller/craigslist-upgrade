import SellerListingForm from "@/components/seller/seller-listing-form";

export default async function EditSellerListingPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <SellerListingForm id={id} />;
}
