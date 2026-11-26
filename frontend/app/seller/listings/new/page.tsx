import SellerListingForm from "@/components/seller/seller-listing-form";
import SellerAuthGuard from "@/components/auth/seller-auth-guard";

export default function NewSellerListingPage() { return <SellerAuthGuard><SellerListingForm /></SellerAuthGuard>; }
