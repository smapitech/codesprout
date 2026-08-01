import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import type { FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

interface StatusOption {
    value: string;
    label: string;
}

interface CurriculumFormValues extends Record<string, FormDataConvertible> {
    title: string;
    description: string;
    target_min_age: string;
    target_max_age: string;
    duration_weeks: string;
    lessons_per_week: string;
    version: string;
    status: string;
}

interface CurriculumFormProps {
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    modeLabel: string;
    statusOptions: StatusOption[];
    curriculum?: Partial<CurriculumFormValues> | null;
    className?: string;
}

const defaultValues: CurriculumFormValues = {
    title: '',
    description: '',
    target_min_age: '6',
    target_max_age: '7',
    duration_weeks: '48',
    lessons_per_week: '3',
    version: '1.0.0',
    status: 'draft',
};

export function CurriculumForm({ action, method, submitLabel, modeLabel, statusOptions, curriculum, className }: CurriculumFormProps) {
    const { data, setData, post, put, processing, errors } = useForm<CurriculumFormValues>({
        ...defaultValues,
        ...curriculum,
        description: curriculum?.description ?? '',
        target_min_age: curriculum?.target_min_age ?? defaultValues.target_min_age,
        target_max_age: curriculum?.target_max_age ?? defaultValues.target_max_age,
        duration_weeks: curriculum?.duration_weeks ?? defaultValues.duration_weeks,
        lessons_per_week: curriculum?.lessons_per_week ?? defaultValues.lessons_per_week,
        version: curriculum?.version ?? defaultValues.version,
        status: curriculum?.status ?? defaultValues.status,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (method === 'post') {
            post(action);

            return;
        }

        put(action);
    };

    return (
        <form onSubmit={submit} className={cn('grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]', className)}>
            <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                <CardHeader className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">{modeLabel}</p>
                    <CardTitle className="font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                        Curriculum details
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-5 md:grid-cols-2">
                    <Field label="Title" error={errors.title}>
                        <Input value={data.title} onChange={(event) => setData('title', event.target.value)} />
                    </Field>

                    <Field label="Version" error={errors.version}>
                        <Input value={data.version} onChange={(event) => setData('version', event.target.value)} />
                    </Field>

                    <Field label="Target minimum age" error={errors.target_min_age}>
                        <Input
                            type="number"
                            min="3"
                            max="18"
                            value={data.target_min_age}
                            onChange={(event) => setData('target_min_age', event.target.value)}
                        />
                    </Field>

                    <Field label="Target maximum age" error={errors.target_max_age}>
                        <Input
                            type="number"
                            min="3"
                            max="18"
                            value={data.target_max_age}
                            onChange={(event) => setData('target_max_age', event.target.value)}
                        />
                    </Field>

                    <Field label="Duration in weeks" error={errors.duration_weeks}>
                        <Input
                            type="number"
                            min="1"
                            value={data.duration_weeks}
                            onChange={(event) => setData('duration_weeks', event.target.value)}
                        />
                    </Field>

                    <Field label="Lessons per week" error={errors.lessons_per_week}>
                        <Input
                            type="number"
                            min="1"
                            value={data.lessons_per_week}
                            onChange={(event) => setData('lessons_per_week', event.target.value)}
                        />
                    </Field>

                    <Field label="Publication status" error={errors.status} className="md:col-span-2">
                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                            <SelectTrigger>
                                <SelectValue placeholder="Choose a status" />
                            </SelectTrigger>
                            <SelectContent>
                                {statusOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>

                    <Field label="Description" error={errors.description} className="md:col-span-2">
                        <Textarea
                            rows={6}
                            value={data.description}
                            onChange={(event) => setData('description', event.target.value)}
                            placeholder="Explain the learning journey, curriculum goals and child-friendly approach."
                        />
                    </Field>
                </CardContent>
            </Card>

            <aside className="space-y-4">
                <Card className="rounded-[2rem] border-white/80 bg-[#fffaf2] shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                    <CardHeader className="space-y-2">
                        <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">Notes</p>
                        <CardTitle className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">
                            Keep the structure clear
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm leading-7 text-slate-700">
                        <p>Slugs are generated automatically from the title, so the title should stay human-readable and descriptive.</p>
                        <p>Publishing is validated separately before content becomes visible to teachers or children.</p>
                        <p>Age values are stored as a range so the curriculum can grow without changing the schema.</p>
                    </CardContent>
                </Card>

                <div className="rounded-[2rem] border border-dashed border-emerald-200 bg-emerald-50/60 p-4 text-sm text-emerald-900">
                    This form only updates the curriculum root. Worlds, units, lessons and stages are managed from the builder page.
                </div>

                <Button
                    type="submit"
                    disabled={processing}
                    className="h-12 w-full rounded-full text-base font-semibold shadow-lg shadow-emerald-500/20"
                >
                    {submitLabel}
                </Button>
            </aside>
        </form>
    );
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
