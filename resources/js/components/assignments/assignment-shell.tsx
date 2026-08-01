import { cn } from '@/lib/utils';
import { PropsWithChildren } from 'react';

export function AssignmentShell({ children, child = false, className }: PropsWithChildren<{ child?: boolean; className?: string }>) {
    return (
        <div
            className={cn(
                'min-h-screen overflow-x-hidden',
                child
                    ? 'bg-[radial-gradient(circle_at_top_left,_rgba(19,138,114,0.14),_transparent_28%),linear-gradient(180deg,_#fff8ea_0%,_#f4fbf7_100%)]'
                    : 'bg-background',
                className,
            )}
        >
            {children}
        </div>
    );
}

export function EmptyState({ title, description }: { title: string; description: string }) {
    return (
        <div className="rounded-[1.5rem] border border-dashed border-emerald-200 bg-emerald-50/50 p-6 text-sm leading-7 text-emerald-950">
            <p className="font-bold">{title}</p>
            <p className="mt-1 text-emerald-900/80">{description}</p>
        </div>
    );
}

export function StatusPill({ value, tone = 'emerald' }: { value: string; tone?: 'emerald' | 'amber' | 'sky' | 'violet' | 'coral' }) {
    const tones = {
        emerald: 'bg-emerald-100 text-emerald-900',
        amber: 'bg-amber-100 text-amber-900',
        sky: 'bg-sky-100 text-sky-900',
        violet: 'bg-violet-100 text-violet-900',
        coral: 'bg-red-100 text-red-900',
    };

    return <span className={cn('inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize', tones[tone])}>{value.replaceAll('_', ' ')}</span>;
}
