import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

export default function TeacherTypingIndex({ exercises }: { exercises: any[] }) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
                { title: 'Typing Library', href: '/teacher/typing' },
            ]}
        >
            <Head title="Typing Library" />
            <main className="space-y-6 p-4 md:p-6">
                <h1 className="font-[family-name:var(--font-display)] text-3xl font-black">Typing practice library</h1>
                <section className="grid gap-4 lg:grid-cols-2">
                    {exercises.map((exercise) => (
                        <Card key={exercise.slug} className="rounded-[2rem]">
                            <CardHeader>
                                <CardTitle>{exercise.title}</CardTitle>
                                <p className="text-sm font-bold text-emerald-700">{exercise.type}</p>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <p className="text-slate-600">{exercise.instructions}</p>
                                <Button asChild className="rounded-full">
                                    <Link href={exercise.previewHref}>Teacher Preview</Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </section>
            </main>
        </AppLayout>
    );
}
