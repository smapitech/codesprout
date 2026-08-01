import { OptimizedPicture } from '@/components/optimized-picture';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { type LandingWorldCardData } from '@/types/landing';
import { ArrowRight } from 'lucide-react';

interface LandingWorldCardProps {
    world: LandingWorldCardData;
}

export function LandingWorldCard({ world }: LandingWorldCardProps) {
    return (
        <Card
            className={cn(
                'group overflow-hidden rounded-[1.8rem] border border-white/80 bg-white/95 shadow-[0_12px_36px_rgba(36,52,67,0.08)] transition duration-300 focus-within:-translate-y-1 hover:-translate-y-1 hover:shadow-[0_18px_44px_rgba(36,52,67,0.12)]',
            )}
        >
            <div className="relative">
                <OptimizedPicture
                    asset={world.image}
                    className="w-full"
                    imgClassName="object-cover object-center transition duration-500 group-hover:scale-[1.02]"
                    loading="lazy"
                    sizes="(min-width: 1280px) 320px, (min-width: 768px) 50vw, 100vw"
                />

                <div
                    className="absolute top-4 left-4 inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-bold text-white shadow-[0_8px_18px_rgba(0,0,0,0.16)]"
                    style={{ backgroundColor: world.themeColour }}
                >
                    <span className="flex size-6 items-center justify-center rounded-full bg-white/20">{world.number}</span>
                    <span>Adventure {world.number}</span>
                </div>

                <div className="absolute inset-x-0 bottom-0 h-20 bg-[linear-gradient(180deg,_rgba(255,248,234,0)_0%,_rgba(255,248,234,0.85)_100%)]" />
            </div>

            <CardContent className="space-y-4 p-5">
                <div className="space-y-2">
                    <h3 className="font-[family-name:var(--font-display)] text-2xl font-black tracking-tight text-[var(--codesprout-ink)]">
                        {world.title}
                    </h3>
                    <p className="text-sm leading-7 text-[var(--codesprout-ink)]/78">{world.description}</p>
                </div>

                <ul className="flex flex-wrap gap-2" aria-label={`${world.title} learning skills`}>
                    {world.skills.map((skill) => (
                        <li
                            key={skill}
                            className="rounded-full border px-3 py-2 text-xs font-semibold text-[var(--codesprout-ink)]/88"
                            style={{ borderColor: world.accentColour, backgroundColor: `${world.accentColour}1A` }}
                        >
                            {skill}
                        </li>
                    ))}
                </ul>

                <div className="flex items-center justify-between gap-3">
                    <div className="h-2 flex-1 rounded-full bg-[rgba(36,52,67,0.08)]">
                        <div className="h-2 rounded-full" style={{ width: '100%', backgroundColor: world.themeColour }} />
                    </div>

                    <Button
                        asChild
                        className="h-11 rounded-full px-4 text-sm font-semibold text-white transition-transform hover:-translate-y-0.5"
                        style={{ backgroundColor: world.themeColour }}
                    >
                        <a href={world.href} aria-label={`Explore ${world.title}`}>
                            Explore World
                            <ArrowRight className="size-4" />
                        </a>
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
