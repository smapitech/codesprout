import { SafePreview } from '@/components/html/safe-preview';
import { SymbolPalette } from '@/components/html/symbol-palette';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function HtmlAttempt({ payload, actions, banner }: any) {
    const starter = payload.exercise.configuration?.starter_html ?? '<h1>Hello webpage</h1>\n<p>My webpage is growing.</p>';
    const [source, setSource] = useState<string>(starter);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        router.post(actions.complete, {
            source_html: source,
            input_method: 'guided_code',
            active_duration_ms: 60000,
            assistance_count: 0,
            idempotency_key: `html-attempt-${payload.attempt.uuid}`,
        });
    };

    return (
        <main className="min-h-screen bg-[#fff8ea] px-4 py-5 text-[#243443] md:px-8">
            <Head title={payload.exercise.title} />
            <section className="mx-auto max-w-6xl space-y-5">
                {banner && <p className="rounded-full bg-sky-50 px-4 py-2 text-sm font-black text-sky-700">{banner}</p>}
                <header className="rounded-[2rem] bg-white p-5">
                    <p className="text-sm font-black tracking-[0.2em] text-[#138a72] uppercase">{payload.exercise.type}</p>
                    <h1 className="font-[family-name:var(--font-display)] text-4xl font-black">{payload.exercise.title}</h1>
                    <p className="mt-2 text-lg font-semibold text-slate-600">{payload.exercise.instructions}</p>
                </header>
                <form className="grid gap-5 lg:grid-cols-[1fr_0.85fr]" onSubmit={submit}>
                    <section className="space-y-4">
                        <label className="block">
                            <span className="text-sm font-black text-slate-700">Your HTML code</span>
                            <textarea
                                className="mt-2 min-h-80 w-full rounded-[1.5rem] border-2 border-emerald-100 bg-white p-4 font-mono text-lg shadow-inner focus-visible:ring-4 focus-visible:ring-emerald-200"
                                value={source}
                                onChange={(event) => setSource(event.target.value)}
                                aria-describedby="editor-help"
                            />
                        </label>
                        <p id="editor-help" className="font-semibold text-slate-600">
                            Type only the tags from this lesson. You can use the symbol palette for help.
                        </p>
                        <SymbolPalette onInsert={(symbol: string) => setSource((value: string) => value + symbol)} />
                        <div className="flex flex-wrap gap-2">
                            {!banner && (
                                <Button type="submit" className="h-12 rounded-full px-6 font-black">
                                    Check my webpage
                                </Button>
                            )}
                            <Button asChild type="button" variant="outline" className="h-12 rounded-full px-6 font-black">
                                <Link href={actions.leave}>Take a break</Link>
                            </Button>
                        </div>
                    </section>
                    <section className="space-y-4">
                        <SafePreview source={source} previewUrl={actions.preview} />
                        {payload.result && (
                            <section aria-live="polite" className="rounded-[1.5rem] bg-white p-4">
                                <h2 className="font-[family-name:var(--font-display)] text-xl font-black">Helpful notes</h2>
                                {(payload.result.summary.guidance ?? []).map((message: string) => (
                                    <p key={message} className="mt-2 font-semibold text-slate-700">
                                        {message}
                                    </p>
                                ))}
                            </section>
                        )}
                    </section>
                </form>
            </section>
        </main>
    );
}
