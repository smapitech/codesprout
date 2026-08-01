import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';

export default function HtmlAdminForm({ mode, kind, action, exercise, typeOptions, tagPolicies }: any) {
    const current = exercise?.currentVersionData;
    const form = useForm(
        kind === 'template'
            ? {
                  title: '',
                  description: '',
                  html_tag_policy_id: String(tagPolicies[0]?.id ?? ''),
                  starter_source: '<h1>My First Webpage</h1>\n<p>My webpage is growing.</p>',
                  project_configuration: { mode: 'synced_blocks_code', autosave: true },
                  checklist_configuration: { items: ['Add a heading', 'Add one paragraph', 'Preview safely'] },
              }
            : {
                  title: exercise?.title ?? '',
                  exercise_type: current?.type ?? typeOptions[0]?.value ?? 'heading_builder',
                  description: exercise?.description ?? '',
                  child_instructions: exercise?.childInstructions ?? '',
                  teacher_instructions: exercise?.teacherInstructions ?? '',
                  html_tag_policy_id: String(tagPolicies[0]?.id ?? ''),
                  content_configuration: { starter_html: '<h1>Hello webpage</h1>' },
                  requirements: current?.requirements?.length
                      ? current.requirements
                      : [{ requirement_type: 'tag_exists', tag_name: 'h1', minimum_count: 1, required: true, scoring_weight: 1 }],
              },
    );

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        mode === 'create' ? form.post(action) : form.put(action);
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'HTML Engine', href: '/admin/html' }]}>
            <Head title={kind === 'template' ? 'Project Template' : 'HTML Exercise'} />
            <main className="p-4 md:p-6">
                <Card className="mx-auto max-w-4xl rounded-[2rem]">
                    <CardHeader>
                        <CardTitle className="font-[family-name:var(--font-display)] text-3xl font-black">
                            {kind === 'template'
                                ? 'Create project template'
                                : mode === 'create'
                                  ? 'Create HTML exercise'
                                  : 'Create a new draft version'}
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
                                    <Label>Tag policy</Label>
                                    <Select
                                        value={String(form.data.html_tag_policy_id)}
                                        onValueChange={(value) => form.setData('html_tag_policy_id', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Choose policy" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {tagPolicies.map((policy: any) => (
                                                <SelectItem key={policy.id} value={String(policy.id)}>
                                                    {policy.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            {kind !== 'template' && (
                                <div className="space-y-2">
                                    <Label>Exercise type</Label>
                                    <Select value={form.data.exercise_type} onValueChange={(value) => form.setData('exercise_type', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {typeOptions.map((option: any) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={form.data.description ?? ''}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                />
                            </div>
                            {kind === 'template' ? (
                                <div className="space-y-2">
                                    <Label htmlFor="starter_source">Starter HTML</Label>
                                    <Textarea
                                        id="starter_source"
                                        className="min-h-56 font-mono"
                                        value={form.data.starter_source}
                                        onChange={(e) => form.setData('starter_source', e.target.value)}
                                    />
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    <Label htmlFor="child_instructions">Child instructions</Label>
                                    <Textarea
                                        id="child_instructions"
                                        value={form.data.child_instructions}
                                        onChange={(e) => form.setData('child_instructions', e.target.value)}
                                    />
                                </div>
                            )}
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
