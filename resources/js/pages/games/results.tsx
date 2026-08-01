import { MetricCard } from '@/components/dashboard/metric-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { BarChart3, CheckCircle2, Clock, Lightbulb } from 'lucide-react';

interface ResultRow {
    child: string;
    learner_id?: string;
    game: string;
    category: string;
    game_type: string;
    difficulty: string;
    completion_status: string;
    accuracy: number;
    completion_time: number;
    hints_used: number;
    score: number;
    maximum_score: number;
}

interface Props {
    summary: {
        sessions_started: number;
        sessions_completed: number;
        sessions_abandoned: number;
        completion_rate: number;
        average_accuracy: number;
        average_completion_time: number;
        hints_used: number;
        results: ResultRow[];
    };
}

export default function GameResults({ summary }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
        { title: 'Game Results', href: '/teacher/games/results' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Game Results" />
            <main className="space-y-6 p-4 md:p-6">
                <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">Game results</h1>
                <section className="grid gap-4 md:grid-cols-4">
                    <MetricCard
                        title="Started"
                        value={String(summary.sessions_started)}
                        description="Sessions in your classes."
                        icon={<BarChart3 />}
                    />
                    <MetricCard
                        title="Completed"
                        value={String(summary.sessions_completed)}
                        description={`${summary.completion_rate}% completion rate.`}
                        icon={<CheckCircle2 />}
                    />
                    <MetricCard
                        title="Accuracy"
                        value={`${summary.average_accuracy}%`}
                        description="Average completed result."
                        icon={<BarChart3 />}
                    />
                    <MetricCard title="Hints" value={String(summary.hints_used)} description="Support used by learners." icon={<Lightbulb />} />
                </section>
                <Card className="rounded-[2rem]">
                    <CardHeader>
                        <CardTitle>Recent results</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {summary.results.map((result, index) => (
                            <div key={`${result.learner_id}-${result.game}-${index}`} className="rounded-2xl border p-4">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p className="font-black text-[var(--foreground)]">{result.game}</p>
                                        <p className="text-sm text-slate-600">
                                            {result.child} · {result.category} · {result.difficulty}
                                        </p>
                                    </div>
                                    <span className="rounded-full bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-700">
                                        {result.accuracy}% accuracy
                                    </span>
                                </div>
                                <p className="mt-2 flex items-center gap-2 text-sm text-slate-600">
                                    <Clock className="h-4 w-4" />
                                    {result.completion_time}s · {result.score}/{result.maximum_score}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
