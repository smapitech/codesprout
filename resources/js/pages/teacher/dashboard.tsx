import { MetricCard } from '@/components/dashboard/metric-card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ClipboardCheck, ClipboardList, Code2, Users, Wand2 } from 'lucide-react';

interface TeacherDashboardProps {
    assignedClasses: Array<{
        id: number;
        name: string;
        code: string;
        learners_count: number;
    }>;
    assignedLearners: Array<{
        id: number;
        name: string;
        learner_id: string | null;
    }>;
    pendingWork: {
        count: number;
        assignments: Array<{ id: number; child: string; assignment: string; status: string; review_href: string }>;
        htmlProjects: Array<{
            uuid: string;
            child: string;
            title: string;
            template: string | null;
            status: string;
            submittedAt: string | null;
            reviewHref: string;
        }>;
    };
    quickActions: Array<{ label: string; href: string }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teacher Dashboard',
        href: '/teacher/dashboard',
    },
];

export default function TeacherDashboard({ assignedClasses, assignedLearners, pendingWork, quickActions }: TeacherDashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teacher Dashboard" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Teacher</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)]">
                        Classroom command center
                    </h1>
                    <p className="text-muted-foreground max-w-2xl">
                        Assigned learners and class groups appear here so teachers can stay focused on the right children.
                    </p>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    <MetricCard
                        title="Assigned classes"
                        value={String(assignedClasses.length)}
                        description="Only classes assigned to this teacher are shown."
                        icon={<ClipboardList className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Assigned learners"
                        value={String(assignedLearners.length)}
                        description="Learners enrolled in assigned classes."
                        accent="from-sky-500/20 to-cyan-500/20"
                        icon={<Users className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Pending work"
                        value={String(pendingWork.count)}
                        description={`${pendingWork.assignments.length} assignments and ${pendingWork.htmlProjects.length} HTML projects shown in your queue.`}
                        accent="from-amber-500/20 to-orange-500/20"
                        icon={<Wand2 className="h-5 w-5" />}
                    />
                </section>

                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {quickActions.map((action) => (
                        <Link
                            key={action.href}
                            href={action.href}
                            className="group flex items-center justify-between rounded-3xl border border-emerald-100 bg-white/90 p-4 font-semibold text-emerald-950 shadow-sm transition hover:bg-emerald-50"
                        >
                            {action.label}
                            <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" />
                        </Link>
                    ))}
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                        <h2 className="font-[family-name:var(--font-display)] text-xl font-bold text-[var(--foreground)]">Assigned classes</h2>
                        <div className="mt-4 space-y-3">
                            {assignedClasses.length > 0 ? (
                                assignedClasses.map((classroom) => (
                                    <Link
                                        key={classroom.id}
                                        href={route('teacher.classes.show', classroom.id)}
                                        className="bg-muted/40 hover:bg-muted/60 block rounded-3xl p-4 transition"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <div>
                                                <p className="font-semibold text-[var(--foreground)]">{classroom.name}</p>
                                                <p className="text-muted-foreground text-sm">{classroom.code}</p>
                                            </div>
                                            <span className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-900">
                                                {classroom.learners_count} learners
                                            </span>
                                        </div>
                                    </Link>
                                ))
                            ) : (
                                <div className="rounded-3xl border border-dashed border-emerald-200 bg-emerald-50/40 p-6 text-sm text-emerald-900">
                                    No class assignments yet.
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                            <h2 className="font-[family-name:var(--font-display)] text-xl font-bold text-[var(--foreground)]">Assigned learners</h2>
                            <div className="mt-4 space-y-3">
                                {assignedLearners.length > 0 ? (
                                    assignedLearners.slice(0, 6).map((learner) => (
                                        <div key={learner.id} className="bg-muted/40 flex items-center justify-between rounded-3xl p-4">
                                            <div>
                                                <p className="font-semibold text-[var(--foreground)]">{learner.name}</p>
                                                <p className="text-muted-foreground text-sm">{learner.learner_id ?? 'Learner ID pending'}</p>
                                            </div>
                                            <span className="text-xs font-medium tracking-[0.2em] text-emerald-700 uppercase">Learner</span>
                                        </div>
                                    ))
                                ) : (
                                    <div className="rounded-3xl border border-dashed border-emerald-200 bg-emerald-50/40 p-6 text-sm text-emerald-900">
                                        Learners will appear once class enrolments are created.
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                            <div className="flex items-center gap-3">
                                <ClipboardCheck className="h-5 w-5 text-emerald-700" />
                                <h2 className="font-[family-name:var(--font-display)] text-xl font-bold">Assignment review queue</h2>
                            </div>
                            <div className="mt-4 space-y-3">
                                {pendingWork.assignments.map((item) => (
                                    <Link
                                        key={item.id}
                                        href={item.review_href}
                                        className="block rounded-3xl bg-amber-50 p-4 transition hover:bg-amber-100"
                                    >
                                        <p className="font-semibold text-amber-950">{item.assignment}</p>
                                        <p className="mt-1 text-sm text-amber-800">
                                            {item.child} · {item.status.replaceAll('_', ' ')}
                                        </p>
                                    </Link>
                                ))}
                                {pendingWork.assignments.length === 0 && (
                                    <p className="rounded-3xl border border-dashed p-5 text-sm text-slate-600">
                                        No assignment submissions are waiting.
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                            <div className="flex items-center gap-3">
                                <Code2 className="h-5 w-5 text-sky-700" />
                                <h2 className="font-[family-name:var(--font-display)] text-xl font-bold">HTML project reviews</h2>
                            </div>
                            <div className="mt-4 space-y-3">
                                {pendingWork.htmlProjects.map((item) => (
                                    <Link
                                        key={item.uuid}
                                        href={item.reviewHref}
                                        className="block rounded-3xl bg-sky-50 p-4 transition hover:bg-sky-100"
                                    >
                                        <p className="font-semibold text-sky-950">{item.title}</p>
                                        <p className="mt-1 text-sm text-sky-800">
                                            {item.child} · {item.status.replaceAll('_', ' ')}
                                        </p>
                                    </Link>
                                ))}
                                {pendingWork.htmlProjects.length === 0 && (
                                    <p className="rounded-3xl border border-dashed p-5 text-sm text-slate-600">
                                        No HTML projects are waiting for review.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
