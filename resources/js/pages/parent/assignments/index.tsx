import { EmptyState, StatusPill } from '@/components/assignments/assignment-shell';
import { MetricCard } from '@/components/dashboard/metric-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ClipboardList, MessageSquareQuote, RotateCcw, ShieldCheck } from 'lucide-react';

interface ParentAssignmentsProps {
    children: Array<{
        id: number;
        name: string;
        learner_id: string | null;
        avatar_url: string | null;
        world: string;
    }>;
    assignments: Array<{
        id: number;
        child: string;
        assignment: string;
        status: string;
        due_at: string | null;
        submitted_at: string | null;
        score: number;
        maximum_score: number;
        teacher_feedback: string | null;
        retry_allowed: boolean;
        skills: string[];
    }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Parent Dashboard', href: '/parent/dashboard' },
    { title: 'Assignments', href: '/parent/assignments' },
];

export default function ParentAssignmentsIndex({ children, assignments }: ParentAssignmentsProps) {
    const outstanding = assignments.filter((assignment) => !['marked', 'completed'].includes(assignment.status)).length;
    const feedbackCount = assignments.filter((assignment) => assignment.teacher_feedback).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Family Assignments" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Parent</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">Assignments and feedback</h1>
                    <p className="text-muted-foreground max-w-3xl">
                        Review linked children only. Parents can see progress and feedback here, but assignment answers stay child-led.
                    </p>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    <MetricCard
                        title="Linked children"
                        value={String(children.length)}
                        description="Only children connected to this account."
                        icon={<ShieldCheck className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Outstanding"
                        value={String(outstanding)}
                        description="Due or awaiting teacher review."
                        accent="from-amber-500/20 to-orange-500/20"
                        icon={<ClipboardList className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Feedback notes"
                        value={String(feedbackCount)}
                        description="Visible teacher feedback messages."
                        accent="from-sky-500/20 to-cyan-500/20"
                        icon={<MessageSquareQuote className="h-5 w-5" />}
                    />
                </section>

                <section className="grid gap-4 xl:grid-cols-[320px_minmax(0,1fr)]">
                    <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                        <CardHeader>
                            <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                Children
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {children.map((child) => (
                                <div key={child.id} className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                    <p className="font-bold text-[var(--foreground)]">{child.name}</p>
                                    <p className="mt-1 text-sm text-slate-600">{child.learner_id ?? 'Learner ID pending'}</p>
                                    <p className="mt-1 text-sm text-slate-600">{child.world}</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <div className="space-y-4">
                        {assignments.length === 0 ? (
                            <EmptyState
                                title="No assignment attempts yet"
                                description="Once your child starts a mission, the assignment summary will appear here."
                            />
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
                                                tone={assignment.retry_allowed ? 'amber' : assignment.status === 'completed' ? 'emerald' : 'sky'}
                                            />
                                            {assignment.retry_allowed ? (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900">
                                                    <RotateCcw className="h-4 w-4" />
                                                    Another try available
                                                </span>
                                            ) : null}
                                        </div>
                                        <CardTitle className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                            {assignment.assignment}
                                        </CardTitle>
                                        <p className="text-sm text-slate-600">{assignment.child}</p>
                                    </CardHeader>
                                    <CardContent className="grid gap-4 md:grid-cols-3">
                                        <Info
                                            label="Due"
                                            value={assignment.due_at ? new Date(assignment.due_at).toLocaleDateString() : 'No due date'}
                                        />
                                        <Info
                                            label="Submitted"
                                            value={assignment.submitted_at ? new Date(assignment.submitted_at).toLocaleDateString() : 'Not submitted'}
                                        />
                                        <Info label="Score" value={`${assignment.score} / ${assignment.maximum_score}`} />
                                        <div className="rounded-[1.5rem] bg-emerald-50 p-4 md:col-span-3">
                                            <p className="text-xs font-bold tracking-[0.18em] text-emerald-700 uppercase">Teacher feedback</p>
                                            <p className="mt-2 text-sm leading-7 text-emerald-950">
                                                {assignment.teacher_feedback ?? 'Feedback will appear after the teacher releases it.'}
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))
                        )}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs font-bold tracking-[0.18em] text-slate-500 uppercase">{label}</p>
            <p className="mt-2 font-bold text-[var(--foreground)]">{value}</p>
        </div>
    );
}
