import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

interface TeacherWorldItem {
    id: number | null;
    slug: string | null;
    number: number | null;
    name: string | null;
    short_description: string | null;
    status: string | null;
    theme_colour: string | null;
    accent_colour: string | null;
    classes: Array<{ id: number; name: string; code: string }>;
    units_count: number;
    lessons_count: number;
    preview_href: string;
}

interface TeacherCurriculumIndexProps {
    worlds: TeacherWorldItem[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
    { title: 'Curriculum', href: '/teacher/curriculum' },
];

export default function TeacherCurriculumIndex({ worlds }: TeacherCurriculumIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teacher Curriculum" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Teacher</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)]">
                        Published curriculum for your classes
                    </h1>
                    <p className="text-muted-foreground max-w-3xl">
                        Teachers can browse only the curriculum worlds assigned to their classes and preview the published child experience.
                    </p>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    {worlds.map((world) => (
                        <Card
                            key={world.slug ?? world.id ?? undefined}
                            className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]"
                        >
                            <CardHeader className="space-y-3">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <Badge className="border-transparent bg-emerald-100 px-3 py-1 text-emerald-900">{world.status}</Badge>
                                    <span className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">World {world.number}</span>
                                </div>
                                <CardTitle className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    {world.name}
                                </CardTitle>
                                <p className="text-sm leading-7 text-slate-600">{world.short_description}</p>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <InfoChip label="Classes" value={world.classes.map((classroom) => classroom.name).join(', ')} />
                                    <InfoChip label="Lessons" value={String(world.lessons_count)} />
                                </div>

                                <div className="flex flex-wrap gap-3">
                                    <Button asChild className="h-11 rounded-full px-5 text-sm font-semibold">
                                        <Link href={world.preview_href}>
                                            Preview world
                                            <ArrowRight className="ml-2 h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" className="h-11 rounded-full px-5 text-sm font-semibold">
                                        <Link href={world.preview_href}>Open published curriculum</Link>
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
