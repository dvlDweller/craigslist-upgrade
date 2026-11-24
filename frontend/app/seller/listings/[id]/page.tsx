import SellerListingForm from "@/components/seller/seller-listing-form";
import SellerAuthGuard from "@/components/auth/seller-auth-guard";

export default async function EditSellerListingPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  return <SellerAuthGuard><SellerListingForm id={id} /></SellerAuthGuard>;
}
