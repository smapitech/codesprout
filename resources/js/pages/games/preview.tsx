import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { RotateCcw, Volume2 } from 'lucide-react';

interface Props {
    preview: boolean;
    game: {
        name: string;
        category: string;
        game_type: string;
        instructions: string;
        supported_input_methods: string[];
        round: {
            prompt: string;
            safe: Record<string, unknown>;
        } | null;
    };
}

export default function GamePreview({ game }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
        { title: 'Games', href: '/teacher/games' },
        { title: 'Preview', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${game.name} Preview`} />
            <main className="space-y-6 p-4 md:p-6">
                <section className="rounded-[2rem] bg-[#fff8ea] p-6">
                    <p className="text-sm font-black tracking-[0.2em] text-emerald-700 uppercase">Teacher Preview</p>
                    <h1 className="font-[family-name:var(--font-display)] text-4xl font-black text-[var(--foreground)]">{game.name}</h1>
                    <p className="mt-3 text-slate-700">{game.instructions}</p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        {game.supported_input_methods.map((method) => (
                            <span key={method} className="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700">
                                {method}
                            </span>
                        ))}
                    </div>
                </section>
                <Card className="rounded-[2rem]">
                    <CardContent className="space-y-5 p-6">
                        <div aria-live="polite" className="rounded-[1.5rem] border-2 border-dashed border-emerald-300 bg-emerald-50 p-6 text-center">
                            <p className="text-sm font-bold text-emerald-700">Sample round</p>
                            <h2 className="mt-2 text-2xl font-black text-[var(--foreground)]">{game.round?.prompt ?? 'No round available'}</h2>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <Button type="button" variant="outline" className="rounded-full">
                                <Volume2 className="h-4 w-4" />
                                Repeat instruction
                            </Button>
                            <Button type="button" variant="outline" className="rounded-full">
                                <RotateCcw className="h-4 w-4" />
                                Reset preview
                            </Button>
                        </div>
                        <p className="text-sm text-slate-600">
                            Preview sessions do not create child progress, rewards, assignment results or game results.
                        </p>
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
