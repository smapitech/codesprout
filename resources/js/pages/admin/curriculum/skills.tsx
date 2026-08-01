import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface SkillItem {
    id: number;
    name: string;
    slug: string;
    category: string;
    description: string | null;
    mastery_description: string | null;
    lessons_count: number;
    stages_count: number;
    status: string;
}

interface SkillPageProps {
    skills: SkillItem[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Dashboard', href: '/admin/dashboard' },
    { title: 'Curriculum', href: '/admin/curriculum' },
    { title: 'Skills', href: '/admin/curriculum/skills' },
];

export default function CurriculumSkillsPage({ skills }: SkillPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Curriculum Skills" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Administrator</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)]">
                        Skills management
                    </h1>
                    <p className="text-muted-foreground max-w-3xl">
                        These skill tags connect lessons and stages to the mastery model that will be expanded in the next phase.
                    </p>
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    {skills.map((skill) => (
                        <Card key={skill.id} className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader className="space-y-3">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <Badge className="border-transparent bg-emerald-100 px-3 py-1 text-emerald-900">{skill.status}</Badge>
                                    <span className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">{skill.category}</span>
                                </div>
                                <CardTitle className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    {skill.name}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm leading-7 text-slate-700">
                                <p>{skill.description}</p>
                                <p className="text-sm font-semibold text-[var(--foreground)]">{skill.mastery_description}</p>
                                <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
                                    {skill.lessons_count} lessons connected / {skill.stages_count} stages connected
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </section>
            </div>
        </AppLayout>
    );
}
