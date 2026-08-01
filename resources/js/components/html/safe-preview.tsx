import { useEffect, useMemo, useState } from 'react';

interface SafePreviewProps {
    html?: string;
    source?: string;
    previewUrl?: string;
    title?: string;
}

export function SafePreview({ html = '', source, previewUrl, title = 'Safe webpage preview' }: SafePreviewProps) {
    const [safeHtml, setSafeHtml] = useState(html);
    const [status, setStatus] = useState(previewUrl ? 'Waiting for code' : 'Preview ready');

    useEffect(() => {
        if (!previewUrl || source === undefined) {
            setSafeHtml(html);
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setStatus('Checking preview safely');
            try {
                const csrf = globalThis.document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
                const response = await fetch(previewUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    signal: controller.signal,
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ source_html: source }),
                });
                if (!response.ok) throw new Error('Preview failed');
                const payload = (await response.json()) as { html: string; issues?: unknown[] };
                setSafeHtml(payload.html ?? '');
                setStatus(payload.issues?.length ? 'Preview updated with safety corrections' : 'Preview updated');
            } catch (error) {
                if ((error as Error).name !== 'AbortError') setStatus('Preview could not be updated. Your last safe preview is still shown.');
            }
        }, 650);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [html, previewUrl, source]);

    const previewDocument = useMemo(() => {
        const applicationOrigin = globalThis.location?.origin ?? '';

        return `<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src ${applicationOrigin} data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'"><style>body{font-family:Georgia,serif;padding:1rem;line-height:1.6;color:#243443;background:#fffef8}img{max-width:100%;height:auto}a{pointer-events:none;color:#0f766e}</style></head><body>${safeHtml}</body></html>`;
    }, [safeHtml]);

    return (
        <section aria-labelledby="safe-preview-title" className="rounded-[1.5rem] border-2 border-dashed border-emerald-200 bg-white p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 id="safe-preview-title" className="font-[family-name:var(--font-display)] text-xl font-black text-[#243443]">
                    {title}
                </h2>
                <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Server checked</span>
            </div>
            <p aria-live="polite" className="mt-2 text-sm font-semibold text-slate-600">
                {status}
            </p>
            <iframe
                title={title}
                sandbox=""
                srcDoc={previewDocument}
                referrerPolicy="no-referrer"
                className="mt-4 min-h-72 w-full rounded-2xl border bg-white"
                aria-label="A safe isolated preview of the learner webpage"
            />
            <p className="mt-3 text-sm font-semibold text-slate-600">
                Only server-sanitised HTML is rendered. Scripts, forms, pop-ups, navigation and unsafe network requests remain blocked.
            </p>
        </section>
    );
}
