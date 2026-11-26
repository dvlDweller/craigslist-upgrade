import MainContainer from "@/components/layout/main-container";
import AuthForm from "@/components/auth/auth-form";

export default function LoginPage() {
  return <MainContainer><section className="mx-auto max-w-md py-12"><h1 className="mb-2 text-3xl font-bold text-gray-900">Sign in</h1><p className="mb-6 text-gray-600">Sign in to manage your listings.</p><AuthForm mode="login" /></section></MainContainer>;
}
