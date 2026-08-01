import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Archive, Pencil, Rocket } from 'lucide-react';

interface Props {
    game: {
        slug: string;
        name: string;
        category: string;
        game_type: string;
        status: string;
        description?: string;
        instructions?: string;
        currentVersion?: {
            id: number;
            version_number: number;
            status: string;
            configuration: Record<string, unknown>;
            difficulty_configuration: Record<string, unknown>;
            supported_input_methods: string[];
        } | null;
    };
    actions: {
        edit: string;
        publish: string | null;
        archive: string;
    };
}

export default function GameShow({ game, actions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Administrator Dashboard', href: '/admin/dashboard' },
        { title: 'Games', href: '/admin/games' },
        { title: game.name, href: `/admin/games/${game.slug}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={game.name} />
            <main className="space-y-6 p-4 md:p-6">
                <section className="rounded-[2rem] bg-[#fff8ea] p-6">
                    <p className="text-sm font-black tracking-[0.2em] text-emerald-700 uppercase">{game.category}</p>
                    <h1 className="mt-2 font-[family-name:var(--font-display)] text-4xl font-black text-[var(--foreground)]">{game.name}</h1>
                    <p className="mt-3 max-w-3xl text-slate-700">{game.description}</p>
                    <div className="mt-5 flex flex-wrap gap-3">
                        <Button asChild className="rounded-full">
                            <Link href={actions.edit}>
                                <Pencil className="h-4 w-4" />
                                Edit as new draft
                            </Link>
                        </Button>
                        {actions.publish ? (
                            <Button asChild variant="outline" className="rounded-full">
                                <Link method="post" href={actions.publish}>
                                    <Rocket className="h-4 w-4" />
                                    Publish current version
                                </Link>
                            </Button>
                        ) : null}
                        <Button asChild variant="outline" className="rounded-full">
                            <Link method="post" href={actions.archive}>
                                <Archive className="h-4 w-4" />
                                Archive
                            </Link>
                        </Button>
                    </div>
                </section>

                <Card className="rounded-[2rem]">
                    <CardHeader>
                        <CardTitle>Safe configuration</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-slate-700">{game.instructions}</p>
                        <pre className="overflow-auto rounded-2xl bg-slate-950 p-4 text-sm text-slate-100">
                            {JSON.stringify(game.currentVersion?.configuration ?? {}, null, 2)}
                        </pre>
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
