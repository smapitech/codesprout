import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

export default function TypingShow({ exercise, actions }: { exercise: any; actions: any }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Typing Engine', href: '/admin/typing' }]}>
            <Head title={exercise.title} />
            <main className="space-y-6 p-4 md:p-6">
                <Card className="rounded-[2rem]">
                    <CardHeader>
                        <CardTitle className="font-[family-name:var(--font-display)] text-3xl font-black">{exercise.title}</CardTitle>
                        <p className="font-bold text-emerald-700">{exercise.type}</p>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p>{exercise.childInstructions}</p>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild className="rounded-full">
                                <Link href={actions.edit}>Create draft version</Link>
                            </Button>
                            {actions.publish ? (
                                <Button asChild variant="outline" className="rounded-full">
                                    <Link method="post" href={actions.publish}>
                                        Publish current version
                                    </Link>
                                </Button>
                            ) : null}
                            <Button asChild variant="outline" className="rounded-full">
                                <Link method="post" href={actions.archive}>
                                    Archive
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
                <section className="grid gap-3 md:grid-cols-2">
                    {exercise.currentVersionData?.items?.map((item: any) => (
                        <Card key={item.id} className="rounded-2xl">
                            <CardContent className="p-4">
                                <p className="font-black">{item.prompt_text}</p>
                                <p className="text-sm text-slate-600">
                                    Expected text is stored server-side for scoring and not exposed to child routes.
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </section>
            </main>
        </AppLayout>
    );
}
