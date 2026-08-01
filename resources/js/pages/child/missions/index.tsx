import { AssignmentShell, EmptyState, StatusPill } from '@/components/assignments/assignment-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays, CheckCircle2, Clock, Lock, Play, RotateCcw, Sparkles, Star } from 'lucide-react';

interface MissionCard {
    id: number;
    title: string;
    category: string;
    estimated_minutes: number;
    stars: number;
    state_label: string;
    action_label: string;
    available_from: string | null;
    due_at: string | null;
    status: string;
    attempt_status: string | null;
    continue_href: string;
}

interface MissionsProps {
    child: {
        name: string;
        learner_id: string | null;
        avatar_url: string | null;
    };
    missions: {
        today: MissionCard[];
        continue: MissionCard[];
        coming_soon: MissionCard[];
        completed: MissionCard[];
    };
}

const sections = [
    { key: 'today', title: 'Today', icon: Sparkles, empty: 'New missions will appear here when your teacher opens them.' },
    { key: 'continue', title: 'Continue', icon: RotateCcw, empty: 'Started missions will wait here so you can come back.' },
    { key: 'coming_soon', title: 'Coming Soon', icon: Lock, empty: 'Future missions will unlock when it is time.' },
    { key: 'completed', title: 'Completed', icon: CheckCircle2, empty: 'Finished missions will shine here.' },
] as const;

export default function ChildMissionsIndex({ child, missions }: MissionsProps) {
    return (
        <AssignmentShell child>
            <Head title="My Missions" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-5 pb-24 md:px-6 lg:py-8">
                <header className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.08)] backdrop-blur">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">My Missions</p>
                            <h1 className="mt-2 font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)] md:text-4xl">
                                Ready to grow today, {child.name.split(' ')[0]}?
                            </h1>
                            <p className="mt-2 max-w-2xl text-base leading-7 text-slate-700">
                                Pick a mission, listen to the instructions and take one small step at a time.
                            </p>
                        </div>
                        <Link
                            href={route('child.dashboard')}
                            className="inline-flex h-12 items-center justify-center rounded-full bg-emerald-100 px-5 text-sm font-bold text-emerald-900 transition hover:bg-emerald-200"
                        >
                            Back home
                        </Link>
                    </div>
                </header>

                {sections.map((section) => {
                    const Icon = section.icon;
                    const cards = missions[section.key];

                    return (
                        <section key={section.key} className="space-y-4">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm">
                                    <Icon className="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                        {section.title}
                                    </h2>
                                    <p className="text-sm text-slate-600">
                                        {cards.length} mission{cards.length === 1 ? '' : 's'}
                                    </p>
                                </div>
                            </div>

                            {cards.length === 0 ? (
                                <EmptyState title="All clear" description={section.empty} />
                            ) : (
                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    {cards.map((mission, index) => (
                                        <MissionCardView
                                            key={`${section.key}-${mission.id}`}
                                            mission={mission}
                                            index={index}
                                            locked={section.key === 'coming_soon'}
                                        />
                                    ))}
                                </div>
                            )}
                        </section>
                    );
                })}
            </div>
        </AssignmentShell>
    );
}

function MissionCardView({ mission, index, locked }: { mission: MissionCard; index: number; locked: boolean }) {
    const tones = ['bg-emerald-500', 'bg-sky-500', 'bg-amber-500', 'bg-violet-500'];

    return (
        <Card className="overflow-hidden rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
            <div className="relative min-h-40 bg-[linear-gradient(135deg,_#fff8ea,_#dff7ef)] p-5">
                <div
                    className={`flex h-16 w-16 items-center justify-center rounded-[1.5rem] ${tones[index % tones.length]} text-2xl font-black text-white shadow-lg`}
                >
                    {index + 1}
                </div>
                <div className="absolute top-5 right-5 flex gap-1 text-amber-500">
                    {Array.from({ length: Math.min(5, mission.stars) }, (_, starIndex) => (
                        <Star key={starIndex} className="h-5 w-5 fill-amber-400" />
                    ))}
                </div>
                <div className="absolute right-5 bottom-5 left-5 flex items-center justify-between gap-3">
                    <StatusPill value={mission.state_label} tone={locked ? 'amber' : mission.attempt_status === 'marked' ? 'emerald' : 'sky'} />
                    <span className="rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-slate-700">{mission.category}</span>
                </div>
            </div>
            <CardContent className="space-y-4 p-5">
                <div>
                    <h3 className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">{mission.title}</h3>
                    <div className="mt-3 flex flex-wrap gap-2 text-sm text-slate-600">
                        <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 font-semibold">
                            <Clock className="h-4 w-4" />
                            {mission.estimated_minutes} min
                        </span>
                        {mission.due_at && (
                            <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-900">
                                <CalendarDays className="h-4 w-4" />
                                Due {new Date(mission.due_at).toLocaleDateString()}
                            </span>
                        )}
                    </div>
                </div>
                <Button asChild disabled={locked} className="h-12 w-full rounded-full text-base font-bold shadow-lg shadow-emerald-500/20">
                    <Link href={mission.continue_href}>
                        {locked ? <Lock className="h-4 w-4" /> : <Play className="h-4 w-4" />}
                        {locked ? 'Not open yet' : mission.action_label}
                    </Link>
                </Button>
            </CardContent>
        </Card>
    );
}
