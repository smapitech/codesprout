import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';

interface Option {
    value: string;
    label: string;
}

interface Props {
    mode: 'create' | 'edit';
    game?: {
        name: string;
        description?: string;
        instructions?: string;
        currentVersion?: {
            configuration: Record<string, unknown>;
            difficulty_configuration: Record<string, unknown>;
            supported_input_methods: string[];
        } | null;
    } | null;
    action: string;
    categoryOptions: Option[];
    typeOptions: Option[];
}

export default function GameForm({ mode, game, action, categoryOptions, typeOptions }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: game?.name ?? '',
        category: categoryOptions[0]?.value ?? '',
        game_type: typeOptions[0]?.value ?? '',
        description: game?.description ?? '',
        instructions: game?.instructions ?? '',
        configuration: JSON.stringify(
            game?.currentVersion?.configuration ?? {
                items: [{ name: 'Screen', value: 'screen', purpose: 'Shows pictures and words' }],
                round_count: 1,
            },
            null,
            2,
        ),
        supported_input_methods: ['mouse', 'touch', 'keyboard'],
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const payload = {
            ...data,
            configuration: JSON.parse(data.configuration || '{}'),
            instruction_content: { written: data.instructions, speech_enabled: true },
            difficulty_configuration: {},
        };
        mode === 'create' ? router.post(action, payload) : router.put(action, payload);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Administrator Dashboard', href: '/admin/dashboard' },
        { title: 'Games', href: '/admin/games' },
        { title: mode === 'create' ? 'Create' : 'Edit', href: action },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mode === 'create' ? 'Create Game' : 'Edit Game'} />
            <main className="p-4 md:p-6">
                <Card className="mx-auto max-w-4xl rounded-[2rem]">
                    <CardHeader>
                        <CardTitle className="font-[family-name:var(--font-display)] text-3xl font-black">
                            {mode === 'create' ? 'Create safe game definition' : 'Create new draft version'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-5">
                            <Input value={data.name} onChange={(event) => setData('name', event.target.value)} placeholder="Game name" />
                            <textarea
                                className="min-h-24 w-full rounded-2xl border p-3"
                                value={data.instructions}
                                onChange={(event) => setData('instructions', event.target.value)}
                                placeholder="Child-facing instruction"
                            />
                            <div className="grid gap-4 md:grid-cols-2">
                                <select
                                    className="h-12 rounded-2xl border px-3"
                                    value={data.category}
                                    onChange={(event) => setData('category', event.target.value)}
                                >
                                    {categoryOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <select
                                    className="h-12 rounded-2xl border px-3"
                                    value={data.game_type}
                                    onChange={(event) => setData('game_type', event.target.value)}
                                >
                                    {typeOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <textarea
                                className="min-h-56 w-full rounded-2xl border bg-slate-950 p-4 font-mono text-sm text-slate-100"
                                value={data.configuration}
                                onChange={(event) => setData('configuration', event.target.value)}
                            />
                            {Object.keys(errors).length ? (
                                <p className="text-sm font-bold text-red-700">Please check the game configuration.</p>
                            ) : null}
                            <Button disabled={processing} className="h-12 rounded-full px-6 font-bold">
                                Save draft
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
