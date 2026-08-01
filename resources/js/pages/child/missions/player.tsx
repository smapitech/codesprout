import { AssignmentShell, StatusPill } from '@/components/assignments/assignment-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { AssignmentAllocation, AssignmentAttempt, AssignmentItem, AssignmentVersion } from '@/types/assignments';
import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle2, Code2, FileCode2, HelpCircle, Send, Volume2 } from 'lucide-react';
import { useMemo, useState } from 'react';

interface PlayerProps {
    attempt: AssignmentAttempt;
    allocation: AssignmentAllocation;
    mission: AssignmentVersion;
    progress: {
        current: number;
        total: number;
    };
    actions: {
        save_response_base: string;
        submit: string;
        attachment_base: string;
        html_launch_base: string;
        project_launch_base: string;
    };
}

export default function ChildMissionPlayer({ attempt, mission, actions }: PlayerProps) {
    const [index, setIndex] = useState(0);
    const [saving, setSaving] = useState(false);
    const [submitted, setSubmitted] = useState(['submitted', 'awaiting_review', 'marked', 'completed'].includes(attempt.status));
    const current = mission.items[index];
    const existing = useMemo(
        () => attempt.responses.find((response) => response.assignment_item_id === current?.id)?.response_data ?? {},
        [attempt.responses, current?.id],
    );
    const [responses, setResponses] = useState<Record<number, Record<string, FormDataConvertible>>>(
        Object.fromEntries(attempt.responses.map((response) => [response.assignment_item_id, response.response_data ?? {}])),
    );
    const value = current?.id ? (responses[current.id] ?? existing) : {};
    const percent = mission.items.length > 0 ? Math.round(((index + 1) / mission.items.length) * 100) : 100;

    const save = (item: AssignmentItem, response: Record<string, FormDataConvertible>) => {
        if (!item.id || submitted) return;
        setSaving(true);
        setResponses((all) => ({ ...all, [item.id as number]: response }));
        router.post(
            `${actions.save_response_base}/${item.id}`,
            { response },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    const submit = () => {
        if (submitted) return;
        setSubmitted(true);
        router.post(actions.submit, {}, { preserveScroll: true });
    };

    if (!current) {
        return (
            <AssignmentShell child>
                <Head title="Mission Complete" />
                <Completion title={mission.title} />
            </AssignmentShell>
        );
    }

    return (
        <AssignmentShell child>
            <Head title={`${mission.title} | Player`} />

            <main className="mx-auto flex min-h-screen max-w-5xl flex-col gap-5 px-4 py-5 md:px-6 lg:py-8">
                <header className="rounded-[2rem] border border-white/70 bg-white/90 p-4 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm font-bold tracking-[0.2em] text-emerald-700 uppercase">Mission player</p>
                            <h1 className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)] md:text-3xl">
                                {mission.title}
                            </h1>
                        </div>
                        <StatusPill value={submitted ? 'Great Work!' : saving ? 'Saving' : 'Ready'} tone={submitted ? 'emerald' : 'sky'} />
                    </div>
                    <div
                        className="mt-4 h-4 rounded-full bg-emerald-100"
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={percent}
                    >
                        <div className="h-4 rounded-full bg-emerald-500 transition-all" style={{ width: `${percent}%` }} />
                    </div>
                    <p className="mt-2 text-sm font-semibold text-slate-600">
                        Step {index + 1} of {mission.items.length}
                    </p>
                </header>

                {submitted ? (
                    <Completion title={mission.title} />
                ) : (
                    <Card className="flex-1 rounded-[2.25rem] border-white/80 bg-white/95 shadow-[0_24px_90px_rgba(31,76,58,0.1)]">
                        <CardContent className="space-y-6 p-5 md:p-8">
                            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <StatusPill value={current.question_type.replaceAll('_', ' ')} tone="violet" />
                                    <h2 className="mt-4 font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">
                                        {current.title}
                                    </h2>
                                    <p className="mt-3 max-w-3xl text-lg leading-8 text-slate-700">{current.prompt_text}</p>
                                </div>
                                <Button type="button" variant="outline" className="h-12 rounded-full px-5 text-base font-bold">
                                    <Volume2 className="h-5 w-5" />
                                    Repeat
                                </Button>
                            </div>

                            {current.html_exercise_version_id ? (
                                <LinkedActivity
                                    title="Complete the published HTML exercise"
                                    description="Your validated completion will return to this exact assignment step."
                                    href={`${actions.html_launch_base}/${current.id}`}
                                    icon={<Code2 className="h-6 w-6" />}
                                />
                            ) : current.project_template_version_id ? (
                                <LinkedActivity
                                    title="Open the webpage project"
                                    description="Your project is saved separately and this step completes only after teacher approval."
                                    href={`${actions.project_launch_base}/${current.id}`}
                                    icon={<FileCode2 className="h-6 w-6" />}
                                />
                            ) : (
                                <Question item={current} value={value} onChange={(response) => save(current, response)} />
                            )}

                            {current.hint_text ? (
                                <details className="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-4 text-amber-950">
                                    <summary className="flex cursor-pointer items-center gap-2 font-bold">
                                        <HelpCircle className="h-5 w-5" />
                                        Hint
                                    </summary>
                                    <p className="mt-2 text-sm leading-7">{current.hint_text}</p>
                                </details>
                            ) : null}

                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={index === 0}
                                    className="h-12 rounded-full px-6 text-base font-bold"
                                    onClick={() => setIndex((currentIndex) => Math.max(0, currentIndex - 1))}
                                >
                                    <ArrowLeft className="h-5 w-5" />
                                    Back
                                </Button>
                                {index === mission.items.length - 1 ? (
                                    <Button
                                        type="button"
                                        className="h-12 rounded-full px-8 text-base font-black shadow-lg shadow-emerald-500/20"
                                        onClick={submit}
                                    >
                                        <Send className="h-5 w-5" />
                                        Submit Mission
                                    </Button>
                                ) : (
                                    <Button
                                        type="button"
                                        className="h-12 rounded-full px-8 text-base font-black shadow-lg shadow-emerald-500/20"
                                        onClick={() => setIndex((currentIndex) => Math.min(mission.items.length - 1, currentIndex + 1))}
                                    >
                                        Next
                                        <ArrowRight className="h-5 w-5" />
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </main>
        </AssignmentShell>
    );
}

function LinkedActivity({ title, description, href, icon }: { title: string; description: string; href: string; icon: React.ReactNode }) {
    return (
        <section className="rounded-[1.75rem] border-2 border-sky-200 bg-sky-50 p-5">
            <div className="flex items-start gap-4">
                <div className="rounded-2xl bg-white p-3 text-sky-800 shadow-sm">{icon}</div>
                <div>
                    <h3 className="text-xl font-black text-sky-950">{title}</h3>
                    <p className="mt-2 text-sm leading-7 text-sky-800">{description}</p>
                </div>
            </div>
            <Button asChild className="mt-5 h-12 rounded-full px-6 font-black">
                <Link method="post" href={href}>
                    Open activity <ArrowRight className="h-4 w-4" />
                </Link>
            </Button>
        </section>
    );
}

function Question({
    item,
    value,
    onChange,
}: {
    item: AssignmentItem;
    value: Record<string, FormDataConvertible>;
    onChange: (response: Record<string, FormDataConvertible>) => void;
}) {
    if (item.left_items && item.right_items) {
        const pairs = (value.pairs as Record<string, string>) ?? {};

        return (
            <div className="grid gap-3 md:grid-cols-2">
                {item.left_items.map((left) => (
                    <label key={left.value ?? left.id} className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                        <span className="font-bold text-[var(--foreground)]">{left.text ?? left.value}</span>
                        <select
                            className="mt-3 h-12 w-full rounded-2xl border border-emerald-200 bg-white px-4 text-base font-semibold"
                            value={pairs[String(left.value)] ?? ''}
                            onChange={(event) => onChange({ pairs: { ...pairs, [String(left.value)]: event.target.value } })}
                        >
                            <option value="">Choose a match</option>
                            {item.right_items?.map((right) => (
                                <option key={right.value ?? right.id} value={String(right.value)}>
                                    {right.text ?? right.value}
                                </option>
                            ))}
                        </select>
                    </label>
                ))}
            </div>
        );
    }

    if (item.items) {
        const order = ((value.order as string[]) ?? item.items.map((option) => String(option.value))).filter(Boolean);

        return (
            <div className="space-y-3">
                {order.map((optionValue, optionIndex) => {
                    const option = item.items?.find((candidate) => String(candidate.value) === optionValue);
                    return (
                        <div key={optionValue} className="flex items-center gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-3">
                            <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-white font-black text-emerald-800">
                                {optionIndex + 1}
                            </span>
                            <span className="min-w-0 flex-1 font-bold text-[var(--foreground)]">{option?.text ?? optionValue}</span>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="rounded-full"
                                onClick={() => onChange({ order: move(order, optionIndex, -1) })}
                                disabled={optionIndex === 0}
                            >
                                <ArrowLeft className="h-4 w-4 rotate-90" />
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="rounded-full"
                                onClick={() => onChange({ order: move(order, optionIndex, 1) })}
                                disabled={optionIndex === order.length - 1}
                            >
                                <ArrowRight className="h-4 w-4 rotate-90" />
                            </Button>
                        </div>
                    );
                })}
            </div>
        );
    }

    if (item.options?.length) {
        const selected = String(value.selected_option_value ?? '');
        return (
            <div className="grid gap-3 md:grid-cols-2">
                {item.options.map((option) => (
                    <button
                        key={option.value ?? option.id}
                        type="button"
                        className={`min-h-20 rounded-[1.5rem] border-2 p-4 text-left text-lg font-black transition ${selected === String(option.value) ? 'border-emerald-500 bg-emerald-50 text-emerald-950' : 'border-slate-200 bg-slate-50 text-[var(--foreground)] hover:border-emerald-300'}`}
                        onClick={() => onChange({ selected_option_value: String(option.value) })}
                    >
                        {option.text ?? option.value}
                    </button>
                ))}
            </div>
        );
    }

    return (
        <div className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
            <Input
                className="h-14 rounded-2xl bg-white text-lg"
                value={String(value.text ?? value.value ?? '')}
                placeholder={item.placeholder ?? 'Type your answer here'}
                onChange={(event) => onChange({ text: event.target.value })}
            />
        </div>
    );
}

function move(values: string[], index: number, direction: -1 | 1) {
    const target = index + direction;
    if (target < 0 || target >= values.length) return values;
    const next = [...values];
    [next[index], next[target]] = [next[target], next[index]];
    return next;
}

function Completion({ title }: { title: string }) {
    return (
        <section className="rounded-[2.25rem] border border-white/70 bg-white/90 p-8 text-center shadow-[0_24px_90px_rgba(31,76,58,0.1)]">
            <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-[2rem] bg-emerald-100 text-emerald-700">
                <CheckCircle2 className="h-10 w-10" />
            </div>
            <h1 className="mt-5 font-[family-name:var(--font-display)] text-4xl font-black text-[var(--foreground)]">Great Work!</h1>
            <p className="mx-auto mt-3 max-w-xl text-lg leading-8 text-slate-700">
                {title} has been submitted. Your teacher can review anything that needs a helping hand.
            </p>
            <Button asChild className="mt-6 h-12 rounded-full px-8 text-base font-black">
                <a href={route('child.missions.index')}>Back to My Missions</a>
            </Button>
        </section>
    );
}
