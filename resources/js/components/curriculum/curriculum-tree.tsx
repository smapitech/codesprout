import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { BookOpen, ChevronDown, ChevronRight, Clock3, Layers3, ListOrdered, Lock, Sparkles, Star } from 'lucide-react';

interface CurriculumStageNode {
    id?: number;
    title: string;
    slug: string;
    stage_type: string;
    interaction_type: string;
    estimated_minutes: number;
    star_value: number;
    is_required: boolean;
    status: string;
    instruction_text?: string | null;
    teacher_guidance?: string | null;
    skill_slugs?: string[];
}

interface CurriculumLessonNode {
    id?: number;
    title: string;
    slug: string;
    lesson_number: number;
    estimated_minutes: number;
    difficulty_level: string;
    status: string;
    learner_objective?: string | null;
    teacher_notes?: string | null;
    skill_slugs?: string[];
    stages: CurriculumStageNode[];
}

interface CurriculumUnitNode {
    id?: number;
    title: string;
    slug: string;
    week_number: number;
    description?: string | null;
    learning_outcomes?: string[] | null;
    status: string;
    lessons: CurriculumLessonNode[];
}

interface CurriculumWorldNode {
    id?: number;
    name: string;
    slug: string;
    number?: number;
    world_number?: number;
    short_description?: string | null;
    story_description?: string | null;
    theme_colour?: string | null;
    accent_colour?: string | null;
    status: string;
    openable?: boolean;
    previewable?: boolean;
    units: CurriculumUnitNode[];
}

interface CurriculumTreeProps {
    worlds: CurriculumWorldNode[];
    variant?: 'admin' | 'teacher' | 'child';
    selectedWorldSlug?: string | null;
    className?: string;
}

const variants = {
    admin: {
        shell: 'border-slate-200 bg-white/95 text-slate-950 shadow-sm',
        heading: 'text-sky-700',
        accent: 'bg-sky-100 text-sky-900',
        outline: 'border-sky-200',
    },
    teacher: {
        shell: 'border-emerald-200 bg-emerald-50/80 text-emerald-950 shadow-sm',
        heading: 'text-emerald-700',
        accent: 'bg-emerald-100 text-emerald-900',
        outline: 'border-emerald-200',
    },
    child: {
        shell: 'border-[#efd7b7] bg-[#fffdf6] text-[var(--foreground)] shadow-[0_18px_50px_rgba(38,84,63,0.08)]',
        heading: 'text-emerald-700',
        accent: 'bg-yellow-100 text-amber-900',
        outline: 'border-[#f0e0c4]',
    },
} as const;

export function CurriculumTree({ worlds, variant = 'admin', selectedWorldSlug, className }: CurriculumTreeProps) {
    const style = variants[variant];

    return (
        <div className={cn('space-y-4', className)}>
            {worlds.map((world) => {
                const worldNumber = world.number ?? world.world_number ?? 0;
                const worldSelected = selectedWorldSlug ? selectedWorldSlug === world.slug : false;
                const unitCount = world.units.length;
                const lessonCount = world.units.reduce((total, unit) => total + unit.lessons.length, 0);
                const stageCount = world.units.reduce(
                    (total, unit) => total + unit.lessons.reduce((lessonTotal, lesson) => lessonTotal + lesson.stages.length, 0),
                    0,
                );

                return (
                    <details
                        key={world.slug}
                        open={worldSelected}
                        className={cn('group overflow-hidden rounded-[2rem] border', style.shell, worldSelected && style.outline)}
                    >
                        <summary
                            className={cn(
                                'flex cursor-pointer list-none items-start gap-4 px-5 py-4 focus-visible:outline-none',
                                worldSelected ? 'bg-white/80' : 'hover:bg-white/60',
                            )}
                        >
                            <div
                                className={cn(
                                    'flex h-14 w-14 shrink-0 items-center justify-center rounded-[1.4rem] border text-lg font-black',
                                    style.accent,
                                )}
                            >
                                {worldNumber}
                            </div>

                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">{world.name}</p>
                                    <Badge className={cn('border-transparent px-3 py-1', style.accent)}>{statusLabel(world.status)}</Badge>
                                    {world.openable ? (
                                        <Badge className="border-transparent bg-emerald-100 px-3 py-1 text-emerald-900">Open</Badge>
                                    ) : null}
                                    {world.previewable ? (
                                        <Badge className="border-transparent bg-amber-100 px-3 py-1 text-amber-900">Preview</Badge>
                                    ) : null}
                                </div>
                                <p className="mt-2 max-w-4xl text-sm leading-7 text-slate-600">
                                    {world.short_description ?? world.story_description}
                                </p>
                                <div className="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                                    <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
                                        <Layers3 className="h-3.5 w-3.5" />
                                        {unitCount} units
                                    </span>
                                    <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
                                        <BookOpen className="h-3.5 w-3.5" />
                                        {lessonCount} lessons
                                    </span>
                                    <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
                                        <Sparkles className="h-3.5 w-3.5" />
                                        {stageCount} stages
                                    </span>
                                </div>
                            </div>

                            <ChevronDown className="mt-2 h-5 w-5 shrink-0 text-slate-500 transition group-open:rotate-180" />
                        </summary>

                        <div className="space-y-4 px-5 pb-5">
                            {world.units.map((unit) => (
                                <details key={unit.slug} className="group rounded-[1.5rem] border border-white/80 bg-white/90">
                                    <summary className="flex cursor-pointer list-none items-start gap-3 px-4 py-4 focus-visible:outline-none">
                                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#e7f5ef] text-sm font-black text-emerald-800">
                                            W{unit.week_number}
                                        </div>

                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold text-[var(--foreground)]">{unit.title}</p>
                                                <Badge className="border-transparent bg-sky-100 px-3 py-1 text-sky-900">
                                                    {statusLabel(unit.status)}
                                                </Badge>
                                            </div>
                                            <p className="mt-1 text-sm leading-6 text-slate-600">{unit.description}</p>
                                        </div>

                                        <ChevronRight className="mt-2 h-4 w-4 shrink-0 text-slate-500 transition group-open:rotate-90" />
                                    </summary>

                                    <div className="space-y-3 px-4 pb-4">
                                        {unit.lessons.map((lesson) => (
                                            <details key={lesson.slug} className="group rounded-[1.35rem] border border-[#f1e6d6] bg-[#fffdf8]">
                                                <summary className="flex cursor-pointer list-none items-start gap-3 px-4 py-3 focus-visible:outline-none">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#f3f0ff] text-sm font-black text-violet-800">
                                                        {lesson.lesson_number}
                                                    </div>

                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <p className="font-semibold text-[var(--foreground)]">{lesson.title}</p>
                                                            <Badge className="border-transparent bg-violet-100 px-3 py-1 text-violet-900">
                                                                {lesson.difficulty_level}
                                                            </Badge>
                                                        </div>
                                                        <div className="mt-1 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                                                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
                                                                <Clock3 className="h-3.5 w-3.5" />
                                                                {lesson.estimated_minutes} min
                                                            </span>
                                                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
                                                                <ListOrdered className="h-3.5 w-3.5" />
                                                                {lesson.stages.length} stages
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <ChevronRight className="mt-2 h-4 w-4 shrink-0 text-slate-500 transition group-open:rotate-90" />
                                                </summary>

                                                <div className="space-y-3 px-4 pb-4">
                                                    {lesson.stages.map((stage) => (
                                                        <div key={stage.slug} className="rounded-[1.2rem] border border-[#eee4d5] bg-white p-4">
                                                            <div className="flex flex-wrap items-start gap-3">
                                                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#eff8f5] text-emerald-700">
                                                                    {stage.is_required ? <Star className="h-4 w-4" /> : <Lock className="h-4 w-4" />}
                                                                </div>

                                                                <div className="min-w-0 flex-1">
                                                                    <div className="flex flex-wrap items-center gap-2">
                                                                        <p className="font-semibold text-[var(--foreground)]">{stage.title}</p>
                                                                        <Badge className="border-transparent bg-amber-100 px-3 py-1 text-amber-900">
                                                                            {stage.stage_type}
                                                                        </Badge>
                                                                        <Badge className="border-transparent bg-cyan-100 px-3 py-1 text-cyan-900">
                                                                            {stage.interaction_type}
                                                                        </Badge>
                                                                    </div>
                                                                    <p className="mt-1 text-sm leading-6 text-slate-600">{stage.instruction_text}</p>
                                                                    <div className="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                                                                        <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
                                                                            <Clock3 className="h-3.5 w-3.5" />
                                                                            {stage.estimated_minutes} min
                                                                        </span>
                                                                        <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
                                                                            <Sparkles className="h-3.5 w-3.5" />
                                                                            {stage.star_value} stars
                                                                        </span>
                                                                        {stage.skill_slugs?.length ? (
                                                                            <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
                                                                                <BookOpen className="h-3.5 w-3.5" />
                                                                                {stage.skill_slugs.join(', ')}
                                                                            </span>
                                                                        ) : null}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </details>
                                        ))}
                                    </div>
                                </details>
                            ))}
                        </div>
                    </details>
                );
            })}
        </div>
    );
}

function statusLabel(status: string): string {
    return status.replaceAll('_', ' ').replace(/^./, (character) => character.toUpperCase());
}
