import { CurriculumTree } from '@/components/curriculum/curriculum-tree';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BookOpen, Calendar, Layers3 } from 'lucide-react';

interface TeacherCurriculumShowProps {
    curriculum: {
        title: string;
        slug: string;
    };
    world: {
        id: number;
        slug: string;
        number: number;
        name: string;
        short_description: string | null;
        story_description: string | null;
        theme_colour: string | null;
        accent_colour: string | null;
        status: string;
        units_count: number;
        lessons_count: number;
    };
    worlds: Array<Record<string, unknown>>;
    tree: Array<Record<string, unknown>>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
    { title: 'Curriculum', href: '/teacher/curriculum' },
    { title: 'World preview', href: '/teacher/curriculum' },
];

export default function TeacherCurriculumShow({ curriculum, world, worlds, tree }: TeacherCurriculumShowProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${world.name} | Teacher Curriculum`} />

            <div className="space-y-6 p-4 md:p-6">
                <section className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                        <CardHeader className="space-y-3">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <Badge className="border-transparent bg-emerald-100 px-3 py-1 text-emerald-900">{world.status}</Badge>
                                <span className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">World {world.number}</span>
                            </div>
                            <CardTitle className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">
                                {world.name}
                            </CardTitle>
                            <p className="max-w-4xl text-sm leading-7 text-slate-600">{world.short_description ?? world.story_description}</p>
                        </CardHeader>

                        <CardContent className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <Metric label="Curriculum" value={curriculum.title} Icon={BookOpen} />
                            <Metric label="Worlds" value={`${worlds.length}`} Icon={Layers3} />
                            <Metric label="Units" value={`${world.units_count}`} Icon={Calendar} />
                            <Metric label="Lessons" value={`${world.lessons_count}`} Icon={BookOpen} />
                        </CardContent>
                    </Card>

                    <Card className="rounded-[2rem] border-white/80 bg-[#fffaf2] shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                        <CardHeader>
                            <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                Assigned worlds
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm leading-7 text-slate-700">
                            {worlds.map((item) => (
                                <div key={String(item.slug)} className="rounded-[1.25rem] border border-slate-200 bg-white p-3">
                                    <p className="font-semibold text-[var(--foreground)]">{String(item.name)}</p>
                                    <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">{String(item.number)}</p>
                                </div>
                            ))}
                            <Button asChild className="h-11 w-full rounded-full px-5 text-sm font-semibold">
                                <Link href={route('teacher.curriculum.index')}>Back to curriculum list</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </section>

                <section className="space-y-4">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Published tree</p>
                            <h2 className="mt-1 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                Review the published curriculum structure
                            </h2>
                        </div>
                        <p className="text-muted-foreground text-sm">Teacher access remains read-only.</p>
                    </div>

                    <CurriculumTree worlds={tree as never[]} variant="teacher" selectedWorldSlug={world.slug} />
                </section>
            </div>
        </AppLayout>
    );
}

function Metric({ label, value, Icon }: { label: string; value: string; Icon: any }) {
    return (
        <div className="rounded-[1.35rem] border border-slate-200 bg-slate-50 px-4 py-3">
            <div className="flex items-center gap-2 text-slate-500">
                <Icon className="h-4 w-4" />
                <p className="text-xs font-semibold tracking-[0.18em] uppercase">{label}</p>
            </div>
            <p className="mt-2 truncate text-sm font-semibold text-[var(--foreground)]">{value}</p>
        </div>
    );
}
