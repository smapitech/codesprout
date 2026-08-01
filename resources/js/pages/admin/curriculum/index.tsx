import { MetricCard } from '@/components/dashboard/metric-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, Layers3, Plus, Sparkles, Star } from 'lucide-react';

interface CurriculumItem {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    status: string;
    version: string;
    published_at: string | null;
    target_min_age: number | null;
    target_max_age: number | null;
    duration_weeks: number;
    lessons_per_week: number;
    worlds_count: number;
    units_count: number;
    lessons_count: number;
    stages_count: number;
    edit_href: string;
    builder_href: string;
    preview_href: string;
}

interface CurriculumIndexProps {
    curricula: CurriculumItem[];
    totals: {
        curricula: number;
        worlds: number;
        units: number;
        lessons: number;
        stages: number;
    };
    statusOptions: Array<{ value: string; label: string }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Dashboard', href: '/admin/dashboard' },
    { title: 'Curriculum', href: '/admin/curriculum' },
];

export default function CurriculumIndex({ curricula, totals }: CurriculumIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Curriculum Management" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="space-y-2">
                        <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Administrator</p>
                        <h1 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)]">
                            Curriculum management
                        </h1>
                        <p className="text-muted-foreground max-w-3xl">
                            Organise the one-year CodeSprout programme, review the current structure and open the curriculum builder.
                        </p>
                    </div>

                    <Button asChild className="h-12 rounded-full px-5 text-base font-semibold shadow-lg shadow-emerald-500/20">
                        <Link href={route('admin.curriculum.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Create curriculum
                        </Link>
                    </Button>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        title="Curricula"
                        value={String(totals.curricula)}
                        description="Programme containers ready for publishing."
                        icon={<BookOpen className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Worlds"
                        value={String(totals.worlds)}
                        description="Story-based learning worlds across the programme."
                        accent="from-sky-500/20 to-cyan-500/20"
                        icon={<Sparkles className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Lessons"
                        value={String(totals.lessons)}
                        description="Teaching sessions arranged inside weekly units."
                        accent="from-violet-500/20 to-fuchsia-500/20"
                        icon={<Layers3 className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Stages"
                        value={String(totals.stages)}
                        description="Small child-friendly interactions within each lesson."
                        accent="from-amber-500/20 to-orange-500/20"
                        icon={<Star className="h-5 w-5" />}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    {curricula.map((curriculum) => (
                        <Card key={curriculum.id} className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader className="space-y-3">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <Badge className="border-transparent bg-emerald-100 px-3 py-1 text-emerald-900">{curriculum.status}</Badge>
                                    <span className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">v{curriculum.version}</span>
                                </div>

                                <CardTitle className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    {curriculum.title}
                                </CardTitle>
                                <p className="text-sm leading-7 text-slate-600">{curriculum.description}</p>
                            </CardHeader>

                            <CardContent className="space-y-4">
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <InfoChip label="Ages" value={`${curriculum.target_min_age ?? 'Any'} to ${curriculum.target_max_age ?? 'Any'}`} />
                                    <InfoChip label="Duration" value={`${curriculum.duration_weeks} weeks`} />
                                    <InfoChip label="Lessons per week" value={String(curriculum.lessons_per_week)} />
                                    <InfoChip label="Hierarchy" value={`${curriculum.worlds_count} worlds`} />
                                </div>

                                <div className="flex flex-wrap gap-3 pt-2">
                                    <Button asChild className="h-11 rounded-full px-5 text-sm font-semibold">
                                        <Link href={curriculum.builder_href}>
                                            Open builder
                                            <ArrowRight className="ml-2 h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" className="h-11 rounded-full px-5 text-sm font-semibold">
                                        <Link href={curriculum.preview_href}>Preview</Link>
                                    </Button>
                                    <Button asChild variant="ghost" className="h-11 rounded-full px-5 text-sm font-semibold text-emerald-800">
                                        <Link href={curriculum.edit_href}>Edit</Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </section>
            </div>
        </AppLayout>
    );
}

function InfoChip({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[1.35rem] border border-slate-200 bg-slate-50 px-4 py-3">
            <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">{label}</p>
            <p className="mt-1 text-sm font-semibold text-[var(--foreground)]">{value}</p>
        </div>
    );
}
