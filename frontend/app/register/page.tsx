import MainContainer from "@/components/layout/main-container";
import AuthForm from "@/components/auth/auth-form";

export default function RegisterPage() {
  return <MainContainer><section className="mx-auto max-w-md py-12"><h1 className="mb-2 text-3xl font-bold text-gray-900">Create an account</h1><p className="mb-6 text-gray-600">Join your local marketplace.</p><AuthForm mode="register" /></section></MainContainer>;
}
