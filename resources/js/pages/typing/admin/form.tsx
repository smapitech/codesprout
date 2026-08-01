import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';

interface Option {
    value: string;
    label: string;
}

interface Props {
    mode: 'create' | 'edit';
    exercise?: any;
    action: string;
    typeOptions: Option[];
    difficultyProfiles: { id: number; name: string }[];
}

export default function TypingForm({ mode, exercise, action, typeOptions, difficultyProfiles }: Props) {
    const current = exercise?.currentVersionData;
    const form = useForm({
        title: exercise?.title ?? '',
        exercise_type: typeOptions[0]?.value ?? 'word_typing',
        description: exercise?.description ?? '',
        child_instructions: exercise?.childInstructions ?? '',
        teacher_instructions: exercise?.teacherInstructions ?? '',
        typing_difficulty_profile_id: '',
        case_sensitive: current?.caseSensitive ?? 'case_insensitive',
        backspace_policy: current?.backspacePolicy ?? 'allowed',
        correction_policy: current?.correctionPolicy ?? 'allowed',
        accuracy_requirement: current?.accuracyRequirement ?? 0,
        content_configuration: { minimum_items: 1, feedback_message: 'Wonderful careful typing!' },
        completion_criteria: { minimum_items: 1, minimum_accuracy: 0, allow_pause: true },
        items: current?.items?.length ? current.items : [{ prompt_text: 'Type cat.', expected_text: 'cat', target_keys: ['c', 'a', 't'] }],
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        mode === 'create' ? form.post(action) : form.put(action);
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Typing Engine', href: '/admin/typing' }]}>
            <Head title={mode === 'create' ? 'Create Typing Exercise' : 'Edit Typing Exercise'} />
            <main className="p-4 md:p-6">
                <Card className="mx-auto max-w-4xl rounded-[2rem]">
                    <CardHeader>
                        <CardTitle className="font-[family-name:var(--font-display)] text-3xl font-black">
                            {mode === 'create' ? 'Create typing exercise' : 'Create a new draft version'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-5" onSubmit={submit}>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="title">Title</Label>
                                    <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Exercise type</Label>
                                    <Select value={form.data.exercise_type} onValueChange={(value) => form.setData('exercise_type', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {typeOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="child_instructions">Child instructions</Label>
                                <Textarea
                                    id="child_instructions"
                                    value={form.data.child_instructions}
                                    onChange={(e) => form.setData('child_instructions', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Difficulty profile</Label>
                                    <Select
                                        value={String(form.data.typing_difficulty_profile_id)}
                                        onValueChange={(value) => form.setData('typing_difficulty_profile_id', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Choose a profile" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {difficultyProfiles.map((profile) => (
                                                <SelectItem key={profile.id} value={String(profile.id)}>
                                                    {profile.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="accuracy_requirement">Accuracy target</Label>
                                    <Input
                                        id="accuracy_requirement"
                                        type="number"
                                        value={form.data.accuracy_requirement}
                                        onChange={(e) => form.setData('accuracy_requirement', Number(e.target.value))}
                                    />
                                </div>
                            </div>
                            <div className="space-y-3 rounded-2xl bg-[#fff8ea] p-4">
                                <Label>Starter prompts</Label>
                                {form.data.items.map((item: { prompt_text: string; expected_text: string }, index: number) => (
                                    <div key={index} className="grid gap-3 md:grid-cols-2">
                                        <Input
                                            aria-label={`Prompt ${index + 1}`}
                                            value={item.prompt_text}
                                            onChange={(e) => {
                                                const items = [...form.data.items];
                                                items[index] = { ...items[index], prompt_text: e.target.value };
                                                form.setData('items', items);
                                            }}
                                        />
                                        <Input
                                            aria-label={`Expected answer ${index + 1}`}
                                            value={item.expected_text}
                                            onChange={(e) => {
                                                const items = [...form.data.items];
                                                items[index] = { ...items[index], expected_text: e.target.value };
                                                form.setData('items', items);
                                            }}
                                        />
                                    </div>
                                ))}
                            </div>
                            <Button type="submit" className="rounded-full px-6 font-bold" disabled={form.processing}>
                                Save safe draft
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
