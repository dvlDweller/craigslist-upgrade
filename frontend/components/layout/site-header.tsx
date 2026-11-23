"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import Logo from "@/components/ui/logo";
import { getCurrentUser, logout, type User } from "@/lib/api/auth";

export default function SiteHeader() {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [checking, setChecking] = useState(true);

  useEffect(() => {
    let active = true;
    const refreshSession = () => {
      getCurrentUser().then((currentUser) => { if (active) setUser(currentUser); })
      .catch(() => { if (active) setUser(null); })
      .finally(() => { if (active) setChecking(false); });
    };
    refreshSession();
    window.addEventListener("marketplace-auth-changed", refreshSession);
    return () => { active = false; window.removeEventListener("marketplace-auth-changed", refreshSession); };
  }, []);

  async function handleLogout() {
    try {
      await logout();
      setUser(null);
      window.dispatchEvent(new Event("marketplace-auth-changed"));
      router.replace("/");
      router.refresh();
    } catch {
      setUser(null);
    }
  }

  return (
    <header className="sticky top-0 z-50 border-b border-gray-200 bg-white">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <Logo />
        <nav className="hidden items-center gap-8 text-sm font-medium text-gray-600 md:flex">
          <Link href="/" className="transition hover:text-orange-600">Home</Link>
          <Link href="/listings" className="transition hover:text-orange-600">Browse Listings</Link>
        </nav>
        <div className="flex items-center gap-3">
          {user ? (
            <>
              <Link href="/seller/listings" className="text-sm font-medium text-gray-700 hover:text-orange-600">My listings</Link>
              <span className="hidden text-sm text-gray-500 sm:inline">{user.name}</span>
              <button onClick={handleLogout} className="text-sm font-medium text-gray-600 hover:text-orange-600">Sign out</button>
           #mùŞÚ$z{-®éÜj×–6P¢ÂöÆ&VÃà¢Æ–çW@¢–CÒ&Ö–â×&–6R ¢G—SÒ&çVÖ&W" ¢æÖSÒ&Ö–å÷&–6R ¢Ö–ãÒ# ¢7FWÒ#ã ¢FVfVÇEfÇVS×¶Ö–å÷&–6WĞ¢Æ6V†öÆFW#Ò.(+Ö–â ¢6Æ74æÖSÒ'rÖgVÆÂ&÷VæFVBÖÆr&÷&FW"&÷&FW"Öw&’Ó3‚Ó2’Ó"FW‡B×6Ò÷WFÆ–æRÖæöæRfö7W3¦&÷&FW"Ö÷&ævRÓS ¢óà¢ÂöF—cà ¢ÆF—cà¢ÆÆ&VÀ¢‡FÖÄf÷#Ò&Ö‚×&–6R ¢6Æ74æÖSÒ&Ö"Ó&Æö6²FW‡B×6ÒföçBÖÖVF—VÒFW‡BÖw&’Ós ¢à¢Ö†–×VÒ&–6P¢ÂöÆ&VÃà¢Æ–çW@¢–CÒ&Ö‚×&–6R ¢G—SÒ&çVÖ&W" ¢æÖSÒ&Ö…÷&–6R ¢Ö–ãÒ# ¢7FWÒ#ã ¢FVfVÇEfÇVS×¶Ö…÷&–6WĞ¢Æ6V†öÆFW#Ò.(+Ö‚ ¢6Æ74æÖSÒ'rÖgVÆÂ&÷VæFVBÖÆr&÷&FW"&÷&FW"Öw&’Ó3‚Ó2’Ó"FW‡B×6Ò÷WFÆ–æRÖæöæRfö7W3¦&÷&FW"Ö÷&ævRÓS ¢óà¢ÂöF—cà¢ÂöF—cà ¢ÆF—cà¢ÆÆ&VÀ¢‡FÖÄf÷#Ò'&–6R×G—R ¢6Æ74æÖSÒ&Ö"Ó&Æö6²FW‡B×6ÒföçBÖÖVF—VÒFW‡BÖw&’Ós ¢à¢&–6RG—P¢ÂöÆ&VÃà¢Ç6VÆV7@¢–CÒ'&–6R×G—R ¢æÖSÒ'&–6U÷G—R ¢FVfVÇEfÇVS×·&–6U÷G—Róò"'Ğ¢6Æ74æÖSÒ'rÖgVÆÂ&÷VæFVBÖÆr&÷&FW"&÷&FW"Öw&’Ó3&r×v†—FR‚Ó2’Ó"FW‡B×6Ò ¢à¢Æ÷F–öâfÇVSÒ"#äÆÂ&–6RG—W3Âö÷F–öãà¢Æ÷F–öâfÇVSÒ&f—†VB#äf—†VB&–6SÂö÷F–öãà¢Æ÷F–öâfÇVSÒ&g&VR#äg&VSÂö÷F–öãà¢Æ÷F–öâfÇVSÒ&6öçF7B#ä6öçF7Bf÷"&–6SÂö÷F–öãà¢Â÷6VÆV7Cà¢ÂöF—cà ¢ÆF—cà¢ÆÆ&VÀ¢‡FÖÄf÷#Ò&Æ—7F–ær×6÷'B ¢6Æ74æÖSÒ&Ö"Ó&Æö6²FW‡B×6ÒföçBÖÖVF—VÒFW‡BÖw&’Ós ¢à¢6÷'B'¢ÂöÆ&VÃà¢Ç6VÆV7@¢–CÒ&Æ—7F–ær×6÷'B ¢æÖSÒ'6÷'B ¢FVfVÇEfÇVS×·6÷'GĞ¢6Æ74æÖSÒ'rÖgVÆÂ&÷VæFVBÖÆr&÷&FW"&÷&FW"Öw&’Ó3&r×v†—FR‚Ó2’Ó"FW‡B×6Ò ¢à¢Æ÷F–öâfÇVSÒ&æWvW7B#äæWvW7Bf—'7CÂö÷F–öãà¢Æ÷F–öâfÇVSÒ'&–6Uö62#å&–6S¢Æ÷rFò†–vƒÂö÷F–öãà¢Æ÷F–öâfÇVSÒ'&–6UöFW62#å&–6S¢†–v‚FòÆ÷sÂö÷F–öãà¢Â÷6VÆV7Cà¢ÂöF—cà ¢Æ'WGFöà¢G—SÒ'7V&Ö—B ¢6Æ74æÖSÒ'rÖgVÆÂ&÷VæFVBÖÆr&rÖ÷&ævRÓc‚ÓB’Ó"ãRFW‡B×6ÒföçB×6VÖ–&öÆBFW‡B×v†—FRG&ç6—F–öâ†÷fW#¦&rÖ÷&ævRÓs ¢à¢Ç’f–ÇFW'0¢Âö'WGFöãà¢Âöf÷&Óà¢“°§Ğ