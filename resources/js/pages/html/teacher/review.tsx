import { SafePreview } from '@/components/html/safe-preview';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';

export default function TeacherHtmlReview({ project, action }: any) {
    const form = useForm({
        review_status: 'approved',
        child_feedback: 'Wonderful work. Your webpage is safely built and ready to share with your family.',
        teacher_only_notes: '',
        release_to_parent: true,
        rubric_result: { safe_structure: true, effort: true },
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(action);
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'HTML Learning', href: '/teacher/html' }]}>
            <Head title={`Review ${project.title}`} />
            <main className="grid gap-5 p-4 md:p-6 xl:grid-cols-[1fr_0.8fr]">
                <SafePreview html={project.sanitisedHtml ?? ''} title={`${project.title} preview`} />
                <Card className="rounded-[2rem]">
                    <CardHeader>
                        <CardTitle className="font-[family-name:var(--font-display)] text-3xl font-black">Review for {project.child}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-4" onSubmit={submit}>
                            <div className="space-y-2">
                                <Label htmlFor="child_feedback">Child-friendly feedback</Label>
                                <Textarea
                                    id="child_feedback"
                                    value={form.data.child_feedback}
                                    onChange={(event) => form.setData('child_feedback', event.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="teacher_only_notes">Teacher-only notes</Label>
                                <Textarea
                                    id="teacher_only_notes"
                                    value={form.data.teacher_only_notes}
                                    onChange={(event) => form.setData('teacher_only_notes', event.target.value)}
                                />
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button type="submit" onClick={() => form.setData('review_status', 'approved')} className="rounded-full font-bold">
                                    Approve project
                                </Button>
                                <Button
                                    type="submit"
                                    onClick={() => form.setData('review_status', 'changes_requested')}
                                    variant="outline"
                                    className="rounded-full font-bold"
                                >
                                    Request changes
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
