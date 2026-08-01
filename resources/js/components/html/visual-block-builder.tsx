import { Button } from '@/components/ui/button';

interface Block {
    id: string;
    type: string;
    text: string;
}

export function blocksToHtml(blocks: Block[]) {
    return blocks
        .map((block) => {
            const escaped = block.text.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
            if (block.type === 'h1') return `<h1>${escaped}</h1>`;
            if (block.type === 'h2') return `<h2>${escaped}</h2>`;
            if (block.type === 'ul') return `<ul><li>${escaped}</li></ul>`;
            if (block.type === 'hr') return '<hr>';
            return `<p>${escaped}</p>`;
        })
        .join('\n');
}

export function VisualBlockBuilder({ blocks, onChange }: { blocks: Block[]; onChange: (blocks: Block[]) => void }) {
    const move = (index: number, direction: -1 | 1) => {
        const next = [...blocks];
        const target = index + direction;
        if (target < 0 || target >= next.length) return;
        [next[index], next[target]] = [next[target], next[index]];
        onChange(next);
    };

    return (
        <section aria-labelledby="visual-builder-title" className="rounded-[1.5rem] bg-white p-4 shadow-[0_16px_50px_rgba(15,118,110,0.08)]">
            <h2 id="visual-builder-title" className="font-[family-name:var(--font-display)] text-xl font-black text-[#243443]">
                Build with blocks
            </h2>
            <p className="mt-1 text-sm font-semibold text-slate-600">Move blocks with buttons or keyboard focus. Dragging is never required.</p>
            <div className="mt-4 space-y-3">
                {blocks.map((block, index) => (
                    <article key={block.id} className="rounded-2xl border bg-[#f8fafc] p-3">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <span className="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">{block.type}</span>
                            <div className="flex gap-2">
                                <Button type="button" variant="outline" onClick={() => move(index, -1)} aria-label={`Move ${block.type} block up`}>
                                    Move up
                                </Button>
                                <Button type="button" variant="outline" onClick={() => move(index, 1)} aria-label={`Move ${block.type} block down`}>
                                    Move down
                                </Button>
                            </div>
                        </div>
                        <p className="mt-2 text-sm font-semibold text-slate-700">{block.text}</p>
                    </article>
                ))}
            </div>
            <div className="mt-4 flex flex-wrap gap-2">
                {['h1', 'p', 'ul', 'hr'].map((type) => (
                    <Button
                        key={type}
                        type="button"
                        className="rounded-full"
                        onClick={() =>
                            onChange([...blocks, { id: crypto.randomUUID(), type, text: type === 'h1' ? 'My heading' : 'My webpage idea' }])
                        }
                    >
                        Add {type}
                    </Button>
                ))}
            </div>
        </section>
    );
}
