import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface LandingSectionHeadingProps {
    eyebrow?: string;
    title: string;
    description?: string;
    centered?: boolean;
    className?: string;
    children?: ReactNode;
}

export function LandingSectionHeading({ eyebrow, title, description, centered = false, className, children }: LandingSectionHeadingProps) {
    return (
        <div className={cn('space-y-3', centered && 'mx-auto max-w-3xl text-center', className)}>
            {eyebrow && <p className="text-sm font-semibold tracking-[0.24em] text-[var(--codesprout-leaf)] uppercase">{eyebrow}</p>}
            <h2 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--codesprout-ink)] sm:text-4xl">
                {title}
            </h2>
            {description && <p className="max-w-3xl text-base leading-8 text-[var(--codesprout-ink)]/80 sm:text-lg">{description}</p>}
            {children}
        </div>
    );
}
