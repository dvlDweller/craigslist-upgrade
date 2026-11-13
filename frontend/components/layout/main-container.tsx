
import type { ReactNode } from "react";

interface MainContainerProps {
  children: ReactNode;
  className?: string;
}

export default function MainContainer({
  children,
  className = "",
}: MainContainerProps) {
  return (
    <main className={`mx-auto w-full max-w-7xl px-6 py-8 ${className}`}>
      {children}
    </main>
  );
}
