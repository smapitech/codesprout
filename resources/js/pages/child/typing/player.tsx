import { OnScreenKeyboard } from '@/components/typing/on-screen-keyboard';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/react';
import { DoorOpen, Pause, Play, Volume2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface Props {
    payload: any;
    actions: {
        batch: string;
        pause: string;
        resume: string;
        complete: string;
        leave: string;
    };
}

export default function ChildTypingPlayer({ payload, actions }: Props) {
    const firstItem = payload.items[payload.session.currentItemPosition] ?? payload.items[0];
    const expected = String(firstItem?.displayText ?? '');
    const targetKeys = firstItem?.targetKeys?.length ? firstItem.targetKeys : expected.split('');
    const [typed, setTyped] = useState('');
    const [sequence, setSequence] = useState(payload.session.lastEventSequence ?? 0);
    const [feedback, setFeedback] = useState('Place your cursor in the typing box when you are ready.');
    const [muted, setMuted] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);
    const startedAt = useRef(performance.now());

    const progress = expected.length > 0 ? Math.min(100, Math.round((typed.length / expected.length) * 100)) : 0;

    const sendBatch = (events: any[], complete = false) => {
        if (events.length === 0) return;
        router.post(
            actions.batch,
            { batch_uuid: crypto.randomUUID(), events },
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (complete) {
                        router.post(actions.complete, {}, { preserveScroll: true, headers: { 'Idempotency-Key': payload.session.uuid } });
                    }
                },
            },
        );
    };

    const recordCharacter = (char: string, eventType = 'input') => {
        const nextSequence = sequence + 1;
        const position = eventType === 'backspace' ? Math.max(0, typed.length - 1) : typed.length;
        const expectedChar = expected[position] ?? '';
        const nextTyped = eventType === 'backspace' ? typed.slice(0, -1) : typed + char;
        setSequence(nextSequence);
        setTyped(nextTyped);
        setFeedback(
            eventType === 'backspace'
                ? 'Good correction. Keep going carefully.'
                : char === expectedChar
                  ? 'Nice key!'
                  : 'Almost there. Try the next key carefully.',
        );

        sendBatch([
            {
                sequence_number: nextSequence,
                typing_content_item_id: firstItem?.id,
                character_position: position,
                event_type: eventType,
                expected_character: expectedChar,
                entered_character: eventType === 'backspace' ? undefined : char,
                correctness_state: eventType === 'backspace' ? 'corrected' : char === expectedChar ? 'correct' : 'incorrect',
                input_method: 'physical_keyboard',
                elapsed_offset_ms: Math.round(performance.now() - startedAt.current),
            },
        ]);
    };

    useEffect(() => {
        const input = inputRef.current;
        if (!input) return;

        const handlePaste = (event: ClipboardEvent) => {
            event.preventDefault();
            const text = event.clipboardData?.getData('text/plain')?.slice(0, 20) ?? '';
            if (!text) return;
            recordCharacter(text, 'paste');
            setFeedback('Some text was entered another way. Your teacher can review it kindly.');
        };

        input.addEventListener('paste', handlePaste);
        return () => input.removeEventListener('paste', handlePaste);
    });

    const finish = () => {
        sendBatch(
            [
                {
                    sequence_number: sequence + 1,
                    typing_content_item_id: firstItem?.id,
                    character_position: typed.length,
                    event_type: 'prompt_replay',
                    elapsed_offset_ms: Math.round(performance.now() - startedAt.current),
                },
            ],
            true,
        );
    };

    return (
        <main className="min-h-screen bg-[#fff8ea] px-3 py-4 text-[#243443] md:px-6">
            <Head title={`${payload.exercise.title} | Typing`} />
            <section className="mx-auto flex min-h-[calc(100vh-2rem)] max-w-5xl flex-col gap-4">
                <header className="rounded-[2rem] bg-white p-4 shadow-[0_18px_60px_rgba(38,84,63,0.08)] md:p-6">
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm font-black tracking-[0.2em] text-[#138a72] uppercase">Typing mission</p>
                            <h1 className="font-[family-name:var(--font-display)] text-3xl font-black md:text-5xl">{payload.exercise.title}</h1>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" className="rounded-full" onClick={() => setMuted(!muted)}>
                                <Volume2 className="h-4 w-4" />
                                {muted ? 'Sound off' : 'Sound on'}
                            </Button>
                            <Button asChild variant="outline" className="rounded-full">
                                <a href={actions.leave}>
                                    <DoorOpen className="h-4 w-4" />
                                    Leave
                                </a>
                            </Button>
                        </div>
                    </div>
                    <p className="mt-3 text-lg font-bold text-slate-700">{payload.exercise.instructions}</p>
                    <div
                        className="mt-4 h-4 rounded-full bg-emerald-100"
                        role="progressbar"
                        aria-label={`Typing progress: ${progress} percent complete`}
                    >
                        <div className="h-4 rounded-full bg-[#138a72]" style={{ width: `${progress}%` }} />
                    </div>
                </header>

                <section className="rounded-[2.5rem] bg-gradient-to-br from-sky-50 via-white to-amber-50 p-5 shadow-[0_24px_90px_rgba(31,76,58,0.1)] md:p-8">
                    <p className="text-sm font-black tracking-[0.18em] text-[#8e7cc3] uppercase">Type this</p>
                    <p className="mt-3 rounded-[2rem] bg-white p-5 text-4xl font-black tracking-wide md:text-6xl">{expected}</p>
                    <label className="mt-5 block text-lg font-black" htmlFor="typing-input">
                        Your typing box
                    </label>
                    <input
                        ref={inputRef}
                        id="typing-input"
                        className="mt-2 min-h-16 w-full rounded-[1.5rem] border-4 border-white bg-white px-5 text-3xl font-black outline-none focus:border-[#54b7d3]"
                        value={typed}
                        autoComplete="off"
                        autoCorrect="off"
                        spellCheck={false}
                        onKeyDown={(event) => {
                            if (event.key === 'Escape' || event.key === 'Tab') return;
                            if (event.key === 'Backspace') {
                                event.preventDefault();
                                recordCharacter('', 'backspace');
                                return;
                            }
                            if (event.key.length === 1 || event.key === 'Enter') {
                                event.preventDefault();
                                recordCharacter(event.key === 'Enter' ? '\n' : event.key);
                            }
                        }}
                        onChange={() => undefined}
                        aria-describedby="typing-feedback"
                    />
                    <div id="typing-feedback" aria-live="polite" className="mt-4 rounded-[1.5rem] bg-white p-4 text-lg font-black text-[#138a72]">
                        {feedback}
                    </div>
                    <div className="mt-5">
                        <OnScreenKeyboard
                            targetKeys={targetKeys}
                            onPress={(key) => {
                                recordCharacter(key === 'Spacebar' ? ' ' : key, key === 'Backspace' ? 'backspace' : 'input');
                            }}
                        />
                    </div>
                </section>

                <footer className="flex flex-wrap justify-between gap-3 rounded-[2rem] bg-white p-3">
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" className="rounded-full">
                            <Link method="post" href={actions.pause} preserveScroll>
                                <Pause className="h-4 w-4" />
                                Pause
                            </Link>
                        </Button>
                        <Button asChild variant="outline" className="rounded-full">
                            <Link method="post" href={actions.resume} data={{ state_version: payload.session.stateVersion }} preserveScroll>
                                <Play className="h-4 w-4" />
                                Resume
                            </Link>
                        </Button>
                    </div>
                    <Button type="button" className="rounded-full px-6 font-black" onClick={finish}>
                        Finish typing
                    </Button>
                </footer>
            </section>
        </main>
    );
}
