import { EmptyState, StatusPill } from '@/components/assignments/assignment-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { AssignmentItem, AssignmentItemOption, AssignmentRecord, AssignmentVersion, SelectOption } from '@/types/assignments';
import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowLeft, ArrowUp, Copy, Eye, FileCheck2, Plus, Save, Send, Trash2 } from 'lucide-react';
import { FormEvent, useMemo } from 'react';

interface BuilderOptions {
    classes: Array<{ id: number; name: string; code?: string | null }>;
    groups: Array<{ id: number; name: string; class_id: number; class_name?: string | null }>;
    children: Array<{ id: number; name: string; learner_id?: string | null }>;
    worlds: Array<{ id: number; name: string; number?: number; slug?: string }>;
    skills: Array<{ id: number; name: string; category?: string }>;
    htmlExercises: Array<{ id: number; title: string; version: number }>;
    projectTemplates: Array<{ id: number; title: string; version: number }>;
}

interface BuilderProps {
    mode: 'create' | 'edit';
    role: 'administrator' | 'teacher';
    assignment: AssignmentRecord | null;
    version: AssignmentVersion | null;
    action: string;
    method: 'post' | 'put';
    publishAction: string | null;
    allocateAction: string | null;
    submitLabel: string;
    modeLabel: string;
    assignmentTypeOptions: SelectOption[];
    difficultyLevelOptions: SelectOption[];
    feedbackModeOptions: SelectOption[];
    scoringMethodOptions: SelectOption[];
    lateSubmissionPolicyOptions: SelectOption[];
    questionTypeOptions: SelectOption[];
    builderOptions: BuilderOptions;
    validation: {
        is_publishable: boolean;
        messages: Record<string, string[]>;
    };
}

interface FormValues extends Record<string, FormDataConvertible> {
    assignment_type: string;
    title: string;
    short_description: string;
    child_instructions: string;
    teacher_instructions: string;
    audio_instruction_path: string;
    estimated_minutes: string;
    difficulty_level: string;
    default_attempt_limit: string;
    feedback_mode: string;
    scoring_method: string;
    settings: Record<string, FormDataConvertible>;
    items: AssignmentItem[];
    curriculum_links: Array<{ curriculum_world_id: number | null }>;
    skill_ids: number[];
}

const defaultItem = (order: number, type = 'multiple_choice'): AssignmentItem => ({
    title: `Mission step ${order}`,
    prompt_text: 'Choose the best answer.',
    question_type: type,
    interaction_type: '',
    points: 1,
    is_required: true,
    hint_text: '',
    explanation_text: '',
    display_order: order,
    configuration: {},
    grading_configuration:
        type.startsWith('type') || type.includes('html') || type.includes('javascript')
            ? { accepted_answers: [''], trim_spaces: true, case_sensitive: false }
            : {},
    options: type === 'short_child_response' || type === 'creative_project' || type === 'teacher_observation' ? [] : defaultOptions(type),
});

const defaultOptions = (type: string): AssignmentItemOption[] => {
    if (type === 'match_items' || type === 'match_opening_and_closing_html_tags') {
        return [
            { option_text: 'Mouse', option_value: 'mouse', matching_key: 'Clicking tool', is_correct: true, display_order: 1 },
            { option_text: 'Keyboard', option_value: 'keyboard', matching_key: 'Typing tool', is_correct: true, display_order: 2 },
        ];
    }

    if (type === 'order_sequence' || type === 'arrange_code_into_correct_order') {
        return [
            { option_text: 'First step', option_value: 'first', is_correct: true, display_order: 1 },
            { option_text: 'Next step', option_value: 'next', is_correct: true, display_order: 2 },
        ];
    }

    return [
        { option_text: 'Yes', option_value: 'yes', is_correct: true, display_order: 1 },
        { option_text: 'Not yet', option_value: 'not_yet', is_correct: false, display_order: 2 },
    ];
};

export default function AssignmentBuilder({
    mode,
    role,
    assignment,
    version,
    action,
    method,
    publishAction,
    allocateAction,
    submitLabel,
    modeLabel,
    assignmentTypeOptions,
    difficultyLevelOptions,
    feedbackModeOptions,
    scoringMethodOptions,
    lateSubmissionPolicyOptions,
    questionTypeOptions,
    builderOptions,
    validation,
}: BuilderProps) {
    const base = role === 'administrator' ? 'admin' : 'teacher';
    const breadcrumbs: BreadcrumbItem[] = [
        { title: role === 'administrator' ? 'Administrator Dashboard' : 'Teacher Dashboard', href: `/${base}/dashboard` },
        { title: 'Assignments', href: `/${base}/assignments` },
        { title: modeLabel, href: `/${base}/assignments` },
    ];

    const initialItems = version?.items?.length ? version.items : [defaultItem(1)];
    const { data, setData, post, put, processing, errors } = useForm<FormValues>({
        assignment_type: assignment?.assignment_type ?? version?.assignment_type ?? 'mission',
        title: version?.title ?? '',
        short_description: version?.short_description ?? '',
        child_instructions: version?.child_instructions ?? '',
        teacher_instructions: version?.teacher_instructions ?? '',
        audio_instruction_path: version?.audio_instruction_path ?? '',
        estimated_minutes: String(version?.estimated_minutes ?? 10),
        difficulty_level: version?.difficulty_level ?? 'introductory',
        default_attempt_limit: String(version?.default_attempt_limit ?? 1),
        feedback_mode: version?.feedback_mode ?? 'after_submission',
        scoring_method: version?.scoring_method ?? 'latest_attempt',
        settings: version?.settings ?? {},
        items: initialItems,
        curriculum_links: version?.curriculum_links?.length
            ? version.curriculum_links.map((link) => ({ curriculum_world_id: Number(link.curriculum_world_id) || null }))
            : [],
        skill_ids: version?.skills?.map((skill) => skill.id) ?? [],
    });

    const allocationForm = useForm({
        target_type: 'class',
        class_id: builderOptions.classes[0]?.id ? String(builderOptions.classes[0].id) : '',
        group_id: '',
        child_id: '',
        available_from: '',
        due_at: '',
        closes_at: '',
        attempt_limit: String(version?.default_attempt_limit ?? 1),
        scoring_method: version?.scoring_method ?? 'latest_attempt',
        show_score_to_child: true,
        show_correct_answers: false,
        allow_late_submission: false,
        late_submission_policy: 'block',
    });

    const totalPoints = useMemo(() => data.items.reduce((sum, item) => sum + Number(item.points || 0), 0), [data.items]);
    const isPublished = version?.status === 'published';

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        method === 'post' ? post(action) : put(action);
    };

    const updateItem = (index: number, patch: Partial<AssignmentItem>) => {
        const next = [...data.items];
        next[index] = { ...next[index], ...patch };
        setData('items', resequence(next));
    };

    const updateOption = (itemIndex: number, optionIndex: number, patch: Partial<AssignmentItemOption>) => {
        const next = [...data.items];
        const options = [...(next[itemIndex].options ?? [])];
        options[optionIndex] = { ...options[optionIndex], ...patch };
        next[itemIndex] = { ...next[itemIndex], options };
        setData('items', next);
    };

    const addItem = (type = 'multiple_choice') => setData('items', [...data.items, defaultItem(data.items.length + 1, type)]);
    const removeItem = (index: number) => setData('items', resequence(data.items.filter((_, itemIndex) => itemIndex !== index)));
    const duplicateItem = (index: number) =>
        setData('items', resequence([...data.items, { ...data.items[index], title: `${data.items[index].title} copy` }]));
    const moveItem = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= data.items.length) return;
        const next = [...data.items];
        [next[index], next[target]] = [next[target], next[index]];
        setData('items', resequence(next));
    };

    const allocate = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!allocateAction) return;

        allocationForm.transform((payload) => ({
            available_from: payload.available_from || null,
            due_at: payload.due_at || null,
            closes_at: payload.closes_at || null,
            attempt_limit: payload.attempt_limit,
            scoring_method: payload.scoring_method,
            show_score_to_child: payload.show_score_to_child,
            show_correct_answers: payload.show_correct_answers,
            allow_late_submission: payload.allow_late_submission,
            late_submission_policy: payload.late_submission_policy,
            class_id: payload.target_type === 'class' ? payload.class_id : null,
            group_id: payload.target_type === 'group' ? payload.group_id : null,
            child_id: payload.target_type === 'child' ? payload.child_id : null,
        }));
        allocationForm.post(allocateAction);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mode === 'create' ? 'Create Assignment' : `${version?.title ?? 'Assignment'} | Builder`} />

            <div className="space-y-6 p-4 md:p-6">
                <section className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="space-y-2">
                        <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">{modeLabel}</p>
                        <h1 className="font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)]">
                            {version?.title || 'Create a child-friendly mission'}
                        </h1>
                        <p className="text-muted-foreground max-w-3xl">
                            Build short activities, publish stable versions and allocate the latest published version to the right learners.
                        </p>
                    </div>
                    <Button asChild variant="outline" className="h-11 rounded-full px-5 text-sm font-semibold">
                        <Link href={route(`${base}.assignments.index`)}>
                            <ArrowLeft className="h-4 w-4" />
                            Back to library
                        </Link>
                    </Button>
                </section>

                <form onSubmit={submit} className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-6">
                        <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader>
                                <CardTitle className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    Mission details
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-5 md:grid-cols-2">
                                <Field label="Assignment title" error={errors.title}>
                                    <Input
                                        value={data.title}
                                        onChange={(event) => setData('title', event.target.value)}
                                        placeholder="Keyboard key quest"
                                    />
                                </Field>
                                <Field label="Assignment type" error={errors.assignment_type}>
                                    <SimpleSelect
                                        value={data.assignment_type}
                                        options={assignmentTypeOptions}
                                        onChange={(value) => setData('assignment_type', value)}
                                    />
                                </Field>
                                <Field label="Short description" className="md:col-span-2" error={errors.short_description}>
                                    <Input value={data.short_description} onChange={(event) => setData('short_description', event.target.value)} />
                                </Field>
                                <Field label="Child instructions" className="md:col-span-2" error={errors.child_instructions}>
                                    <Textarea
                                        value={data.child_instructions}
                                        onChange={(event) => setData('child_instructions', event.target.value)}
                                        placeholder="Listen, look carefully, then choose your answer."
                                    />
                                </Field>
                                <Field label="Teacher instructions" className="md:col-span-2" error={errors.teacher_instructions}>
                                    <Textarea
                                        value={data.teacher_instructions}
                                        onChange={(event) => setData('teacher_instructions', event.target.value)}
                                    />
                                </Field>
                                <Field label="Audio instruction path">
                                    <Input
                                        value={data.audio_instruction_path}
                                        onChange={(event) => setData('audio_instruction_path', event.target.value)}
                                    />
                                </Field>
                                <Field label="Estimated minutes">
                                    <Input
                                        type="number"
                                        min="1"
                                        max="240"
                                        value={data.estimated_minutes}
                                        onChange={(event) => setData('estimated_minutes', event.target.value)}
                                    />
                                </Field>
                                <Field label="Difficulty">
                                    <SimpleSelect
                                        value={data.difficulty_level}
                                        options={difficultyLevelOptions}
                                        onChange={(value) => setData('difficulty_level', value)}
                                    />
                                </Field>
                                <Field label="Attempt limit">
                                    <Input
                                        type="number"
                                        min="1"
                                        max="10"
                                        value={data.default_attempt_limit}
                                        onChange={(event) => setData('default_attempt_limit', event.target.value)}
                                    />
                                </Field>
                                <Field label="Feedback timing">
                                    <SimpleSelect
                                        value={data.feedback_mode}
                                        options={feedbackModeOptions}
                                        onChange={(value) => setData('feedback_mode', value)}
                                    />
                                </Field>
                                <Field label="Scoring method">
                                    <SimpleSelect
                                        value={data.scoring_method}
                                        options={scoringMethodOptions}
                                        onChange={(value) => setData('scoring_method', value)}
                                    />
                                </Field>
                            </CardContent>
                        </Card>

                        <section className="space-y-4">
                            <div className="flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Activities</p>
                                    <h2 className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                        Questions and mission steps
                                    </h2>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button type="button" variant="outline" className="h-10 rounded-full" onClick={() => addItem('multiple_choice')}>
                                        <Plus className="h-4 w-4" />
                                        Choice
                                    </Button>
                                    <Button type="button" variant="outline" className="h-10 rounded-full" onClick={() => addItem('type_word')}>
                                        <Plus className="h-4 w-4" />
                                        Typing
                                    </Button>
                                    <Button type="button" variant="outline" className="h-10 rounded-full" onClick={() => addItem('match_items')}>
                                        <Plus className="h-4 w-4" />
                                        Matching
                                    </Button>
                                </div>
                            </div>

                            {data.items.map((item, index) => (
                                <Card
                                    key={`${item.display_order}-${index}`}
                                    className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]"
                                >
                                    <CardHeader className="space-y-3">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <StatusPill value={`Step ${index + 1}`} tone="sky" />
                                            <div className="flex gap-2">
                                                <IconButton
                                                    label="Move up"
                                                    disabled={index === 0}
                                                    onClick={() => moveItem(index, -1)}
                                                    icon={<ArrowUp className="h-4 w-4" />}
                                                />
                                                <IconButton
                                                    label="Move down"
                                                    disabled={index === data.items.length - 1}
                                                    onClick={() => moveItem(index, 1)}
                                                    icon={<ArrowDown className="h-4 w-4" />}
                                                />
                                                <IconButton
                                                    label="Duplicate"
                                                    onClick={() => duplicateItem(index)}
                                                    icon={<Copy className="h-4 w-4" />}
                                                />
                                                <IconButton
                                                    label="Remove"
                                                    disabled={data.items.length === 1}
                                                    onClick={() => removeItem(index)}
                                                    icon={<Trash2 className="h-4 w-4" />}
                                                />
                                            </div>
                                        </div>
                                        <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                            {item.title || `Step ${index + 1}`}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="grid gap-5 md:grid-cols-2">
                                        <Field label="Step title">
                                            <Input value={item.title} onChange={(event) => updateItem(index, { title: event.target.value })} />
                                        </Field>
                                        <Field label="Question type">
                                            <SimpleSelect
                                                value={item.question_type}
                                                options={questionTypeOptions}
                                                onChange={(value) =>
                                                    updateItem(index, {
                                                        question_type: value,
                                                        options: defaultOptions(value),
                                                        grading_configuration:
                                                            value.startsWith('type') || value.includes('html')
                                                                ? { accepted_answers: [''], trim_spaces: true, case_sensitive: false }
                                                                : {},
                                                    })
                                                }
                                            />
                                        </Field>
                                        <Field label="Prompt" className="md:col-span-2">
                                            <Textarea
                                                value={item.prompt_text ?? ''}
                                                onChange={(event) => updateItem(index, { prompt_text: event.target.value })}
                                            />
                                        </Field>
                                        <Field label="Published HTML exercise">
                                            <SimpleSelect
                                                value={item.html_exercise_version_id ? String(item.html_exercise_version_id) : 'none'}
                                                options={[
                                                    { value: 'none', label: 'No HTML exercise attached' },
                                                    ...builderOptions.htmlExercises.map((exercise) => ({
                                                        value: String(exercise.id),
                                                        label: `${exercise.title} · v${exercise.version}`,
                                                    })),
                                                ]}
                                                onChange={(value) =>
                                                    updateItem(index, {
                                                        html_exercise_version_id: value === 'none' ? null : Number(value),
                                                        project_template_version_id: value === 'none' ? item.project_template_version_id : null,
                                                    })
                                                }
                                            />
                                        </Field>
                                        <Field label="Published webpage project">
                                            <SimpleSelect
                                                value={item.project_template_version_id ? String(item.project_template_version_id) : 'none'}
                                                options={[
                                                    { value: 'none', label: 'No project template attached' },
                                                    ...builderOptions.projectTemplates.map((template) => ({
                                                        value: String(template.id),
                                                        label: `${template.title} · v${template.version}`,
                                                    })),
                                                ]}
                                                onChange={(value) =>
                                                    updateItem(index, {
                                                        project_template_version_id: value === 'none' ? null : Number(value),
                                                        html_exercise_version_id: value === 'none' ? item.html_exercise_version_id : null,
                                                    })
                                                }
                                            />
                                        </Field>
                                        <Field label="Points">
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                value={String(item.points)}
                                                onChange={(event) => updateItem(index, { points: Number(event.target.value) })}
                                            />
                                        </Field>
                                        <Field label="Hint">
                                            <Input
                                                value={item.hint_text ?? ''}
                                                onChange={(event) => updateItem(index, { hint_text: event.target.value })}
                                            />
                                        </Field>
                                        {isTypingType(item.question_type) ? (
                                            <Field label="Accepted answers" className="md:col-span-2">
                                                <Input
                                                    value={acceptedAnswers(item).join(', ')}
                                                    onChange={(event) =>
                                                        updateItem(index, {
                                                            grading_configuration: {
                                                                ...(item.grading_configuration ?? {}),
                                                                accepted_answers: event.target.value.split(',').map((value) => value.trim()),
                                                            },
                                                        })
                                                    }
                                                    placeholder="Ada, ada"
                                                />
                                            </Field>
                                        ) : item.question_type === 'short_child_response' ||
                                          item.question_type === 'creative_project' ||
                                          item.question_type === 'teacher_observation' ? (
                                            <div className="rounded-[1.35rem] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 md:col-span-2">
                                                This step is teacher reviewed and does not need a stored correct answer.
                                            </div>
                                        ) : (
                                            <div className="space-y-3 md:col-span-2">
                                                <div className="flex items-center justify-between gap-3">
                                                    <Label className="text-sm font-semibold">Answer options</Label>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        className="h-9 rounded-full"
                                                        onClick={() =>
                                                            updateItem(index, {
                                                                options: [
                                                                    ...(item.options ?? []),
                                                                    {
                                                                        option_text: '',
                                                                        option_value: '',
                                                                        matching_key: '',
                                                                        is_correct: false,
                                                                        display_order: (item.options?.length ?? 0) + 1,
                                                                    },
                                                                ],
                                                            })
                                                        }
                                                    >
                                                        <Plus className="h-4 w-4" />
                                                        Add option
                                                    </Button>
                                                </div>
                                                {(item.options ?? []).map((option, optionIndex) => (
                                                    <div
                                                        key={optionIndex}
                                                        className="grid gap-3 rounded-[1.35rem] border border-slate-200 bg-slate-50 p-3 md:grid-cols-[1fr_1fr_auto]"
                                                    >
                                                        <Input
                                                            value={option.option_text ?? ''}
                                                            onChange={(event) =>
                                                                updateOption(index, optionIndex, { option_text: event.target.value })
                                                            }
                                                            placeholder="Label"
                                                        />
                                                        <Input
                                                            value={option.matching_key ?? option.option_value ?? ''}
                                                            onChange={(event) =>
                                                                updateOption(
                                                                    index,
                                                                    optionIndex,
                                                                    isMatchingType(item.question_type)
                                                                        ? {
                                                                              matching_key: event.target.value,
                                                                              option_value: option.option_value ?? option.option_text ?? '',
                                                                          }
                                                                        : { option_value: event.target.value },
                                                                )
                                                            }
                                                            placeholder={isMatchingType(item.question_type) ? 'Matching answer' : 'Value'}
                                                        />
                                                        <label className="flex min-h-10 items-center gap-2 rounded-xl bg-white px-3 text-sm font-semibold">
                                                            <Checkbox
                                                                checked={Boolean(option.is_correct)}
                                                                onCheckedChange={(checked) =>
                                                                    updateOption(index, optionIndex, { is_correct: Boolean(checked) })
                                                                }
                                                            />
                                                            Correct
                                                        </label>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </section>
                    </div>

                    <aside className="space-y-4">
                        <Card className="rounded-[2rem] border-white/80 bg-[#fffaf2] shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader>
                                <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                    Publish readiness
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm leading-7 text-slate-700">
                                <div className="grid grid-cols-2 gap-3">
                                    <Info label="Version" value={version ? `v${version.version_number}` : 'Draft'} />
                                    <Info label="Points" value={String(totalPoints)} />
                                    <Info label="Items" value={String(data.items.length)} />
                                    <Info label="Status" value={version?.status ?? 'draft'} />
                                </div>
                                {validation.is_publishable ? (
                                    <div className="rounded-[1.35rem] border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                                        Ready to publish.
                                    </div>
                                ) : (
                                    <div className="rounded-[1.35rem] border border-amber-200 bg-amber-50 p-4 text-amber-950">
                                        <p className="font-semibold">Publication checks</p>
                                        {Object.entries(validation.messages).length === 0 ? (
                                            <p className="mt-1">Save a complete draft to run validation.</p>
                                        ) : (
                                            <ul className="mt-2 space-y-1">
                                                {Object.entries(validation.messages)
                                                    .slice(0, 6)
                                                    .map(([key, messages]) => (
                                                        <li key={key}>{messages.join(' ')}</li>
                                                    ))}
                                            </ul>
                                        )}
                                    </div>
                                )}
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="h-12 w-full rounded-full text-base font-semibold shadow-lg shadow-emerald-500/20"
                                >
                                    <Save className="h-4 w-4" />
                                    {submitLabel}
                                </Button>
                                {publishAction && (
                                    <Button
                                        type="button"
                                        disabled={!validation.is_publishable || isPublished}
                                        variant="secondary"
                                        className="h-12 w-full rounded-full text-base font-semibold"
                                        onClick={() => router.post(publishAction)}
                                    >
                                        <FileCheck2 className="h-4 w-4" />
                                        Publish version
                                    </Button>
                                )}
                                <Button type="button" variant="outline" className="h-12 w-full rounded-full text-base font-semibold">
                                    <Eye className="h-4 w-4" />
                                    Preview as child
                                </Button>
                            </CardContent>
                        </Card>

                        <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                            <CardHeader>
                                <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                    Curriculum and skills
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Field label="Learning world">
                                    <SimpleSelect
                                        value={String(data.curriculum_links[0]?.curriculum_world_id ?? 'none')}
                                        options={[
                                            { value: 'none', label: 'No world link' },
                                            ...builderOptions.worlds.map((world) => ({
                                                value: String(world.id),
                                                label: `${world.number ?? ''} ${world.name}`.trim(),
                                            })),
                                        ]}
                                        onChange={(value) =>
                                            setData('curriculum_links', value === 'none' ? [] : [{ curriculum_world_id: Number(value) }])
                                        }
                                    />
                                </Field>
                                <div className="space-y-2">
                                    <Label className="text-sm font-semibold">Skills practised</Label>
                                    <div className="max-h-56 space-y-2 overflow-auto rounded-[1.35rem] border border-slate-200 bg-slate-50 p-3">
                                        {builderOptions.skills.map((skill) => (
                                            <label key={skill.id} className="flex min-h-10 items-center gap-3 rounded-xl bg-white px-3 text-sm">
                                                <Checkbox
                                                    checked={data.skill_ids.includes(skill.id)}
                                                    onCheckedChange={(checked) =>
                                                        setData(
                                                            'skill_ids',
                                                            checked ? [...data.skill_ids, skill.id] : data.skill_ids.filter((id) => id !== skill.id),
                                                        )
                                                    }
                                                />
                                                <span>{skill.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {allocateAction ? (
                            <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                                <CardHeader>
                                    <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                                        Allocate mission
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {isPublished ? (
                                        <form onSubmit={allocate} className="space-y-4">
                                            <Field label="Target type">
                                                <SimpleSelect
                                                    value={allocationForm.data.target_type}
                                                    options={[
                                                        { value: 'class', label: 'Class' },
                                                        { value: 'group', label: 'Learner group' },
                                                        { value: 'child', label: 'Individual child' },
                                                    ]}
                                                    onChange={(value) => allocationForm.setData('target_type', value)}
                                                />
                                            </Field>
                                            {allocationForm.data.target_type === 'class' && (
                                                <Field label="Class">
                                                    <SimpleSelect
                                                        value={allocationForm.data.class_id}
                                                        options={builderOptions.classes.map((item) => ({ value: String(item.id), label: item.name }))}
                                                        onChange={(value) => allocationForm.setData('class_id', value)}
                                                    />
                                                </Field>
                                            )}
                                            {allocationForm.data.target_type === 'group' && (
                                                <Field label="Group">
                                                    <SimpleSelect
                                                        value={allocationForm.data.group_id}
                                                        options={builderOptions.groups.map((item) => ({ value: String(item.id), label: item.name }))}
                                                        onChange={(value) => allocationForm.setData('group_id', value)}
                                                    />
                                                </Field>
                                            )}
                                            {allocationForm.data.target_type === 'child' && (
                                                <Field label="Learner">
                                                    <SimpleSelect
                                                        value={allocationForm.data.child_id}
                                                        options={builderOptions.children.map((item) => ({
                                                            value: String(item.id),
                                                            label: item.name,
                                                        }))}
                                                        onChange={(value) => allocationForm.setData('child_id', value)}
                                                    />
                                                </Field>
                                            )}
                                            <Field label="Available from">
                                                <Input
                                                    type="datetime-local"
                                                    value={allocationForm.data.available_from}
                                                    onChange={(event) => allocationForm.setData('available_from', event.target.value)}
                                                />
                                            </Field>
                                            <Field label="Due at">
                                                <Input
                                                    type="datetime-local"
                                                    value={allocationForm.data.due_at}
                                                    onChange={(event) => allocationForm.setData('due_at', event.target.value)}
                                                />
                                            </Field>
                                            <Field label="Closes at">
                                                <Input
                                                    type="datetime-local"
                                                    value={allocationForm.data.closes_at}
                                                    onChange={(event) => allocationForm.setData('closes_at', event.target.value)}
                                                />
                                            </Field>
                                            <Field label="Attempt limit">
                                                <Input
                                                    type="number"
                                                    min="1"
                                                    max="10"
                                                    value={allocationForm.data.attempt_limit}
                                                    onChange={(event) => allocationForm.setData('attempt_limit', event.target.value)}
                                                />
                                            </Field>
                                            <Field label="Late policy">
                                                <SimpleSelect
                                                    value={allocationForm.data.late_submission_policy}
                                                    options={lateSubmissionPolicyOptions}
                                                    onChange={(value) => allocationForm.setData('late_submission_policy', value)}
                                                />
                                            </Field>
                                            <Button
                                                type="submit"
                                                disabled={allocationForm.processing}
                                                className="h-12 w-full rounded-full text-base font-semibold"
                                            >
                                                <Send className="h-4 w-4" />
                                                Allocate
                                            </Button>
                                        </form>
                                    ) : (
                                        <EmptyState title="Publish first" description="Only published versions can be allocated to children." />
                                    )}
                                </CardContent>
                            </Card>
                        ) : null}
                    </aside>
                </form>
            </div>
        </AppLayout>
    );
}

function resequence(items: AssignmentItem[]) {
    return items.map((item, index) => ({ ...item, display_order: index + 1 }));
}

function acceptedAnswers(item: AssignmentItem) {
    const answers = item.grading_configuration?.accepted_answers;
    return Array.isArray(answers) ? answers.map(String) : [];
}

function isTypingType(type: string) {
    return type.startsWith('type') || type.includes('html') || type.includes('javascript') || type.includes('symbol') || type.includes('code_error');
}

function isMatchingType(type: string) {
    return type === 'match_items' || type === 'match_opening_and_closing_html_tags' || type === 'match_symbol_to_name';
}

function Field({ label, error, className, children }: { label: string; error?: string; className?: string; children: React.ReactNode }) {
    return (
        <div className={cn('space-y-2', className)}>
            <Label className="text-sm font-semibold text-[var(--foreground)]">{label}</Label>
            {children}
            {error ? <p className="text-sm font-medium text-red-600">{error}</p> : null}
        </div>
    );
}

function SimpleSelect({ value, options, onChange }: { value: string; options: SelectOption[]; onChange: (value: string) => void }) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger>
                <SelectValue placeholder="Choose" />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function IconButton({ label, icon, disabled, onClick }: { label: string; icon: React.ReactNode; disabled?: boolean; onClick: () => void }) {
    return (
        <Button
            type="button"
            variant="outline"
            size="icon"
            disabled={disabled}
            className="h-10 w-10 rounded-full"
            aria-label={label}
            onClick={onClick}
        >
            {icon}
        </Button>
    );
}

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[1.25rem] border border-slate-200 bg-white p-3">
            <p className="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">{label}</p>
            <p className="mt-1 text-sm font-bold text-[var(--foreground)]">{value}</p>
        </div>
    );
}
