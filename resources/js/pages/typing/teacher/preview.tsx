import { OnScreenKeyboard } from '@/components/typing/on-screen-keyboard';
import { Head } from '@inertiajs/react';

export default function TeacherTypingPreview({ payload, banner }: { payload: any; banner: string }) {
    const first = payload.items[0];
    return (
        <main className="min-h-screen bg-[#fff8ea] p-4 text-[#243443]">
            <Head title="Teacher Typing Preview" />
            <section className="mx-auto max-w-4xl space-y-5 rounded-[2rem] bg-white p-6 shadow-lg">
                <p className="rounded-full bg-amber-100 px-4 py-2 text-sm font-black text-amber-800">{banner}</p>
                <h1 className="font-[family-name:var(--font-display)] text-3xl font-black">{payload.exercise.title}</h1>
                <p className="text-lg font-bold">{payload.exercise.instructions}</p>
                <div className="rounded-[2rem] bg-sky-50 p-6">
                    <p className="text-sm font-black tracking-[0.16em] text-sky-700 uppercase">Preview prompt</p>
                    <p className="mt-2 text-4xl font-black">{first?.displayText ?? 'Ready'}</p>
                </div>
                <OnScreenKeyboard targetKeys={first?.targetKeys ?? []} interactive={false} />
            </section>
        </main>
    );
}
