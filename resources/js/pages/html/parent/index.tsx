import { SafePreview } from '@/components/html/safe-preview';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function ParentHtmlIndex({ summary }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'HTML Progress', href: '/parent/html' }]}>
            <Head title="HTML Progress" />
            <main className="space-y-6 p-4 md:p-6">
                <header>
                    <p className="text-sm font-black tracking-[0.2em] text-emerald-700 uppercase">Family HTML summary</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[#243443]">Webpage learning progress</h1>
                    <p className="mt-2 max-w-3xl text-slate-600">
                        HTML gives structure to a webpage. Short, careful coding practice is more valuable than rushing.
                    </p>
                </header>
                <section className="grid gap-4 lg:grid-cols-2">
                    {summary.projects.map((project: any) => (
                        <Card key={`${project.child}-${project.title}`} className="rounded-[2rem]">
                            <CardHeader>
                                <CardTitle>{project.title}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <p className="font-semibold text-slate-600">
                                    {project.child} · {project.status}
                                </p>
                                {project.approvedPreview ? (
                                    <SafePreview html={project.approvedPreview} title={`${project.title} approved preview`} />
                                ) : (
                                    <p className="rounded-2xl bg-[#fff8ea] p-4 font-semibold text-slate-700">
                                        This project is waiting for teacher review or released feedback.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </section>
            </main>
        </AppLayout>
    );
}
