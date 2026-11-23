import MainContainer from "@/components/layout/main-container";

export default function Loading() {
  return <MainContainer><div role="status" aria-live="polite" className="py-24 text-center text-gray-600">Loading marketplace…</div></MainContainer>;
}
