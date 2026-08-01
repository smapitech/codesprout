import { MetricCard } from '@/components/dashboard/metric-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Code2, FilePlus2, ShieldCheck, Sparkles } from 'lucide-react';

export default function AdminHtmlIndex({ summary, exercises, templates, createHref, templateCreateHref }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'HTML Engine', href: '/admin/html' }]}>
            <Head title="HTML Engine" />
            <main className="space-y-6 p-4 md:p-6">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-sm font-black tracking-[0.2em] text-emerald-700 uppercase">CodeSprout HTML</p>
                        <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[#243443]">Early webpage builder engine</h1>
                        <p className="mt-2 max-w-3xl text-slate-600">
                            Versioned HTML exercises, safe previews, immutable templates and teacher-reviewed projects.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild className="rounded-full font-bold">
                            <Link href={createHref}>
                                <FilePlus2 className="h-4 w-4" /> Create exercise
                            </Link>
                        </Button>
                        <Button asChild variant="outline" className="rounded-full font-bold">
                            <Link href={templateCreateHref}>Create template</Link>
                        </Button>
                    </div>
                </section>
                <section className="grid gap-4 md:grid-cols-4">
                    <MetricCard
                        title="Published exercises"
                        value={String(summary.publishedExercises)}
                        description="Visible to learners"
                        icon={<Code2 />}
                    />
                    <MetricCard
                        title="Completed attempts"
                        value={String(summary.completedAttempts)}
                        description="Server validated"
                        icon={<Sparkles />}
                    />
                    <MetricCard
                        title="Awaiting review"
                        value={String(summary.projectsAwaitingReview)}
                        description="Teacher queue"
                        icon={<FilePlus2 />}
                    />
                    <MetricCard
                        title="Unsafe blocked"
                        value={String(summary.unsafeValidations)}
                        description="Sanitiser protections"
                        icon={<ShieldCheck />}
                    />
                </section>
                <section className="grid gap-4 lg:grid-cols-2">
                    {exercises.map((exercise: any) => (
                        <Card key={exercise.slug} className="rounded-[2rem]">
                            <CardHeader>
                                <CardTitle>{exercise.title}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <p className="text-sm font-semibold text-slate-600">
                                    {exercise.type} · {exercise.status} · version {exercise.currentVersion ?? 'draft'}
                                </p>
                                <Button asChild className="rounded-full">
                                    <Link href={exercise.href}>Open</Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </section>
                <section className="rounded-[2rem] bg-[#fff8ea] p-5">
                    <h2 className="font-[family-name:var(--font-display)] text-2xl font-black">Project templates</h2>
                    <div className="mt-4 grid gap-3 md:grid-cols-2">
                        {templates.map((template: any) => (
                            <article key={template.slug} className="rounded-2xl bg-white p-4">
                                <p className="font-black">{template.title}</p>
                                <p className="text-sm font-semibold text-slate-600">
                                    {template.status} · version {template.currentVersion ?? 'draft'}
                                </p>
                                {template.publishHref && (
                                    <Button asChild size="sm" className="mt-3 rounded-full">
                                        <Link method="post" href={template.publishHref}>
                                            Publish version
                                        </Link>
                                    </Button>
                                )}
                            </article>
                        ))}
                    </div>
                </section>
            </main>
        </AppLayout>
    );
}
