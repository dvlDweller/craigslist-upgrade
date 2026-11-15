
import Logo from "@/components/ui/logo";

export default function SiteFooter() {
  return (
    <footer className="border-t border-gray-200 bg-white">
      <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-8 sm:flex-row">
        <Logo className="text-lg" />

        <p className="text-sm text-gray-500">
          &copy; 2026 MarketPlace. All rights reserved.
        </p>

        <div className="flex gap-5 text-sm text-gray-500">
          <span>About</span>
          <span>Contact</span>
          <span>Privacy</span>
        </div>
      </div>
    </footer>
  );
}
