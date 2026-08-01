import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { Code2, Keyboard, LayoutTemplate, Sparkles } from 'lucide-react';

export default function ChildHtmlIndex({ summary, readiness, recommendation, exercises, templates, projects }: any) {
    return (
        <main className="min-h-screen bg-[#fff8ea] px-4 py-5 text-[#243443] md:px-8">
            <Head title="My HTML Adventure" />
            <section className="mx-auto max-w-6xl space-y-6">
                <header className="overflow-hidden rounded-[2.5rem] bg-white p-5 shadow-[0_20px_80px_rgba(31,76,58,0.12)] md:p-8">
                    <p className="text-sm font-black tracking-[0.2em] text-[#138a72] uppercase">CodeSprout HTML</p>
                    <h1 className="font-[family-name:var(--font-display)] text-4xl font-black md:text-6xl">Build your first webpages</h1>
                    <p className="mt-3 max-w-2xl text-lg font-semibold text-slate-600">
                        Discover tags, practise coding symbols, and preview your webpage in a safe little window.
                    </p>
                    <div className="mt-5 grid gap-3 md:grid-cols-3">
                        <ChildMetric icon={<Sparkles />} label="Completed HTML" value={String(summary.completedExercises || 0)} />
                        <ChildMetric icon={<LayoutTemplate />} label="Active projects" value={String(summary.activeProjects || 0)} />
                        <ChildMetric icon={<Code2 />} label="Approved projects" value={String(summary.approvedProjects || 0)} />
                    </div>
                </header>

                <section className="rounded-[2rem] bg-white p-5">
                    <h2 className="font-[family-name:var(--font-display)] text-2xl font-black">Continue HTML Adventure</h2>
                    <p className="mt-1 font-semibold text-slate-600">
                        {recommendation.label}: {recommendation.reason}
                    </p>
                    <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        {readiness.slice(0, 8).map((item: any) => (
                            <div key={item.key} className="rounded-2xl bg-[#fff8ea] p-3">
                                <p className="text-lg font-black">{item.key}</p>
                                <p className="text-sm font-semibold text-slate-600">{item.label}</p>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {exercises.map((exercise: any) => (
                        <article key={exercise.slug} className="rounded-[2rem] bg-white p-5 shadow-[0_16px_60px_rgba(31,76,58,0.1)]">
                            <Keyboard className="h-9 w-9 text-[#138a72]" aria-hidden />
                            <p className="mt-4 text-sm font-black tracking-[0.16em] text-[#138a72] uppercase">{exercise.type}</p>
                            <h2 className="mt-1 font-[family-name:var(--font-display)] text-2xl font-black">{exercise.title}</h2>
                            <p className="mt-2 min-h-12 font-semibold text-slate-600">{exercise.instructions}</p>
                            <Button asChild className="mt-5 h-12 w-full rounded-full text-base font-black">
                                <Link method="post" href={exercise.startHref}>
                                    Start activity
                                </Link>
                            </Button>
                        </article>
                    ))}
                </section>

                <section className="grid gap-4 lg:grid-cols-2">
                    <Panel title="Starter webpage projects">
                        {templates.map((template: any) => (
                            <div key={template.slug} className="rounded-2xl bg-[#fff8ea] p-4">
                                <p className="font-black">{template.title}</p>
                                <p className="text-sm font-semibold text-slate-600">{template.description}</p>
                                <Button asChild className="mt-3 rounded-full">
                                    <Link method="post" href={template.startHref}>
                                        Start project
                                    </Link>
                                </Button>
                            </div>
                        ))}
                    </Panel>
                    <Panel title="Saved webpages">
                        {projects.map((project: any) => (
                            <Link key={project.uuid} href={project.href} className="block rounded-2xl bg-[#f1fbf7] p-4 focus-visible:ring-4">
                                <p className="font-black">{project.title}</p>
                                <p className="text-sm font-semibold text-slate-600">{project.status}</p>
                            </Link>
                        ))}
                    </Panel>
                </section>
            </section>
        </main>
    );
}

function ChildMetric({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
    return (
        <div className="rounded-[1.5rem] bg-[#fff8ea] p-4">
            <div className="flex items-center gap-3">
                <span className="rounded-full bg-white p-2 text-[#138a72]">{icon}</span>
                <span className="text-sm font-black text-slate-600">{label}</span>
            </div>
            <p className="mt-2 text-3xl font-black">{value}</p>
        </div>
    );
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <section className="space-y-3 rounded-[2rem] bg-white p-5">
            <h2 className="font-[family-name:var(--font-display)] text-2xl font-black">{title}</h2>
            {children}
        </section>
    );
}
