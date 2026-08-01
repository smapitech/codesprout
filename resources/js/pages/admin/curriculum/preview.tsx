import { CurriculumTree } from '@/components/curriculum/curriculum-tree';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface CurriculumPreviewProps {
    mode: 'preview';
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
    { title: 'Preview', href: '/admin/curriculum' },
];

export default function CurriculumPreviewPage({ curriculum, worlds }: CurriculumPreviewProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${curriculum.title} | Preview`} />

            <div className="space-y-6 p-4 md:p-6">
                <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                    <CardHeader className="space-y-3">
                        <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Administrator preview</p>
                        <CardTitle className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">
                            {curriculum.title}
                        </CardTitle>
                        <p className="max-w-4xl text-sm leading-7 text-slate-600">{curriculum.description}</p>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-3">
                        <Button asChild className="h-11 rounded-full px-5 text-sm font-semibold">
                            <Link href={route('admin.curriculum.show', curriculum.slug)}>Back to builder</Link>
                        </Button>
                        <Button asChild variant="outline" className="h-11 rounded-full px-5 text-sm font-semibold">
                            <Link href={route('admin.curriculum.edit', curriculum.slug)}>Edit curriculum</Link>
                        </Button>
                    </CardContent>
                </Card>

                <CurriculumTree worlds={worlds as never[]} variant="admin" />
            </div>
        </AppLayout>
    );
}
