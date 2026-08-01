import { cn } from '@/lib/utils';
import { type LandingPathWorld } from '@/types/landing';

interface LandingPathTimelineProps {
    worlds: LandingPathWorld[];
}

export function LandingPathTimeline({ worlds }: LandingPathTimelineProps) {
    return (
        <div className="relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/90 p-4 shadow-[0_12px_36px_rgba(36,52,67,0.08)] sm:p-5">
            <div className="pointer-events-none absolute inset-0 hidden opacity-70 xl:block" aria-hidden="true">
                <svg className="landing-draw h-full w-full" viewBox="0 0 1200 500" preserveAspectRatio="none">
                    <path
                        d="M70 135 C 190 40, 270 240, 390 145 S 600 55, 730 145 S 930 240, 1090 125"
                        fill="none"
                        stroke="rgba(19,138,114,0.24)"
                        strokeDasharray="10 14"
                        strokeLinecap="round"
                        strokeWidth="8"
                    />
                </svg>
            </div>

            <ol className="relative grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {worlds.map((world, index) => (
                    <li
                        key={world.slug}
                        className={cn(
                            'rounded-[1.6rem] border border-white/90 bg-[rgba(255,255,255,0.96)] p-4 shadow-[0_8px_24px_rgba(36,52,67,0.06)] transition duration-300 hover:-translate-y-1',
                            index % 2 === 1 && 'xl:translate-y-10',
                        )}
                    >
                        <div className="flex items-start gap-3">
                            <div
                                className="flex size-11 shrink-0 items-center justify-center rounded-2xl text-sm font-black text-white"
                                style={{ backgroundColor: world.themeColour }}
                            >
                                {world.number}
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-semibold tracking-[0.24em] text-[var(--codesprout-ink)]/58 uppercase">World</p>
                                <h3 className="mt-1 font-[family-name:var(--font-display)] text-lg font-black text-[var(--codesprout-ink)]">
                                    {world.title}
                                </h3>
                                {world.shortDescription ? (
                                    <p className="mt-2 text-sm leading-7 text-[var(--codesprout-ink)]/76">{world.shortDescription}</p>
                                ) : null}
                            </div>
                        </div>

                        <div className="mt-4 flex items-center gap-2">
                            <span className="h-2 w-2 rounded-full" style={{ backgroundColor: world.accentColour }} />
                            <span className="h-px flex-1 bg-[rgba(36,52,67,0.12)]" />
                        </div>
                    </li>
                ))}
            </ol>
        </div>
    );
}
