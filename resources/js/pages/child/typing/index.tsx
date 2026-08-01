import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { Keyboard, Sparkles, TimerReset } from 'lucide-react';

export default function ChildTypingIndex({ summary, exercises }: { summary: any; exercises: any[] }) {
    return (
        <main className="min-h-screen bg-[#fff8ea] px-4 py-5 text-[#243443] md:px-8">
            <Head title="My Typing Practice" />
            <section className="mx-auto max-w-6xl space-y-6">
                <header className="overflow-hidden rounded-[2.5rem] bg-white p-5 shadow-[0_20px_80px_rgba(31,76,58,0.12)] md:p-8">
                    <p className="text-sm font-black tracking-[0.2em] text-[#138a72] uppercase">CodeSprout typing</p>
                    <h1 className="font-[family-name:var(--font-display)] text-4xl font-black md:text-6xl">Let’s grow careful typing</h1>
                    <p className="mt-3 max-w-2xl text-lg font-semibold text-slate-600">
                        Short, calm practice helps your keyboard confidence grow one key at a time.
                    </p>
                    <div className="mt-5 grid gap-3 md:grid-cols-3">
                        <ChildMetric icon={<Sparkles />} label="Accuracy growing" value={`${summary.averageAccuracy || 0}%`} />
                        <ChildMetric icon={<Keyboard />} label="Confident keys" value={String(summary.confidentKeys || 0)} />
                        <ChildMetric icon={<TimerReset />} label="Practice minutes" value={String(summary.practiceMinutes || 0)} />
                    </div>
                </header>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {exercises.map((exercise) => (
                        <article key={exercise.slug} className="rounded-[2rem] bg-white p-5 shadow-[0_16px_60px_rgba(31,76,58,0.1)]">
                            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f7c948]">
                                <Keyboard className="h-7 w-7" aria-hidden />
                            </div>
                            <p className="mt-4 text-sm font-black tracking-[0.16em] text-[#138a72] uppercase">{exercise.type}</p>
                            <h2 className="mt-1 font-[family-name:var(--font-display)] text-2xl font-black">{exercise.title}</h2>
                            <p className="mt-2 min-h-12 font-semibold text-slate-600">{exercise.instructions}</p>
                            <Button asChild className="mt-5 h-12 w-full rounded-full text-base font-black">
                                <Link method="post" href={exercise.startHref} data={{ input_method: 'unknown', keyboard_layout: 'qwerty' }}>
                                    Start typing
                                </Link>
                            </Button>
                        </article>
                    ))}
                </section>
            </section>
        </main>
    );
}

function ChildMetric({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
    return (
        <div className="rounded-[1.5rem] bg-[#fff8ea] p-4">
            <div className="flex items-center gap-3">
                <span className="rounded-full bg-white p-2 text-[#138a72]">{icon}</span>
                <span className="text-sm font-black text-slate-600">{label}</span>
            </div>
            <p className="mt-2 text-3xl font-black">{value}</p>
        </div>
    );
}
