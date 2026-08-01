import { CurriculumTree } from '@/components/curriculum/curriculum-tree';
import { MetricCard } from '@/components/dashboard/metric-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArchiveRestore, ArrowDownToLine, BadgeCheck, Pencil, PlayCircle, ShieldCheck } from 'lucide-react';

interface CurriculumBuilderProps {
    mode: 'builder';
    curriculum: {
        title: string;
        slug: string;
        description: string | null;
        target_min_age: number | null;
        target_max_age: number | null;
        duration_weeks: number;
        lessons_per_week: number;
        version: string;
        status: string;
        published_at: string | null;
        worlds_count: number;
        units_count: number;
        lessons_count: number;
        stages_count: number;
    };
    worlds: Array<Record<string, unknown>>;
    skills: Array<Record<string, unknown>>;
    statusOptions: Array<{ value: string; label: string }>;
    validation: {
        is_publishable: boolean;
        messages: Record<string, string[]>;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Dashboard', href: '/admin/dashboard' },
    { title: 'Curriculum', href: '/admin/curriculum' },
    { title: 'Builder', href: '/admin/curriculum' },
];

export default function CurriculumBuilderPage({ curriculum, worlds, skills, validation }: CurriculumBuilderProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${curriculum.title} | Curriculum Builder`} />

            <div className="space-y-6 p-4 md:p-6">
                <section className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                        <CardHeader className="space-y-3">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <Badge className="border-transparent bg-emerald-100 px-3 py-1 text-emerald-900">{curriculum.status}</Badge>
                                <span className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">v{curriculum.version}</span>
                            </div>

                            <CardTitle className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">
                                {curriculum.title}
                            </CardTitle>
                            <p className="max-w-4xl text-sm leading-7 text-slate-600">{curriculum.description}</p>
                        </CardHeader>

                        <CardContent className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <MetricCard
                                title="Worlds"
                                value={String(curriculum.worlds_count)}
                                description="Learning worlds in the year-long programme."
                                icon={<BadgeCheck className="h-5 w-5" />}
                            />
                            <MetricCard
                                title="Units"
                                value={String(curriculum.units_count)}
                                description="Weekly units grouped by story world."
                                accent="from-sky-500/20 to-cyan-500/20"
                                icon={<ArchiveRestore className="h-5 w-5" />}
                            />
                            <MetricCard
                                title="Lessons"
                                value={String(curriculum.lessons_count)}
                                description="Child-friendly teaching sessions."
                                accent="from-violet-500/20 to-fuchsia-500/20"
                                icon={<PlayCircle className="h-5 w-5" />}
                            />
                            <MetricCard
                                title="Stages"
                                value={String(curriculum.stages_count)}
                                description="Small supported steps inside each lesson."
                                accent="from-amber-500/20 to-orange-500/20"
                                icon={<ShieldCheck className="h-5 w-5" />}
                            />
                        </CardContent>
                    </Card>

                    <Card className="rounded-[2rem] border-white/80 bg-[#fffaf2] shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                        <CardHeader>
                            <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                Validation
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm leading-7 text-slate-700">
                            {validation.is_publishable ? (
                                <div className="rounded-[1.35rem] border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                                    This curriculum currently meets the publication rules.
                                </div>
                            ) : (
                                <div className="space-y-3 rounded-[1.35rem] border border-amber-200 bg-amber-50 p-4 text-amber-950">
                                    <p className="font-semibold">Fix the items below before publishing:</p>
                                    <ul className="space-y-2">
                                        {Object.entries(validation.messages).map(([key, values]) => (
                                            <li key={key}>
                                                <span className="font-semibold">{key}:</span> {values.join(' ')}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="flex flex-wrap gap-2">
                                <Button asChild variant="outline" className="h-11 rounded-full px-4 text-sm font-semibold">
                                    <Link href={route('admin.curriculum.preview', curriculum.slug)}>Preview</Link>
                                </Button>
                                <Button asChild variant="ghost" className="h-11 rounded-full px-4 text-sm font-semibold text-emerald-800">
                                    <Link href={route('admin.curriculum.edit', curriculum.slug)}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Edit
                                    </Link>
                                </Button>
                            </div>

                            <div className="grid gap-2">
                                <Button asChild className="h-12 rounded-full px-4 text-base font-semibold shadow-lg shadow-emerald-500/20">
                                    <Link method="post" href={route('admin.curriculum.validate', curriculum.slug)}>
                                        Validate publication
                                    </Link>
                                </Button>
                                <Button asChild variant="secondary" className="h-12 rounded-full px-4 text-base font-semibold">
                                    <Link method="post" href={route('admin.curriculum.publish', curriculum.slug)}>
                                        Publish curriculum
                                    </Link>
                                </Button>
                                <Button asChild variant="outline" className="h-12 rounded-full px-4 text-base font-semibold">
                                    <Link method="post" href={route('admin.curriculum.archive', curriculum.slug)}>
                                        Archive curriculum
                                    </Link>
                                </Button>
                                <Button asChild variant="outline" className="h-12 rounded-full px-4 text-base font-semibold">
                                    <Link method="post" href={route('admin.curriculum.restore', curriculum.slug)}>
                                        Restore curriculum
                                    </Link>
                                </Button>
                                <Button asChild variant="ghost" className="h-12 rounded-full px-4 text-base font-semibold text-emerald-800">
                                    <a href={route('admin.curriculum.export', curriculum.slug)} target="_blank" rel="noreferrer">
                                        <ArrowDownToLine className="mr-2 h-4 w-4" />
                                        Export JSON
                                    </a>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </section>

                <section className="space-y-4">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Hierarchy</p>
                            <h2 className="mt-1 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                Curriculum, worlds, units, lessons and stages
                            </h2>
                        </div>
                        <p className="text-muted-foreground text-sm">{skills.length} skills connected to the seed curriculum</p>
                    </div>

                    <CurriculumTree worlds={worlds as never[]} variant="admin" />
                </section>

                <section className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                        <CardHeader>
                            <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                What comes next
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm leading-7 text-slate-700">
                            <p>This builder is the foundation for future world, unit, lesson and stage editors.</p>
                            <p>
                                Publication remains guarded by a dedicated validation service, and the child journey only consumes published content.
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="rounded-[2rem] border-white/80 bg-[#fffaf2] shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                        <CardHeader>
                            <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                Skills
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {skills.slice(0, 8).map((skill) => (
                                <div key={String(skill.slug)} className="rounded-[1.25rem] border border-slate-200 bg-white p-3">
                                    <p className="font-semibold text-[var(--foreground)]">{String(skill.name)}</p>
                                    <p className="mt-1 text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">{String(skill.category)}</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
