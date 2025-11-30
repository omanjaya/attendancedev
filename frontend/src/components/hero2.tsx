import { ArrowDownRight } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

const Hero2 = () => {
  return (
    <section className="py-32">
      <div className="container">
        <div className="grid items-center gap-10 lg:grid-cols-2 lg:gap-20">
          <div className="bg-muted flex justify-end">
            <img
              src="https://deifkwefumgah.cloudfront.net/shadcnblocks/block/placeholder-1.svg"
              alt="placeholder hero"
              className="max-h-[600px] w-full rounded-md object-cover lg:max-h-[800px]"
            />
          </div>
          <div className="flex flex-col items-center text-center lg:max-w-3xl lg:items-start lg:text-left">
            <Badge variant="secondary">
              New Release
              <ArrowDownRight className="ml-2 size-4" />
            </Badge>
            <h1 className="my-6 text-pretty text-4xl font-bold md:text-5xl">
              Blocks built with Shadcn & Tailwind
            </h1>
            <p className="text-muted-foreground mb-8 max-w-xl lg:text-xl">
              Lorem ipsum dolor sit amet consectetur adipisicing elit. Elig
              doloremque mollitia fugiat omnis! Porro facilis quo animi
              consequatur. Explicabo.
            </p>
            <div className="flex w-full flex-col justify-center gap-2 sm:flex-row lg:justify-start">
              <Button className="w-full sm:w-auto">Primary Button</Button>
              <Button variant="outline" className="w-full sm:w-auto">
                Secondary Button
                <ArrowDownRight className="size-4" />
              </Button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export { Hero2 };
