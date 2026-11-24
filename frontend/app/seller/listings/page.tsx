import SellerListings from "@/components/seller/seller-listings";
import SellerAuthGuard from "@/components/auth/seller-auth-guard";

export default function SellerListingsPage() { return <SellerAuthGuard><SellerListings /></SellerAuthGuard>; }
