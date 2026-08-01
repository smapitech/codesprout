import { cn } from '@/lib/utils';

const rows = [
    ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
    ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l'],
    ['Shift', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'Backspace'],
    ['Spacebar', 'Enter'],
];

interface Props {
    targetKeys?: string[];
    onPress?: (key: string) => void;
    interactive?: boolean;
    uppercase?: boolean;
}

export function OnScreenKeyboard({ targetKeys = [], onPress, interactive = true, uppercase = false }: Props) {
    const targets = targetKeys.map((key) => key.toLowerCase());

    return (
        <div className="rounded-[1.5rem] bg-white/90 p-3 shadow-inner" aria-label="On-screen keyboard teaching aid">
            {rows.map((row, index) => (
                <div key={index} className="mb-2 flex justify-center gap-1.5 last:mb-0">
                    {row.map((key) => {
                        const label = uppercase && key.length === 1 ? key.toUpperCase() : key;
                        const highlighted = targets.includes(key.toLowerCase()) || targets.includes(label.toLowerCase());
                        return (
                            <button
                                key={key}
                                type="button"
                                aria-label={`${label} key${highlighted ? ', current target' : ''}`}
                                disabled={!interactive}
                                onClick={() => onPress?.(key === 'Spacebar' ? ' ' : key)}
                                className={cn(
                                    'min-h-11 min-w-10 rounded-xl border-2 px-3 text-sm font-black transition focus-visible:ring-4 focus-visible:ring-sky-300 focus-visible:outline-none md:min-h-12 md:min-w-12 md:text-base',
                                    key === 'Spacebar' && 'min-w-36',
                                    highlighted ? 'border-emerald-600 bg-[#f7c948] text-[#243443]' : 'border-slate-200 bg-white text-slate-700',
                                )}
                            >
                                {label}
                            </button>
                        );
                    })}
                </div>
            ))}
        </div>
    );
}
