import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function TeacherTypingResults({ results }: { results: any[] }) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
                { title: 'Typing Results', href: '/teacher/typing/results' },
            ]}
        >
            <Head title="Typing Results" />
            <main className="space-y-6 p-4 md:p-6">
                <h1 className="font-[family-name:var(--font-display)] text-3xl font-black">Typing progress</h1>
                <section className="grid gap-3">
                    {results.map((result) => (
                        <Card key={result.id} className="rounded-2xl">
                            <CardHeader>
                                <CardTitle>{result.child}</CardTitle>
                                <p className="text-sm text-slate-600">{result.exercise}</p>
                            </CardHeader>
                            <CardContent className="grid gap-3 md:grid-cols-4">
                                <p>First try: {result.firstAttemptAccuracy}%</p>
                                <p>Final text: {result.finalTextAccuracy}%</p>
                                <p>Input: {result.inputMethod.replaceAll('_', ' ')}</p>
                                <p>{result.message}</p>
                            </CardContent>
                        </Card>
                    ))}
                </section>
            </main>
        </AppLayout>
    );
}
