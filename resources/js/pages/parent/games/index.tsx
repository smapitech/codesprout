import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Result {
    child: string;
    learner_id?: string;
    game: string;
    category: string;
    difficulty: string;
    completion_status: string;
    accuracy: number;
    score: number;
    maximum_score: number;
    completed_at?: string;
}

interface Props {
    results: Result[];
}

export default function ParentGameResults({ results }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Parent Dashboard', href: '/parent/dashboard' },
        { title: 'Game Progress', href: '/parent/games' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Game Progress" />
            <main className="space-y-6 p-4 md:p-6">
                <section>
                    <p className="text-sm font-black tracking-[0.2em] text-emerald-700 uppercase">Parent preview</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">Game progress summaries</h1>
                    <p className="mt-2 max-w-2xl text-slate-600">
                        Released results for linked children appear here with encouraging learning information.
                    </p>
                </section>
                <section className="grid gap-4 lg:grid-cols-2">
                    {results.map((result, index) => (
                        <Card key={`${result.learner_id}-${result.game}-${index}`} className="rounded-[2rem] border-white/80 bg-white/95">
                            <CardHeader>
                                <CardTitle>{result.game}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-slate-700">
                                <p>
                                    <strong>{result.child}</strong> practised {result.category}.
                                </p>
                                <p>Difficulty: {result.difficulty}</p>
                                <p>Accuracy: {result.accuracy}%</p>
                                <p>
                                    Score released: {result.score} / {result.maximum_score}
                                </p>
                                <p className="font-bold text-emerald-700">Great steady progress.</p>
                            </CardContent>
                        </Card>
                    ))}
                    {results.length === 0 ? <p className="rounded-2xl bg-white p-5 text-slate-600">No released game results yet.</p> : null}
                </section>
            </main>
        </AppLayout>
    );
}
