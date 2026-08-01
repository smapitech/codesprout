import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

export default function HtmlAdminShow({ exercise, actions }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'HTML Engine', href: '/admin/html' }]}>
            <Head title={exercise.title} />
            <main className="space-y-6 p-4 md:p-6">
                <Card className="rounded-[2rem]">
                    <CardHeader>
                        <CardTitle className="font-[family-name:var(--font-display)] text-3xl font-black">{exercise.title}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-slate-600">{exercise.childInstructions}</p>
                        <p className="text-sm font-semibold text-slate-500">
                            {exercise.type} · {exercise.status} · tag policy {exercise.tagPolicy}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild className="rounded-full">
                                <Link href={actions.edit}>Create new version</Link>
                            </Button>
                            {actions.publish && (
                                <Button asChild variant="outline" className="rounded-full">
                                    <Link method="post" href={actions.publish}>
                                        Publish
                                    </Link>
                                </Button>
                            )}
                            <Button asChild variant="outline" className="rounded-full">
                                <Link method="post" href={actions.archive}>
                                    Archive
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
