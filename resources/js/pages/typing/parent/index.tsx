import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function ParentTypingIndex({ results }: { results: any[] }) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Parent Dashboard', href: '/parent/dashboard' },
                { title: 'Typing Growth', href: '/parent/typing' },
            ]}
        >
            <Head title="Typing Growth" />
            <main className="space-y-6 p-4 md:p-6">
                <section>
                    <p className="text-sm font-black tracking-[0.2em] text-emerald-700 uppercase">CodeSprout typing</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black">Typing growth summary</h1>
                    <p className="mt-2 max-w-3xl text-slate-600">
                        Accuracy shows how often your child chooses the correct key. Speed appears only when there is enough practice data to measure
                        fairly.
                    </p>
                </section>
                <section className="grid gap-4">
                    {results.map((result) => (
                        <Card key={result.id} className="rounded-[2rem]">
                            <CardHeader>
                                <CardTitle>{result.child}</CardTitle>
                                <p className="text-sm text-slate-600">{result.exercise}</p>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <p>First-attempt accuracy: {result.firstAttemptAccuracy}%</p>
                                <p>Final accuracy: {result.finalTextAccuracy}%</p>
                                <p>{result.message}</p>
                            </CardContent>
                        </Card>
                    ))}
                </section>
            </main>
        </AppLayout>
    );
}
