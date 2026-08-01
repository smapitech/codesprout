import { EmptyState, StatusPill } from '@/components/assignments/assignment-shell';
import { MetricCard } from '@/components/dashboard/metric-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { AssignmentRecord } from '@/types/assignments';
import { Head, Link } from '@inertiajs/react';
import { Archive, ClipboardCheck, FilePlus2, Pencil, Send, Users } from 'lucide-react';

interface AssignmentIndexProps {
    role: 'administrator' | 'teacher';
    assignments: AssignmentRecord[];
    summary: {
        assigned_learners: number;
        awaiting_review: number;
        completed: number;
        attempt_count: number;
        allocation_count: number;
        average_score: number;
    };
    markingQueue?: Array<{
        id: number;
        child: string;
        class: string;
        assignment: string;
        status: string;
        auto_score: number;
        maximum_score: number;
        late: boolean;
        review_href: string;
    }>;
}

export default function AssignmentIndex({ role, assignments, summary, markingQueue = [] }: AssignmentIndexProps) {
    const base = role === 'administrator' ? 'admin' : 'teacher';
    const breadcrumbs: BreadcrumbItem[] = [
        { title: role === 'administrator' ? 'Administrator Dashboard' : 'Teacher Dashboard', href: `/${base}/dashboard` },
        { title: 'Assignments', href: `/${base}/assignments` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Assignment Management" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="space-y-2">
                        <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">{role}</p>
                        <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">Assignment library</h1>
                        <p className="text-muted-foreground max-w-3xl">
                            Create reusable CodeSprout missions, publish safe versions, allocate work and review submissions.
                        </p>
                    </div>
                    <Button asChild className="h-12 rounded-full px-5 text-base font-semibold shadow-lg shadow-emerald-500/20">
                        <Link href={route(`${base}.assignments.create`)}>
                            <FilePlus2 className="h-4 w-4" />
                            Create assignment
                        </Link>
                    </Button>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        title="Allocations"
                        value={String(summary.allocation_count ?? 0)}
                        description="Active and scheduled mission releases."
                        icon={<Send className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Attempts"
                        value={String(summary.attempt_count ?? 0)}
                        description="Learner starts, submissions and reviews."
                        accent="from-sky-500/20 to-cyan-500/20"
                        icon={<ClipboardCheck className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Awaiting review"
                        value={String(summary.awaiting_review ?? 0)}
                        description="Manual marking ready for teachers."
                        accent="from-amber-500/20 to-orange-500/20"
                        icon={<Pencil className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Completed"
                        value={String(summary.completed ?? 0)}
                        description="Marked or completed assignment attempts."
                        accent="from-violet-500/20 to-fuchsia-500/20"
                        icon={<Users className="h-5 w-5" />}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-4">
                        {assignments.length === 0 ? (
                            <EmptyState title="No assignments yet" description="Create a draft to start building the reusable mission library." />
                        ) : (
                            assignments.map((assignment) => (
                                <Card
                                    key={assignment.id}
                                    className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]"
                                >
                                    <CardHeader className="space-y-3">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <StatusPill
                                                value={assignment.status}
                                                tone={
                                                    assignment.status === 'published'
                                                        ? 'emerald'
                                                        : assignment.status === 'archived'
                                                          ? 'coral'
                                                          : 'amber'
                                                }
                                            />
                                            <span className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
                                                {assignment.assignment_type_label} · {assignment.versions_count} version(s)
                                            </span>
                                        </div>
                                        <CardTitle className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                            {assignment.current_version?.title ?? 'Untitled draft'}
                                        </CardTitle>
                                        <p className="text-sm leading-7 text-slate-600">
                                            {assignment.current_version?.short_description ??
                                                'Draft assignment ready for questions and instructions.'}
                                        </p>
                                    </CardHeader>
                                    <CardContent className="flex flex-wrap gap-3">
                                        <Button asChild className="h-11 rounded-full px-5 text-sm font-semibold">
                                            <Link href={route(`${base}.assignments.show`, assignment.id)}>
                                                Open builder
                                                <Pencil className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        {role === 'administrator' && (
                                            <Button asChild variant="outline" className="h-11 rounded-full px-5 text-sm font-semibold">
                                                <Link method="post" href={route('admin.assignments.archive', assignment.id)}>
                                                    <Archive className="h-4 w-4" />
                                                    Archive
                                                </Link>
                                            </Button>
                                        )}
                                    </CardContent>
                                </Card>
                            ))
                        )}
                    </div>

                    <aside className="space-y-4">
                        <Card className="rounded-[2rem] border-white/80 bg-[#fffaf2] shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader>
                                <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                    Marking queue
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {markingQueue.length === 0 ? (
                                    <EmptyState title="Nothing waiting" description="Submitted missions that need review will appear here." />
                                ) : (
                                    markingQueue.map((attempt) => (
                                        <Link
                                            key={attempt.id}
                                            href={attempt.review_href}
                                            className="block rounded-[1.25rem] border border-slate-200 bg-white p-4 transition hover:border-emerald-300"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-bold text-[var(--foreground)]">{attempt.assignment}</p>
                                                    <p className="mt-1 text-sm text-slate-600">
                                                        {attempt.child} · {attempt.class}
                                                    </p>
                                                </div>
                                                <StatusPill value={attempt.status} tone={attempt.late ? 'coral' : 'sky'} />
                                            </div>
                                            <p className="mt-3 text-sm font-semibold text-emerald-900">
                                                Auto score: {attempt.auto_score} / {attempt.maximum_score}
                                            </p>
                                        </Link>
                                    ))
                                )}
                            </CardContent>
                        </Card>

                        <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader>
                                <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                    Version safety
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm leading-7 text-slate-700">
                                <p>Published versions stay stable for existing allocations. Edits create a new draft version for future releases.</p>
                                <p>Child mission payloads do not include correct-answer flags.</p>
                            </CardContent>
                        </Card>
                    </aside>
                </section>
            </div>
        </AppLayout>
    );
}
