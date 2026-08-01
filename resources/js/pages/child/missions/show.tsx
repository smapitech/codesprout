import { AssignmentShell, StatusPill } from '@/components/assignments/assignment-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { AssignmentAllocation, AssignmentAttempt, AssignmentVersion } from '@/types/assignments';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Clock, Play, RotateCcw, Sparkles, Star, Volume2 } from 'lucide-react';

interface MissionShowProps {
    allocation: AssignmentAllocation;
    mission: AssignmentVersion;
    latestAttempt: AssignmentAttempt | null;
    canStart: boolean;
    startAction: string;
}

export default function ChildMissionShow({ allocation, mission, latestAttempt, canStart, startAction }: MissionShowProps) {
    const canContinue = latestAttempt && ['in_progress', 'returned'].includes(latestAttempt.status);

    return (
        <AssignmentShell child>
            <Head title={`${mission.title} | Mission`} />

            <main className="mx-auto max-w-5xl space-y-6 px-4 py-5 pb-24 md:px-6 lg:py-8">
                <Link
                    href={route('child.missions.index')}
                    className="inline-flex h-11 items-center gap-2 rounded-full bg-white px-4 text-sm font-bold text-emerald-900 shadow-sm"
                >
                    <ArrowLeft className="h-4 w-4" />
                    My Missions
                </Link>

                <section className="overflow-hidden rounded-[2.25rem] border border-white/70 bg-white/90 shadow-[0_24px_90px_rgba(31,76,58,0.1)]">
                    <div className="bg-[linear-gradient(135deg,_#138a72,_#54b7d3)] p-6 text-white md:p-8">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <StatusPill value={canContinue ? 'Continue Mission' : 'Ready to Play'} tone="amber" />
                            <span className="inline-flex items-center gap-1 rounded-full bg-white/20 px-3 py-1 text-sm font-bold">
                                <Star className="h-4 w-4 fill-amber-300 text-amber-300" />
                                {Math.max(1, Math.ceil(mission.total_points / 2))} stars
                            </span>
                        </div>
                        <h1 className="mt-6 font-[family-name:var(--font-display)] text-4xl font-black md:text-5xl">{mission.title}</h1>
                        <p className="mt-4 max-w-3xl text-lg leading-8 text-white/90">{mission.child_instructions}</p>
                    </div>

                    <CardContent className="grid gap-5 p-6 md:grid-cols-3 md:p-8">
                        <Info icon={<Clock className="h-5 w-5" />} label="Time" value={`${mission.estimated_minutes} minutes`} />
                        <Info icon={<Sparkles className="h-5 w-5" />} label="Steps" value={`${mission.items.length} activities`} />
                        <Info
                            icon={<RotateCcw className="h-5 w-5" />}
                            label="Attempts"
                            value={`${allocation.attempt_limit ?? mission.default_attempt_limit} try`}
                        />

                        <div className="space-y-4 rounded-[1.75rem] bg-emerald-50 p-5 md:col-span-3">
                            <div className="flex items-start gap-3">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm">
                                    <Volume2 className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                        Listen and try your best
                                    </p>
                                    <p className="mt-1 text-sm leading-7 text-slate-700">You can use hints and come back if you need a break.</p>
                                </div>
                            </div>

                            {canContinue && latestAttempt ? (
                                <Button
                                    asChild
                                    className="h-14 w-full rounded-full text-lg font-black shadow-lg shadow-emerald-500/20 md:w-auto md:px-8"
                                >
                                    <Link href={route('child.missions.attempts.show', latestAttempt.id)}>
                                        <Play className="h-5 w-5" />
                                        Continue Mission
                                    </Link>
                                </Button>
                            ) : (
                                <Button
                                    asChild
                                    disabled={!canStart}
                                    className="h-14 w-full rounded-full text-lg font-black shadow-lg shadow-emerald-500/20 md:w-auto md:px-8"
                                >
                                    <Link method="post" href={startAction}>
                                        <Play className="h-5 w-5" />
                                        Start Mission
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </section>
            </main>
        </AssignmentShell>
    );
}

function Info({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
    return (
        <Card className="rounded-[1.75rem] border-slate-200 bg-slate-50 shadow-none">
            <CardContent className="flex items-center gap-3 p-4">
                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm">{icon}</div>
                <div>
                    <p className="text-xs font-bold tracking-[0.18em] text-slate-500 uppercase">{label}</p>
                    <p className="mt-1 font-bold text-[var(--foreground)]">{value}</p>
                </div>
            </CardContent>
        </Card>
    );
}
