import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

export default function TeacherHtmlIndex({ exercises, projects }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'HTML Learning', href: '/teacher/html' }]}>
            <Head title="Teacher HTML Progress" />
            <main className="space-y-6 p-4 md:p-6">
                <header>
                    <p className="text-sm font-black tracking-[0.2em] text-emerald-700 uppercase">HTML learning</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[#243443]">Class webpage progress</h1>
                    <p className="mt-2 max-w-3xl text-slate-600">
                        Review safe previews, released feedback, tag confidence and projects awaiting teacher attention.
                    </p>
                </header>
                <section className="grid gap-4 lg:grid-cols-2">
                    <Card className="rounded-[2rem]">
                        <CardHeader>
                            <CardTitle>Published HTML activities</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {exercises.map((exercise: any) => (
                                <div key={exercise.slug} className="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-[#fff8ea] p-3">
                                    <div>
                                        <p className="font-black">{exercise.title}</p>
                                        <p className="text-sm font-semibold text-slate-600">{exercise.type}</p>
                                    </div>
                                    <Button asChild variant="outline" className="rounded-full">
                                        <Link href={exercise.previewHref}>Preview</Link>
                                    </Button>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                    <Card className="rounded-[2rem]">
                        <CardHeader>
                            <CardTitle>Projects in your classes</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {(projects.data ?? []).map((project: any) => (
                                <div key={project.uuid} className="rounded-2xl bg-[#f8fafc] p-3">
                                    <p className="font-black">{project.title}</p>
                                    <p className="text-sm font-semibold text-slate-600">
                                        {project.child} · {project.status} · {project.template}
                                    </p>
                                    <Button asChild className="mt-3 rounded-full">
                                        <Link href={project.reviewHref}>Review project</Link>
                                    </Button>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </section>
            </main>
        </AppLayout>
    );
}
