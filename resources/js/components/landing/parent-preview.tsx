import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { BadgeCheck, BookOpen, Clock3, MessageSquareQuote, MousePointer2, Star, UsersRound } from 'lucide-react';

const previewMetrics = [
    { label: 'Completed learning worlds', value: '7 of 12', tone: 'leaf', icon: BookOpen },
    { label: 'Typing accuracy', value: '84%', tone: 'sky', icon: MousePointer2 },
    { label: 'Skills being practised', value: 'Letters, spacing and symbols', tone: 'sun', icon: BadgeCheck },
    { label: 'Assignments', value: '1 active', tone: 'coral', icon: MessageSquareQuote },
    { label: 'Teacher feedback', value: 'Encouraging note ready', tone: 'lavender', icon: MessageSquareQuote },
    { label: 'Projects', value: 'Name card preview', tone: 'teal', icon: Star },
    { label: 'Time spent learning', value: '24 min this week', tone: 'sky', icon: Clock3 },
    { label: 'Achievements', value: '4 stars earned', tone: 'sun', icon: UsersRound },
] as const;

const toneStyles = {
    leaf: 'bg-[rgba(19,138,114,0.12)] text-[var(--codesprout-leaf)]',
    sky: 'bg-[rgba(84,183,211,0.15)] text-[#1f8fb0]',
    sun: 'bg-[rgba(247,201,72,0.18)] text-[#9b7300]',
    coral: 'bg-[rgba(255,107,94,0.14)] text-[#d94f45]',
    lavender: 'bg-[rgba(142,124,195,0.14)] text-[#6f5ba9]',
    teal: 'bg-[rgba(19,138,114,0.08)] text-[var(--codesprout-leaf)]',
} as const;

export function LandingParentPreview() {
    return (
        <Card className="overflow-hidden rounded-[2rem] border border-white/80 bg-white/95 shadow-[0_12px_36px_rgba(36,52,67,0.08)]">
            <CardHeader className="space-y-3 border-b border-[rgba(36,52,67,0.08)] bg-[linear-gradient(180deg,_rgba(84,183,211,0.08)_0%,_rgba(255,255,255,0.96)_100%)] px-6 py-6 sm:px-7">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="space-y-2">
                        <p className="text-sm font-semibold tracking-[0.24em] text-[var(--codesprout-leaf)] uppercase">
                            Parents see the progress, too
                        </p>
                        <h3 className="font-[family-name:var(--font-display)] text-2xl font-black tracking-tight text-[var(--codesprout-ink)]">
                            Example progress preview
                        </h3>
                    </div>
                    <span className="rounded-full bg-[rgba(19,138,114,0.12)] px-4 py-2 text-sm font-semibold text-[var(--codesprout-leaf)]">
                        Preview only
                    </span>
                </div>

                <p className="max-w-2xl text-sm leading-7 text-[var(--codesprout-ink)]/78">
                    This sample dashboard shows the kind of information families can follow without exposing real child data on the public homepage.
                </p>
            </CardHeader>

            <CardContent className="grid gap-5 p-6 sm:p-7 lg:grid-cols-[280px_minmax(0,1fr)]">
                <div className="rounded-[1.75rem] border border-[rgba(36,52,67,0.08)] bg-[rgba(255,248,234,0.62)] p-5">
                    <div className="mx-auto flex h-36 w-36 items-center justify-center rounded-full border-[10px] border-white bg-[conic-gradient(from_180deg_at_50%_50%,_rgba(19,138,114,1)_0deg,_rgba(19,138,114,1)_245deg,_rgba(36,52,67,0.08)_245deg,_rgba(36,52,67,0.08)_360deg)] shadow-[inset_0_0_0_8px_rgba(255,255,255,0.9)]">
                        <div className="flex h-28 w-28 items-center justify-center rounded-full bg-white text-center shadow-sm">
                            <div>
                                <p className="text-xs font-semibold tracking-[0.24em] text-[var(--codesprout-leaf)] uppercase">Example</p>
                                <p className="mt-2 font-[family-name:var(--font-display)] text-4xl font-black text-[var(--codesprout-ink)]">68%</p>
                                <p className="mt-1 text-xs font-medium text-[var(--codesprout-ink)]/62">On track</p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-5 space-y-3 text-center">
                        <p className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--codesprout-ink)]">Sample learner view</p>
                        <p className="text-sm leading-7 text-[var(--codesprout-ink)]/74">
                            Families can track progress, see teacher feedback and celebrate achievements in one place.
                        </p>
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                    {previewMetrics.map((metric) => {
                        const Icon = metric.icon;

                        return (
                            <div
                                key={metric.label}
                                className="rounded-[1.4rem] border border-[rgba(36,52,67,0.08)] bg-white px-4 py-4 shadow-[0_8px_20px_rgba(36,52,67,0.04)]"
                            >
                                <div className="flex items-start gap-3">
                                    <span className={cn('flex size-11 shrink-0 items-center justify-center rounded-2xl', toneStyles[metric.tone])}>
                                        <Icon className="size-5" />
                                    </span>
                                    <div className="min-w-0">
                                        <p className="text-xs font-semibold tracking-[0.22em] text-[var(--codesprout-ink)]/52 uppercase">
                                            {metric.label}
                                        </p>
                                        <p className="mt-1 text-base leading-7 font-semibold text-[var(--codesprout-ink)]">{metric.value}</p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
