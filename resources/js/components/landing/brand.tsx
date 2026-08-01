import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

interface LandingBrandProps {
    className?: string;
    compact?: boolean;
}

export function LandingBrand({ className, compact = false }: LandingBrandProps) {
    return (
        <div className={cn('flex items-center gap-3', className)}>
            <span
                className={cn(
                    'flex shrink-0 items-center justify-center rounded-[1.15rem] border border-white/70 bg-white/90 text-[var(--codesprout-leaf)] shadow-[0_8px_24px_rgba(19,138,114,0.14)]',
                    compact ? 'size-11' : 'size-14',
                )}
                aria-hidden="true"
            >
                <AppLogoIcon className={cn(compact ? 'size-7' : 'size-8')} />
            </span>

            <div className="min-w-0">
                <p
                    className={cn(
                        'truncate font-[family-name:var(--font-display)] font-black tracking-tight text-[var(--codesprout-ink)]',
                        compact ? 'text-xl' : 'text-[1.75rem]',
                    )}
                >
                    CodeSprout
                </p>
                <p className={cn('truncate text-sm text-[var(--codesprout-ink)]/75', compact && 'text-[0.8rem]')}>by ChildsBridge Academy</p>
            </div>
        </div>
    );
}
