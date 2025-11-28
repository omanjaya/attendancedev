import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Card, CardContent } from "@/components/ui/card";

const statsData = [
  {
    logo: "https://deifkwefumgah.cloudfront.net/shadcnblocks/block/logos/company/fictional-company-logo-1.svg",
    avatar: "https://deifkwefumgah.cloudfront.net/shadcnblocks/block/avatar-1.webp",
    avatarFallback: "",
    heading: "89%",
    text: "Stop spending on ads with zero conversions",
  },
  {
    logo: "https://deifkwefumgah.cloudfront.net/shadcnblocks/block/logos/company/fictional-company-logo-2.svg",
    avatar: "https://deifkwefumgah.cloudfront.net/shadcnblocks/block/avatar-2.webp",
    avatarFallback: "",
    heading: "7 HRS",
    text: "Daily savings on ad management",
  },
  {
    logo: "https://deifkwefumgah.cloudfront.net/shadcnblocks/block/logos/company/fictional-company-logo-3.svg",
    avatar: "https://deifkwefumgah.cloudfront.net/shadcnblocks/block/avatar-3.webp",
    avatarFallback: "",
    heading: "2,540%",
    text: "Growth in overall client ad investment",
  },
];

const Stats10 = () => {
  return (
    <section className="py-12 md:py-20">
      <div className="container max-w-[75rem]">
        <div className="pb-10 pt-10">
          <div className="grid grid-cols-1 gap-7 md:grid-cols-2 lg:grid-cols-3">
            {statsData.map(
              ({ logo, avatar, avatarFallback, heading, text }, i) => (
                <a href="#" key={`link${i}`} className="block w-full">
                  <Card className="hover:border-primary rounded-3xl border-2 p-10 transition hover:-translate-y-3">
                    <CardContent className="block p-0">
                      <div className="flex items-center gap-7">
                        <Avatar className="h-14 w-14 overflow-hidden rounded-full border">
                          <AvatarImage src={avatar} alt="" />
                          <AvatarFallback>{avatarFallback}</AvatarFallback>
                        </Avatar>
                        <img src={logo} alt="" className="h-6" />
                      </div>
                      <div className="mt-6 text-6xl font-semibold leading-tight">
                        {heading}
                      </div>
                      <p className="mb-5 max-w-52 text-lg font-medium">
                        {text}
                      </p>
                    </CardContent>
                  </Card>
                </a>
              ),
            )}
          </div>
        </div>
      </div>
    </section>
  );
};

export { Stats10 };
