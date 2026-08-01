import { SafePreview } from '@/components/html/safe-preview';
import { SymbolPalette } from '@/components/html/symbol-palette';
import { blocksToHtml, VisualBlockBuilder } from '@/components/html/visual-block-builder';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function HtmlProjectEditor({ project, actions }: any) {
    const { featureFlags } = usePage<SharedData & { featureFlags?: Record<string, boolean> }>().props;
    const visualBuilderEnabled = featureFlags?.html_visual_builder ?? true;
    const [source, setSource] = useState<string>(project.sourceHtml ?? '<h1>My First Webpage</h1>');
    const [stateVersion, setStateVersion] = useState(project.stateVersion);
    const [saveStatus, setSaveStatus] = useState('Saved');
    const [blocks, setBlocks] = useState([{ id: 'starter-heading', type: 'h1', text: project.title }]);

    const autosave = () => {
        setSaveStatus('Saving');
        router.post(
            actions.autosave,
            {
                autosave_uuid: crypto.randomUUID(),
                state_version: stateVersion,
                source_html: source,
                client_instance_id: 'codesprout-html-editor',
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setStateVersion((version: number) => version + 1);
                    setSaveStatus('Saved');
                },
                onError: () => setSaveStatus('Could not save'),
            },
        );
    };

    return (
        <main className="min-h-screen bg-[#fff8ea] px-4 py-5 text-[#243443] md:px-8">
            <Head title={project.title} />
            <section className="mx-auto max-w-7xl space-y-5">
                <header className="rounded-[2rem] bg-white p-5">
                    <p className="text-sm font-black tracking-[0.2em] text-[#138a72] uppercase">Webpage project</p>
                    <h1 className="font-[family-name:var(--font-display)] text-4xl font-black">{project.title}</h1>
                    <p className="mt-2 font-semibold text-slate-600">
                        Autosave status: <span aria-live="polite">{saveStatus}</span>
                    </p>
                </header>
                <section className={`grid gap-5 ${visualBuilderEnabled ? 'xl:grid-cols-[0.9fr_1fr_0.9fr]' : 'xl:grid-cols-2'}`}>
                    {visualBuilderEnabled ? (
                        <VisualBlockBuilder
                            blocks={blocks}
                            onChange={(next) => {
                                setBlocks(next);
                                setSource(blocksToHtml(next));
                            }}
                        />
                    ) : null}
                    <section className="space-y-3">
                        <label className="block">
                            <span className="text-sm font-black">HTML code</span>
                            <textarea
                                className="mt-2 min-h-96 w-full rounded-[1.5rem] border-2 border-emerald-100 bg-white p-4 font-mono text-base focus-visible:ring-4"
                                value={source}
                                onChange={(event) => setSource(event.target.value)}
                            />
                        </label>
                        <SymbolPalette onInsert={(symbol: string) => setSource((value: string) => value + symbol)} />
                    </section>
                    <section className="space-y-4">
                        <SafePreview html={project.sanitisedHtml ?? ''} source={source} previewUrl={actions.preview} />
                        <section className="rounded-[1.5rem] bg-white p-4">
                            <h2 className="font-[family-name:var(--font-display)] text-xl font-black">Project checklist</h2>
                            <ul className="mt-3 space-y-2">
                                {project.checklist.map((item: string) => (
                                    <li key={item} className="font-semibold text-slate-700">
                                        □ {item}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    </section>
                </section>
                <div className="flex flex-wrap gap-2">
                    <Button type="button" onClick={autosave} className="h-12 rounded-full px-6 font-black">
                        Save
                    </Button>
                    <Button asChild variant="outline" className="h-12 rounded-full px-6 font-black">
                        <Link method="post" href={actions.pause}>
                            Take a break
                        </Link>
                    </Button>
                    <Button asChild variant="outline" className="h-12 rounded-full px-6 font-black">
                        <Link method="post" href={actions.submit}>
                            Submit to teacher
                        </Link>
                    </Button>
                    <Button asChild variant="ghost" className="h-12 rounded-full px-6 font-black">
                        <Link href={actions.leave}>Back to HTML</Link>
                    </Button>
                </div>
            </section>
        </main>
    );
}
