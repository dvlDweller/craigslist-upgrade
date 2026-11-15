
import Link from "next/link";

interface LogoProps {
  className?: string;
}

export default function Logo({ className = "" }: LogoProps) {
  return (
    <Link
      href="/"
      className={`inline-flex items-center text-2xl font-extrabold tracking-tight ${className}`}
      aria-label="MarketPlace home"
    >
      <span className="text-orange-600">Market</span>
      <span className="text-gray-900">Place</span>
      <span className="ml-1.5 h-2 w-2 rounded-full bg-orange-600" />
    </Link>
  );
}
