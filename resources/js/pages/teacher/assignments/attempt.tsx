import { EmptyState, StatusPill } from '@/components/assignments/assignment-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { AssignmentAllocation, AssignmentAttempt } from '@/types/assignments';
import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, RotateCcw, Save } from 'lucide-react';
import { FormEvent } from 'react';

interface TeacherAttemptProps {
    attempt: AssignmentAttempt;
    allocation: AssignmentAllocation;
    actions: {
        mark: string;
        return: string;
        complete: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
    { title: 'Assignments', href: '/teacher/assignments' },
    { title: 'Review submission', href: '/teacher/assignments' },
];

interface MarkingForm extends Record<string, FormDataConvertible> {
    manual_scores: Record<string, string>;
    teacher_comment: string;
    feedback_text: string;
    feedback_type: string;
    returned_for_retry: boolean;
    visible_to_child: boolean;
    visible_to_parent: boolean;
}

export default function TeacherAssignmentAttempt({ attempt, allocation, actions }: TeacherAttemptProps) {
    const { data, setData, patch, processing, errors } = useForm<MarkingForm>({
        manual_scores: Object.fromEntries(attempt.responses.map((response) => [response.assignment_item_id, String(response.manual_score ?? 0)])),
        teacher_comment: '',
        feedback_text: attempt.feedback[0]?.feedback_text ?? '',
        feedback_type: 'general',
        returned_for_retry: false,
        visible_to_child: true,
        visible_to_parent: true,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        patch(actions.mark);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${attempt.assignment_title ?? 'Assignment'} | Review`} />

            <div className="space-y-6 p-4 md:p-6">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="space-y-2">
                        <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Submission review</p>
                        <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">
                            {attempt.assignment_title}
                        </h1>
                        <p className="text-muted-foreground max-w-3xl">
                            {attempt.child_name} · Attempt {attempt.attempt_number} · {allocation.target_label}
                        </p>
                    </div>
                    <Button asChild variant="outline" className="h-11 rounded-full px-5 text-sm font-semibold">
                        <Link href={route('teacher.assignments.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Back to queue
                        </Link>
                    </Button>
                </section>

                <section className="grid gap-4 md:grid-cols-4">
                    <Metric label="Status" value={attempt.status.replaceAll('_', ' ')} />
                    <Metric label="Auto score" value={`${attempt.auto_score} / ${attempt.maximum_score}`} />
                    <Metric label="Manual score" value={String(attempt.manual_score)} />
                    <Metric label="Late" value={attempt.is_late ? 'Yes' : 'No'} />
                </section>

                <form onSubmit={submit} className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-4">
                        {attempt.responses.length === 0 ? (
                            <EmptyState title="No responses yet" description="The learner has not saved any responses for this attempt." />
                        ) : (
                            attempt.responses.map((response, index) => (
                                <Card
                                    key={response.id}
                                    className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]"
                                >
                                    <CardHeader className="space-y-3">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <StatusPill
                                                value={`Response ${index + 1}`}
                                                tone={response.is_correct ? 'emerald' : response.is_correct === false ? 'amber' : 'sky'}
                                            />
                                            <span className="text-sm font-bold text-slate-600">Auto: {response.auto_score}</span>
                                        </div>
                                        <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                            {response.text_response || 'Saved response'}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="grid gap-4 md:grid-cols-[180px_minmax(0,1fr)]">
                                        <div>
                                            <Label className="text-sm font-semibold">Manual points</Label>
                                            <Input
                                                type="number"
                                                min="0"
                                                step="0.5"
                                                value={String(data.manual_scores[response.assignment_item_id] ?? '0')}
                                                onChange={(event) =>
                                                    setData('manual_scores', {
                                                        ...data.manual_scores,
                                                        [response.assignment_item_id]: event.target.value,
                                                    })
                                                }
                                            />
                                        </div>
                                        <div>
                                            <Label className="text-sm font-semibold">Stored learner response</Label>
                                            <pre className="mt-2 overflow-auto rounded-[1.25rem] bg-slate-50 p-4 text-sm text-slate-700">
                                                {JSON.stringify(response.response_data ?? response.text_response, null, 2)}
                                            </pre>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))
                        )}
                    </div>

                    <aside className="space-y-4">
                        <Card className="rounded-[2rem] border-white/80 bg-[#fffaf2] shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader>
                                <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                    Feedback
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Child-friendly feedback</Label>
                                    <Textarea
                                        value={data.feedback_text}
                                        onChange={(event) => setData('feedback_text', event.target.value)}
                                        placeholder="You worked carefully. Try the symbol step one more time."
                                    />
                                    {errors.feedback_text ? <p className="text-sm text-red-600">{errors.feedback_text}</p> : null}
                                </div>
                                <div className="space-y-2">
                                    <Label>Teacher note for responses</Label>
                                    <Textarea value={data.teacher_comment} onChange={(event) => setData('teacher_comment', event.target.value)} />
                                </div>
                                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={data.visible_to_parent}
                                        onChange={(event) => setData('visible_to_parent', event.target.checked)}
                                    />
                                    Visible to parent
                                </label>
                                <Button type="submit" disabled={processing} className="h-12 w-full rounded-full text-base font-semibold">
                                    <Save className="h-4 w-4" />
                                    Save marking
                                </Button>
                                <Button asChild variant="outline" className="h-12 w-full rounded-full text-base font-semibold">
                                    <Link method="post" href={actions.return}>
                                        <RotateCcw className="h-4 w-4" />
                                        Return for retry
                                    </Link>
                                </Button>
                                <Button asChild variant="secondary" className="h-12 w-full rounded-full text-base font-semibold">
                                    <Link method="post" href={actions.complete}>
                                        <CheckCircle2 className="h-4 w-4" />
                                        Mark complete
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </aside>
                </form>
            </div>
        </AppLayout>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[1.5rem] border border-white/80 bg-white/95 p-4 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
            <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">{label}</p>
            <p className="mt-2 text-lg font-black text-[var(--foreground)] capitalize">{value}</p>
        </div>
    );
}
