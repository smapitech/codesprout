import { Button } from '@/components/ui/button';

const symbols = ['<', '>', '/', '=', '"', "'", '-', '_', '!', ' ', 'Enter', 'Backspace'];

export function SymbolPalette({ onInsert }: { onInsert: (symbol: string) => void }) {
    return (
        <section aria-labelledby="symbol-palette-title" className="rounded-[1.5rem] bg-[#fff8ea] p-4">
            <h2 id="symbol-palette-title" className="font-[family-name:var(--font-display)] text-xl font-black text-[#243443]">
                Coding symbols
            </h2>
            <p className="mt-1 text-sm font-semibold text-slate-600">Choose a symbol when you need a little help. This is practice, not a race.</p>
            <div className="mt-4 grid grid-cols-4 gap-2 sm:grid-cols-6">
                {symbols.map((symbol) => (
                    <Button
                        key={symbol}
                        type="button"
                        variant="secondary"
                        className="h-12 rounded-2xl text-lg font-black focus-visible:ring-4"
                        onClick={() => onInsert(symbol === 'Enter' ? '\n' : symbol === 'Backspace' ? '' : symbol)}
                        aria-label={`Insert ${symbol === ' ' ? 'space' : symbol}`}
                    >
                        {symbol === ' ' ? 'Space' : symbol}
                    </Button>
                ))}
            </div>
        </section>
    );
}
