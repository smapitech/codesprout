import { MetricCard } from '@/components/dashboard/metric-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Gamepad2, Play, Plus, ShieldCheck } from 'lucide-react';

interface GameRow {
    id?: number;
    slug: string;
    name: string;
    category: string;
    game_type: string;
    status: string;
    version_count?: number;
    current_version?: number | null;
    href?: string;
    preview_href?: string;
    supported_input_methods?: string[];
}

interface Props {
    role: 'administrator' | 'teacher';
    games: GameRow[];
    summary?: {
        published: number;
        draft: number;
        archived: number;
    };
    createHref?: string;
}

export default function GameIndex({ role, games, summary, createHref }: Props) {
    const base = role === 'administrator' ? 'admin' : 'teacher';
    const breadcrumbs: BreadcrumbItem[] = [
        { title: role === 'administrator' ? 'Administrator Dashboard' : 'Teacher Dashboard', href: `/${base}/dashboard` },
        { title: 'Game Library', href: `/${base}/games` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Game Library" />

            <main className="space-y-6 p-4 md:p-6">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-sm font-bold tracking-[0.2em] text-emerald-700 uppercase">CodeSprout games</p>
                        <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">
                            Computer, mouse and keyboard games
                        </h1>
                        <p className="mt-2 max-w-3xl text-slate-600">
                            Approved game definitions with safe declarative configuration, spoken instructions and child-friendly controls.
                        </p>
                    </div>
                    {createHref ? (
                        <Button asChild className="h-12 rounded-full px-5 text-base font-bold">
                            <Link href={createHref}>
                                <Plus className="h-4 w-4" />
                                Create game
                            </Link>
                        </Button>
                    ) : null}
                </section>

                {summary ? (
                    <section className="grid gap-4 md:grid-cols-3">
                        <MetricCard
                            title="Published"
                            value={String(summary.published)}
                            description="Ready for preview and play."
                            icon={<ShieldCheck />}
                        />
                        <MetricCard
                            title="Draft"
                            value={String(summary.draft)}
                            description="Not visible to children."
                            accent="from-amber-500/20 to-orange-500/20"
                            icon={<Gamepad2 />}
                        />
                        <MetricCard
                            title="Archived"
                            value={String(summary.archived)}
                            description="Historical results remain readable."
                            accent="from-rose-500/20 to-coral-500/20"
                            icon={<Gamepad2 />}
                        />
                    </section>
                ) : null}

                <section className="grid gap-4 lg:grid-cols-2">
                    {games.map((game) => (
                        <Card key={game.slug} className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black tracking-[0.16em] text-emerald-700 uppercase">
                                        {game.category}
                                    </span>
                                    <span className="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">{game.status}</span>
                                </div>
                                <CardTitle className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    {game.name}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-sm font-semibold text-slate-600">{game.game_type}</p>
                                <div className="flex flex-wrap gap-2">
                                    {(game.supported_input_methods ?? ['mouse', 'touch', 'keyboard']).map((method) => (
                                        <span key={method} className="rounded-full bg-[#fff8ea] px-3 py-1 text-xs font-bold text-slate-700">
                                            {method}
                                        </span>
                                    ))}
                                </div>
                                <Button asChild className="h-11 rounded-full px-5 font-bold">
                                    <Link href={game.preview_href ?? game.href ?? '#'}>
                                        <Play className="h-4 w-4" />
                                        {role === 'teacher' ? 'Teacher Preview' : 'Open game'}
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </section>
            </main>
        </AppLayout>
    );
}
