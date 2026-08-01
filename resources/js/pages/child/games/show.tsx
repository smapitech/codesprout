import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { Gamepad2, Volume2 } from 'lucide-react';

interface Props {
    game: {
        name: string;
        description?: string;
        instructions?: string;
        category: string;
        game_type: string;
    };
    actions: {
        start: string;
    };
}

export default function ChildGameShow({ game, actions }: Props) {
    return (
        <main className="min-h-screen bg-[#fff8ea] px-4 py-5 text-[#243443] md:px-8">
            <Head title={game.name} />
            <section className="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[1fr_380px] lg:items-center">
                <div className="space-y-5">
                    <p className="inline-flex rounded-full border border-emerald-300 bg-white px-4 py-2 text-sm font-black tracking-[0.16em] text-emerald-700 uppercase">
                        {game.category}
                    </p>
                    <h1 className="font-[family-name:var(--font-display)] text-4xl font-black md:text-6xl">{game.name}</h1>
                    <p className="max-w-2xl text-lg leading-8 text-slate-700">{game.description}</p>
                    <Card className="rounded-[2rem] border-white/80 bg-white/95">
                        <CardContent className="space-y-4 p-5">
                            <div className="flex items-start gap-3">
                                <div className="rounded-2xl bg-sky-100 p-3 text-sky-700">
                                    <Volume2 className="h-6 w-6" />
                                </div>
                                <div>
                                    <p className="font-black">Listen, read and play safely</p>
                                    <p className="mt-1 text-slate-700">{game.instructions}</p>
                                </div>
                            </div>
                            <Button asChild className="h-14 rounded-full px-8 text-lg font-black">
                                <Link
                                    method="post"
                                    href={actions.start}
                                    data={{ difficulty: 'slow', client_session_identifier: `child-game-${Date.now()}` }}
                                >
                                    <Gamepad2 className="h-5 w-5" />
                                    Start Game
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
                <div className="rounded-[2.5rem] bg-white p-6 text-center shadow-[0_24px_90px_rgba(31,76,58,0.12)]">
                    <div className="mx-auto flex aspect-square max-w-xs items-center justify-center rounded-[2rem] bg-gradient-to-br from-emerald-100 via-sky-100 to-amber-100">
                        <Gamepad2 className="h-24 w-24 text-emerald-700" />
                    </div>
                    <p className="mt-5 text-sm font-bold text-slate-600">Large controls · Keyboard friendly · Touch friendly</p>
                </div>
            </section>
        </main>
    );
}
