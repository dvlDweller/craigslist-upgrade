
import Button from "@/components/ui/button";
import MainContainer from "@/components/layout/main-container";

export default function Home() {
  return (
    <MainContainer className="flex flex-1 flex-col justify-center">
      <section className="mx-auto flex w-full max-w-4xl flex-col items-center py-20 text-center sm:py-28">
        <span className="mb-6 rounded-full bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700">
          Your Local Marketplace
        </span>

        <h1 className="max-w-3xl text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
          Buy and sell with your{" "}
          <span className="text-orange-600">community.</span>
        </h1>

        <p className="mt-6 max-w-2xl text-base leading-8 text-gray-500 sm:text-lg">
          A simple place to discover local opportunities and connect with
          people in your community. Find what you need and make room for
          something new.
        </p>

        <div className="mt-10">
          <Button disabled>
            Marketplace Coming Soon
          </Button>
        </div>

        <p className="mt-4 text-sm text-gray-400">
         We are building something better for your local community.
        </p>
      </section>
    </MainContainer>
  );
}
