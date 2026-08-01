import { ProgressRing } from '@/components/dashboard/progress-ring';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Award, Flame, Sparkles, Star } from 'lucide-react';
import type React from 'react';

interface ParentChildProgress {
    id: number;
    name: string;
    learner_id: string | null;
    avatar_url: string | null;
    profile: {
        total_stars: number;
        total_experience: number;
        current_level: string;
        current_streak: number;
        completed_missions: number;
        completed_worlds: number;
    };
    skills: Array<{ name: string; mastery: number; label: string; aria_label: string }>;
    recent_badges: Array<{ id: string; name: string; description: string }>;
}

interface ParentProgressProps {
    children: ParentChildProgress[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Progress', href: '/parent/progress' }];

export default function ParentProgress({ children }: ParentProgressProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Family Progress" />
            <main className="space-y-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Parent</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight">Family progress summary</h1>
                    <p className="text-muted-foreground max-w-3xl">
                        Encouraging progress summaries for linked children only. Parents can view learning progress, not submit work or change
                        rewards.
                    </p>
                </section>

                <section className="grid gap-6">
                    {children.map((child) => (
                        <article
                            key={child.id}
                            className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]"
                        >
                            <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <h2 className="font-[family-name:var(--font-display)] text-2xl font-black">{child.name}</h2>
                                    <p className="text-muted-foreground text-sm">{child.learner_id ?? 'Learner ID pending'}</p>
                                    <p className="mt-3 text-sm text-slate-700">
                                        Mouse Control, typing and coding-symbol skills grow through short, validated missions and teacher-reviewed
                                        work.
                                    </p>
                                </div>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <MiniStat icon={<Star />} label="Stars" value={child.profile.total_stars} />
                                    <MiniStat icon={<Sparkles />} label="XP" value={child.profile.total_experience} />
                                    <MiniStat icon={<Flame />} label="Streak" value={child.profile.current_streak} />
                                    <MiniStat icon={<Award />} label="Worlds" value={child.profile.completed_worlds} />
                                </div>
                            </div>

                            <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                {child.skills.slice(0, 4).map((skill) => (
                                    <div key={skill.name} role="img" aria-label={skill.aria_label} className="rounded-3xl bg-emerald-50/70 p-4">
                                        <ProgressRing value={skill.mastery} label={skill.name} size={130} />
                                        <p className="mt-3 text-center text-sm font-semibold">{skill.label}</p>
                                    </div>
                                ))}
                            </div>
                        </article>
                    ))}
                </section>
            </main>
        </AppLayout>
    );
}

function MiniStat({ icon, label, value }: { icon: React.ReactNode; label: string; value: number }) {
    return (
        <div className="rounded-2xl bg-[#FFF8EA] p-4 text-center">
            <div className="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-white text-emerald-700">{icon}</div>
            <p className="mt-2 text-xs font-bold tracking-[0.14em] text-emerald-700 uppercase">{label}</p>
            <p className="font-[family-name:var(--font-display)] text-2xl font-black">{value}</p>
        </div>
    );
}
