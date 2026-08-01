import { Button } from '@/components/ui/button';
import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowLeft, ArrowRight, ArrowUp, DoorOpen, Lightbulb, Pause, Play, RotateCcw, Volume2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface Payload {
    session: {
        uuid: string;
        status: string;
        difficulty: string;
        current_round: number;
        total_rounds: number;
    };
    game: {
        name: string;
        category: string;
        game_type: string;
        instructions: string;
        supported_input_methods: string[];
    };
    round: {
        prompt: string;
        safe?: Record<string, unknown>;
    } | null;
}

interface Props {
    payload: Payload;
    actions: {
        action: string;
        pause: string;
        resume: string;
        complete: string;
        leave: string;
    };
}

export default function ChildGamePlayer({ payload, actions }: Props) {
    const [feedback, setFeedback] = useState('');
    const [busy, setBusy] = useState(false);
    const [hintUsed, setHintUsed] = useState(false);
    const gameArea = useRef<HTMLDivElement>(null);
    const progress = payload.session.total_rounds > 0 ? Math.round((payload.session.current_round / payload.session.total_rounds) * 100) : 100;

    const submitResponse = (response: Record<string, FormDataConvertible>) => {
        if (busy) return;
        setBusy(true);
        router.post(
            actions.action,
            {
                round_number: payload.session.current_round,
                response,
                response_time_ms: 1500,
                hint_used: hintUsed,
            },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const props = page.props as unknown as { correct?: boolean; feedback?: string; complete?: boolean };
                    setFeedback(props.feedback ?? 'Nice try. Keep going!');
                },
                onFinish: () => setBusy(false),
            },
        );
    };

    useEffect(() => {
        const handleKey = (event: KeyboardEvent) => {
            if (!gameArea.current?.contains(document.activeElement) && document.activeElement !== gameArea.current) return;
            if (event.key === 'Escape') return;

            if (payload.game.game_type.includes('keyboard') || payload.game.game_type === 'falling_letters') {
                event.preventDefault();
                submitResponse({ key: event.key });
            }

            if (payload.game.game_type === 'arrow_key_path' && event.key.startsWith('Arrow')) {
                event.preventDefault();
                submitResponse({ move: event.key });
            }
        };

        window.addEventListener('keydown', handleKey);
        return () => window.removeEventListener('keydown', handleKey);
    });

    return (
        <main className="min-h-screen bg-[#fff8ea] px-3 py-4 text-[#243443] md:px-6">
            <Head title={`${payload.game.name} | Game`} />
            <section className="mx-auto flex min-h-[calc(100vh-2rem)] max-w-6xl flex-col gap-4">
                <header className="rounded-[2rem] border border-white bg-white/95 p-4 shadow-[0_18px_60px_rgba(38,84,63,0.08)]">
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm font-black tracking-[0.2em] text-emerald-700 uppercase">CodeSprout game</p>
                            <h1 className="font-[family-name:var(--font-display)] text-2xl font-black md:text-4xl">{payload.game.name}</h1>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                className="rounded-full"
                                onClick={() => window.speechSynthesis?.speak(new SpeechSynthesisUtterance(payload.game.instructions))}
                            >
                                <Volume2 className="h-4 w-4" />
                                Repeat
                            </Button>
                            <Button asChild variant="outline" className="rounded-full">
                                <a href={actions.leave}>
                                    <DoorOpen className="h-4 w-4" />
                                    Leave Game
                                </a>
                            </Button>
                        </div>
                    </div>
                    <div
                        className="mt-4 h-4 rounded-full bg-emerald-100"
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={progress}
                    >
                        <div className="h-4 rounded-full bg-emerald-500" style={{ width: `${progress}%` }} />
                    </div>
                    <p className="mt-2 text-sm font-bold text-slate-600">
                        Round {payload.session.current_round} of {payload.session.total_rounds} · {payload.session.difficulty.replaceAll('_', ' ')}
                    </p>
                </header>

                <section
                    ref={gameArea}
                    tabIndex={0}
                    className="flex flex-1 flex-col rounded-[2.5rem] border-4 border-white bg-gradient-to-br from-sky-50 via-white to-amber-50 p-4 shadow-[0_24px_90px_rgba(31,76,58,0.1)] outline-none focus:border-emerald-400 md:p-8"
                    aria-label="Game area"
                >
                    <p className="text-lg font-bold text-slate-700">{payload.game.instructions}</p>
                    <h2 className="mt-5 font-[family-name:var(--font-display)] text-3xl font-black md:text-5xl">
                        {payload.round?.prompt ?? 'Mission complete!'}
                    </h2>

                    <GameControls gameType={payload.game.game_type} round={payload.round} onAnswer={submitResponse} />

                    <div aria-live="polite" className="mt-auto rounded-[1.5rem] bg-white/90 p-4 text-lg font-black text-emerald-800">
                        {feedback || 'Focus the game area, then use your mouse, keyboard or touch controls.'}
                    </div>
                </section>

                <footer className="flex flex-wrap justify-between gap-3 rounded-[2rem] bg-white/90 p-3">
                    <Button type="button" variant="outline" className="rounded-full" onClick={() => setHintUsed(true)}>
                        <Lightbulb className="h-4 w-4" />
                        Hint
                    </Button>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild type="button" variant="outline" className="rounded-full">
                            <Link method="post" href={actions.pause} preserveScroll>
                                <Pause className="h-4 w-4" />
                                Pause
                            </Link>
                        </Button>
                        <Button asChild type="button" variant="outline" className="rounded-full">
                            <Link method="post" href={actions.resume} preserveScroll>
                                <Play className="h-4 w-4" />
                                Resume
                            </Link>
                        </Button>
                        <Button asChild type="button" className="rounded-full">
                            <Link method="post" href={actions.complete} preserveScroll headers={{ 'Idempotency-Key': payload.session.uuid }}>
                                <RotateCcw className="h-4 w-4" />
                                Finish
                            </Link>
                        </Button>
                    </div>
                </footer>
            </section>
        </main>
    );
}

function GameControls({
    gameType,
    round,
    onAnswer,
}: {
    gameType: string;
    round: Payload['round'];
    onAnswer: (response: Record<string, FormDataConvertible>) => void;
}) {
    const safe = round?.safe ?? {};
    const value = String(safe.value ?? safe.key ?? safe.label ?? safe.name ?? '');

    if (gameType === 'arrow_key_path') {
        return (
            <div className="my-8 grid max-w-xs grid-cols-3 gap-3 self-center">
                <span />
                <ControlButton label="Up" onClick={() => onAnswer({ move: 'ArrowUp' })} icon={<ArrowUp />} />
                <span />
                <ControlButton label="Left" onClick={() => onAnswer({ move: 'ArrowLeft' })} icon={<ArrowLeft />} />
                <ControlButton label="Down" onClick={() => onAnswer({ move: 'ArrowDown' })} icon={<ArrowDown />} />
                <ControlButton label="Right" onClick={() => onAnswer({ move: 'ArrowRight' })} icon={<ArrowRight />} />
            </div>
        );
    }

    if (gameType.includes('keyboard') || gameType === 'falling_letters') {
        return (
            <div className="my-8 flex flex-wrap justify-center gap-3">
                {['A', 'S', 'D', 'F', 'Enter', 'Spacebar', 'Shift', 'ArrowRight'].map((key) => (
                    <button
                        key={key}
                        type="button"
                        className="min-h-14 rounded-2xl border-2 border-slate-200 bg-white px-5 text-lg font-black"
                        onClick={() => onAnswer({ key })}
                    >
                        {key}
                    </button>
                ))}
            </div>
        );
    }

    if (gameType === 'double_click_practice') {
        return (
            <button
                type="button"
                className="my-10 min-h-28 rounded-[2rem] bg-amber-300 px-10 text-2xl font-black shadow-lg"
                onDoubleClick={() => onAnswer({ interval_ms: 700 })}
            >
                Double-click or double-tap me
            </button>
        );
    }

    return (
        <div className="my-8 flex flex-wrap justify-center gap-4">
            <button
                type="button"
                className="min-h-20 rounded-[2rem] bg-emerald-500 px-8 text-xl font-black text-white shadow-lg"
                onClick={() => onAnswer({ selected_part: value, selected_target: value, match: String(safe.expected ?? value), value })}
            >
                {String(safe.name ?? safe.label ?? 'Choose this')}
            </button>
            <button
                type="button"
                className="min-h-20 rounded-[2rem] bg-white px-8 text-xl font-black text-slate-700 shadow-lg"
                onClick={() => onAnswer({ selected_part: 'try_again', selected_target: 'try_again', match: 'try_again', value: 'try_again' })}
            >
                Try another answer
            </button>
        </div>
    );
}

function ControlButton({ label, icon, onClick }: { label: string; icon: React.ReactNode; onClick: () => void }) {
    return (
        <button
            type="button"
            className="flex min-h-16 items-center justify-center rounded-2xl bg-white p-4 font-black shadow"
            aria-label={label}
            onClick={onClick}
        >
            {icon}
        </button>
    );
}
