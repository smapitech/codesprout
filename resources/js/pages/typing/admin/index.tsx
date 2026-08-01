import { MetricCard } from '@/components/dashboard/metric-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Keyboard, Plus, ShieldCheck, Sparkles } from 'lucide-react';

interface ExerciseRow {
    slug: string;
    title: string;
    type: string;
    status: string;
    versions: number;
    currentVersion?: number;
    difficulty?: string;
    href: string;
}

interface Props {
    exercises: ExerciseRow[];
    summary: {
        exercises: number;
        published_exercises: number;
        sessions: number;
        completed_sessions: number;
        average_first_attempt_accuracy: number;
        average_final_text_accuracy: number;
    };
    createHref: string;
}

export default function AdminTypingIndex({ exercises, summary, createHref }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Administrator Dashboard', href: '/admin/dashboard' },
        { title: 'Typing Engine', href: '/admin/typing' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Typing Engine" />
            <main className="space-y-6 p-4 md:p-6">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-sm font-black tracking-[0.2em] text-emerald-700 uppercase">CodeSprout typing</p>
                        <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[#243443]">Typing practice library</h1>
                        <p className="mt-2 max-w-3xl text-slate-600">
                            Versioned typing exercises with safe content, bounded input capture and server-calculated accuracy.
                        </p>
                    </div>
                    <Button asChild className="h-12 rounded-full px-5 font-bold">
                        <Link href={createHref}>
                            <Plus className="h-4 w-4" />
                            Create exercise
                        </Link>
                    </Button>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    <MetricCard
                        title="Exercises"
                        value={String(summary.exercises)}
                        description="Draft, published and archived."
                        icon={<Keyboard />}
                    />
                    <MetricCard
                        title="Published"
                        value={String(summary.published_exercises)}
                        description="Visible to authorised learners."
                        icon={<ShieldCheck />}
                    />
                    <MetricCard
                        title="Average accuracy"
                        value={`${summary.average_first_attempt_accuracy || 0}%`}
                        description="First-attempt accuracy across valid results."
                        icon={<Sparkles />}
                    />
                </section>

                <section className="grid gap-4 lg:grid-cols-2">
                    {exercises.map((exercise) => (
                        <Card key={exercise.slug} className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader>
                                <div className="flex flex-wrap justify-between gap-2">
                                    <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{exercise.type}</span>
                                    <span className="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">{exercise.status}</span>
                                </div>
                                <CardTitle className="font-[family-name:var(--font-display)] text-2xl font-black">{exercise.title}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-sm font-semibold text-slate-600">
                                    Version {exercise.currentVersion ?? 'draft'} · {exercise.difficulty ?? 'No difficulty profile'}
                                </p>
                                <Button asChild className="rounded-full font-bold">
                                    <Link href={exercise.href}>Open exercise</Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </section>
            </main>
        </AppLayout>
    );
}
