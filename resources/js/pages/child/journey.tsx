import { CurriculumTree } from '@/components/curriculum/curriculum-tree';
import { OptimizedPicture } from '@/components/optimized-picture';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { type ResponsiveImageAsset, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { ArrowRight, BookOpen, Home, Map, Menu, MousePointer2, Sparkles, Star, Target, Trophy } from 'lucide-react';

interface ChildJourneyProps {
    branding: {
        mark: ResponsiveImageAsset;
        mascot: ResponsiveImageAsset;
    };
    child: {
        first_name: string;
        display_name: string;
        avatar_url: string | null;
        learner_id: string | null;
        current_level: number;
    };
    currentWorld: {
        number: number;
        name: string;
        missions_completed: number;
        missions_available: number;
        completion_percent: number;
        continue_href: string;
        banner: ResponsiveImageAsset;
    };
    progress: {
        overall: number;
        mouse_control: number;
        typing_accuracy: number;
        coding_symbols: number;
    };
    summary: {
        current_level: number;
        missions_completed: number;
        missions_available: number;
        overall_progress: number;
        streak_days: number;
        stars: number;
    };
    badge: {
        label: string;
        title: string;
        earned_at: string;
        image: ResponsiveImageAsset;
    };
    streak: {
        label: string;
        days: number;
        description: string;
    };
    teacherFeedback: {
        headline: string;
        message: string;
        guide: ResponsiveImageAsset;
    };
    journey: {
        curriculum: {
            title: string;
            slug: string;
            total_worlds: number;
            published_worlds: number;
        };
        worlds: Array<Record<string, unknown>>;
        selected_world: Array<Record<string, unknown>> | Record<string, unknown> | null;
        selected_world_slug: string | null;
    };
}

const navItems = [
    { label: 'Home', href: route('child.dashboard'), icon: Home, active: false },
    { label: 'My Journey', href: '#journey', icon: Map, active: true },
    { label: 'Missions', href: '#current-world', icon: Target, active: false },
    { label: 'Projects', href: '#progress', icon: BookOpen, active: false },
    { label: 'Rewards', href: '#rewards', icon: Trophy, active: false },
];

export default function ChildJourneyPage(props: ChildJourneyProps) {
    const { auth } = usePage<SharedData>().props;
    const displayName = props.child.first_name ?? props.child.display_name ?? auth.user?.profile?.display_name ?? 'Explorer';
    const avatarUrl = props.child.avatar_url ?? auth.user?.avatar_url;

    return (
        <div className="min-h-screen overflow-x-hidden bg-[radial-gradient(circle_at_top_left,_rgba(46,196,145,0.16),_transparent_24%),radial-gradient(circle_at_top_right,_rgba(255,207,102,0.18),_transparent_24%),linear-gradient(180deg,_#fffdf7_0%,_#f4fbf7_100%)] text-[var(--foreground)]">
            <Head title={`My Journey | ${displayName}`} />

            <div className="mx-auto grid min-h-screen max-w-[1600px] gap-4 px-3 pt-3 pb-28 xl:grid-cols-[248px_minmax(0,1fr)_360px] xl:px-4 xl:pb-4">
                <aside className="hidden rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur xl:flex xl:flex-col xl:justify-between">
                    <div className="space-y-6">
                        <a href={route('child.dashboard')} className="block">
                            <BrandBlock branding={props.branding} />
                        </a>

                        <nav aria-label="Child journey sections" className="space-y-2">
                            {navItems.map((item) => (
                                <SidebarLink key={item.label} {...item} />
                            ))}
                        </nav>
                    </div>

                    <div className="rounded-[1.75rem] bg-emerald-50 p-4 shadow-sm">
                        <div className="mx-auto flex h-36 w-36 items-center justify-center">
                            <OptimizedPicture
                                asset={props.branding.mascot}
                                decorative
                                className="h-36 w-36"
                                imgClassName="object-contain"
                                loading="lazy"
                                sizes="144px"
                            />
                        </div>
                        <p className="mt-2 text-center font-[family-name:var(--font-display)] text-lg font-black">Your journey is growing.</p>
                        <p className="mt-1 text-center text-sm text-emerald-900/75">Only published learning content appears here.</p>
                    </div>
                </aside>

                <main className="space-y-4">
                    <header className="rounded-[1.75rem] border border-white/70 bg-white/90 p-3 shadow-[0_18px_60px_rgba(31,76,58,0.08)] backdrop-blur xl:hidden">
                        <div className="flex items-center justify-between gap-3">
                            <Sheet>
                                <SheetTrigger asChild>
                                    <Button variant="ghost" size="icon" className="h-11 w-11 rounded-2xl bg-emerald-50 text-emerald-800">
                                        <Menu className="h-5 w-5" />
                                    </Button>
                                </SheetTrigger>
                                <SheetContent side="left" className="w-80 border-emerald-100 bg-[#fffaf2] p-5">
                                    <SheetTitle className="sr-only">Child journey menu</SheetTitle>
                                    <SheetHeader className="items-start text-left">
                                        <BrandBlock branding={props.branding} compact />
                                    </SheetHeader>
                                    <nav aria-label="Child journey sections" className="mt-6 space-y-2">
                                        {navItems.map((item) => (
                                            <SidebarLink key={item.label} {...item} compact />
                                        ))}
                                    </nav>
                                </SheetContent>
                            </Sheet>

                            <a href={route('child.dashboard')} className="flex min-w-0 items-center gap-3">
                                <BrandBlock branding={props.branding} compact />
                            </a>

                            <Avatar className="h-11 w-11 rounded-2xl border border-emerald-100 bg-emerald-50 shadow-sm">
                                <AvatarImage src={avatarUrl ?? undefined} alt={`${displayName} profile avatar`} />
                                <AvatarFallback className="rounded-2xl bg-emerald-100 text-base font-black text-emerald-900">
                                    {displayName.slice(0, 1)}
                                </AvatarFallback>
                            </Avatar>
                        </div>
                    </header>

                    <section
                        id="current-world"
                        className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.08)] backdrop-blur"
                    >
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <Avatar className="h-20 w-20 rounded-[2rem] border border-emerald-100 bg-emerald-50 shadow-sm">
                                    <AvatarImage src={avatarUrl ?? undefined} alt={`${displayName} profile avatar`} />
                                    <AvatarFallback className="rounded-[2rem] bg-emerald-100 text-3xl font-black text-emerald-900">
                                        {displayName.slice(0, 1)}
                                    </AvatarFallback>
                                </Avatar>

                                <div className="min-w-0">
                                    <p className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)] sm:text-4xl">
                                        Hi, {displayName}! Here is your journey map.
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-base">
                                        Published worlds only. Open the current world to keep moving through the year-long programme.
                                    </p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <Pill label={`Learner ID: ${props.child.learner_id ?? 'Pending'}`} tone="emerald" />
                                        <Pill label={`Level ${props.child.current_level}`} tone="violet" icon={Sparkles} />
                                        <Pill label={`${props.summary.stars} stars`} tone="amber" icon={Star} />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 self-start rounded-[1.5rem] bg-emerald-50 px-4 py-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm">
                                    <Target className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-xs font-semibold tracking-[0.18em] text-emerald-700 uppercase">Current world</p>
                                    <p className="mt-1 text-sm font-bold text-[var(--foreground)]">{props.currentWorld.name}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-[2rem] border border-white/70 bg-white/90 shadow-[0_20px_80px_rgba(31,76,58,0.08)] backdrop-blur">
                        <div className="p-4 sm:p-5 lg:hidden">
                            <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">World {props.currentWorld.number}</p>
                            <h2 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                {props.currentWorld.name}
                            </h2>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {props.currentWorld.missions_completed} of {props.currentWorld.missions_available} missions complete
                            </p>

                            <div className="mt-4 flex flex-wrap gap-3">
                                <Button asChild className="h-12 rounded-full px-6 text-base font-semibold shadow-lg shadow-emerald-500/20">
                                    <a href="#journey">
                                        Continue Adventure
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </a>
                                </Button>
                                <div className="rounded-full bg-white px-4 py-3 text-sm font-semibold text-emerald-900 shadow-sm">
                                    {props.currentWorld.completion_percent}% complete
                                </div>
                            </div>
                        </div>

                        <div className="relative isolate overflow-hidden">
                            <OptimizedPicture
                                asset={props.currentWorld.banner}
                                className="h-[280px] w-full sm:h-[340px] lg:h-[420px]"
                                imgClassName="object-cover object-[center_28%]"
                                loading="eager"
                                sizes="(min-width: 1280px) 760px, (min-width: 640px) 100vw, 100vw"
                            />

                            <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(90deg,_rgba(255,250,242,0.96)_0%,_rgba(255,250,242,0.75)_24%,_rgba(255,250,242,0.08)_52%,_rgba(255,250,242,0)_100%)]" />

                            <div className="absolute inset-0 hidden p-5 lg:flex lg:flex-col lg:justify-between xl:p-8">
                                <div className="max-w-[34rem] rounded-[1.75rem] bg-white/80 p-5 shadow-[0_16px_40px_rgba(31,76,58,0.12)] backdrop-blur">
                                    <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">
                                        World {props.currentWorld.number}
                                    </p>
                                    <h2 className="mt-2 font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)] xl:text-4xl">
                                        {props.currentWorld.name}
                                    </h2>
                                    <p className="mt-3 max-w-xl text-sm leading-7 text-slate-700">
                                        {props.currentWorld.missions_completed} of {props.currentWorld.missions_available} missions complete
                                    </p>
                                    <div className="mt-5 flex flex-wrap gap-3">
                                        <Button asChild className="h-12 rounded-full px-6 text-base font-semibold shadow-lg shadow-emerald-500/20">
                                            <a href="#journey">
                                                Continue Adventure
                                                <ArrowRight className="ml-2 h-4 w-4" />
                                            </a>
                                        </Button>
                                        <div className="rounded-full bg-white px-4 py-3 text-sm font-semibold text-emerald-900 shadow-sm">
                                            {props.currentWorld.completion_percent}% complete
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        id="journey"
                        className="space-y-4 rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.08)] backdrop-blur"
                    >
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">My journey</p>
                                <h2 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    The 12-world adventure
                                </h2>
                            </div>
                            <p className="text-muted-foreground text-sm">
                                {props.journey.curriculum.published_worlds} published worlds available to explore
                            </p>
                        </div>

                        <CurriculumTree
                            worlds={props.journey.worlds as never[]}
                            variant="child"
                            selectedWorldSlug={props.journey.selected_world_slug}
                        />
                    </section>
                </main>

                <aside id="rewards" className="space-y-4 xl:sticky xl:top-4 xl:self-start">
                    <section
                        id="progress"
                        className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur"
                    >
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">My progress</p>
                                <h3 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    Keep growing
                                </h3>
                            </div>
                            <div className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-900">
                                {props.summary.overall_progress}%
                            </div>
                        </div>

                        <div className="mt-5 grid gap-3">
                            <Metric label="Overall progress" value={props.progress.overall} Icon={Star} tone="emerald" />
                            <Metric label="Mouse control" value={props.progress.mouse_control} Icon={MousePointer2} tone="emerald" />
                            <Metric label="Typing accuracy" value={props.progress.typing_accuracy} Icon={Sparkles} tone="violet" />
                            <Metric label="Coding symbols" value={props.progress.coding_symbols} Icon={BookOpen} tone="amber" />
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">{props.streak.label}</p>
                                <h3 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    {props.streak.days} day streak
                                </h3>
                            </div>
                            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shadow-sm">
                                <Sparkles className="h-6 w-6" />
                            </div>
                        </div>

                        <p className="text-muted-foreground mt-3 text-sm leading-7">{props.streak.description}</p>
                    </section>

                    <section className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">{props.badge.label}</p>
                                <h3 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    {props.badge.title}
                                </h3>
                            </div>
                            <div className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Reward</div>
                        </div>

                        <div className="mt-4 flex items-center gap-4">
                            <OptimizedPicture
                                asset={props.badge.image}
                                className="h-24 w-24 shrink-0 rounded-[1.5rem] bg-gradient-to-br from-emerald-100 to-cyan-100 p-2 shadow-sm"
                                imgClassName="object-contain"
                                loading="lazy"
                                sizes="96px"
                            />

                            <div className="min-w-0">
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">Achievement date</p>
                                <p className="mt-2 text-sm text-[var(--foreground)]">{props.badge.earned_at}</p>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur">
                        <div className="flex items-start gap-4">
                            <OptimizedPicture
                                asset={props.teacherFeedback.guide}
                                className="h-24 w-24 shrink-0 rounded-[1.5rem] bg-amber-50 p-2 shadow-sm"
                                imgClassName="object-contain"
                                loading="lazy"
                                sizes="96px"
                            />

                            <div className="min-w-0">
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">{props.teacherFeedback.headline}</p>
                                <p className="mt-2 text-base leading-7 text-[var(--foreground)]">{props.teacherFeedback.message}</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>

            <nav
                aria-label="Quick navigation"
                className="fixed inset-x-0 bottom-0 z-40 border-t border-white/70 bg-white/95 px-3 py-2 shadow-[0_-12px_40px_rgba(31,76,58,0.08)] backdrop-blur xl:hidden"
            >
                <div className="grid grid-cols-5 gap-2">
                    {navItems.map((item) => (
                        <a
                            key={item.label}
                            href={item.href}
                            className={cn(
                                'flex min-h-14 flex-col items-center justify-center gap-1 rounded-2xl px-2 py-2 text-[11px] font-semibold transition',
                                item.active ? 'bg-emerald-100 text-emerald-900' : 'text-slate-700 hover:bg-emerald-50',
                            )}
                        >
                            <item.icon className="h-5 w-5" />
                            <span>{item.label}</span>
                        </a>
                    ))}
                </div>
            </nav>
        </div>
    );
}

function BrandBlock({ branding, compact = false }: { branding: ChildJourneyProps['branding']; compact?: boolean }) {
    return (
        <div className={cn('flex items-center gap-3', compact ? 'max-w-[14rem]' : 'max-w-[16rem]')}>
            <OptimizedPicture
                asset={branding.mark}
                className={cn('shrink-0 rounded-[1.5rem] bg-white/70 p-2 shadow-sm', compact ? 'h-14 w-14' : 'h-20 w-20')}
                imgClassName="object-contain"
                loading="eager"
                sizes={compact ? '56px' : '80px'}
            />

            <div className="min-w-0">
                <p
                    className={cn(
                        'font-[family-name:var(--font-display)] font-black tracking-tight text-[var(--foreground)]',
                        compact ? 'text-xl' : 'text-2xl',
                    )}
                >
                    CodeSprout
                </p>
                <p className="text-muted-foreground truncate text-sm">by ChildsBridge Academy</p>
            </div>
        </div>
    );
}

function SidebarLink({
    label,
    href,
    icon: Icon,
    active = false,
    compact = false,
}: {
    label: string;
    href: string;
    icon: any;
    active?: boolean;
    compact?: boolean;
}) {
    return (
        <a
            href={href}
            className={cn(
                'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition',
                active ? 'bg-emerald-100 text-emerald-900 shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900',
                compact && 'py-4',
            )}
        >
            <Icon className="h-5 w-5 shrink-0" />
            <span>{label}</span>
        </a>
    );
}

function Pill({ label, tone, icon: Icon }: { label: string; tone: 'emerald' | 'violet' | 'amber'; icon?: any }) {
    const toneStyles = {
        emerald: 'bg-emerald-100 text-emerald-900',
        violet: 'bg-violet-100 text-violet-900',
        amber: 'bg-amber-100 text-amber-900',
    }[tone];

    return (
        <span className={cn('inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold', toneStyles)}>
            {Icon && <Icon className="h-4 w-4" />}
            {label}
        </span>
    );
}

function Metric({ label, value, Icon, tone }: { label: string; value: number; Icon: any; tone: 'emerald' | 'violet' | 'amber' }) {
    const toneStyles = {
        emerald: {
            icon: 'bg-emerald-100 text-emerald-800',
            bar: 'bg-emerald-500',
        },
        violet: {
            icon: 'bg-violet-100 text-violet-800',
            bar: 'bg-violet-500',
        },
        amber: {
            icon: 'bg-amber-100 text-amber-800',
            bar: 'bg-amber-500',
        },
    }[tone];

    return (
        <div className="space-y-2 rounded-[1.35rem] border border-slate-200 bg-slate-50 px-4 py-3">
            <div className="flex items-center gap-3">
                <div className={cn('flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl', toneStyles.icon)}>
                    <Icon className="h-5 w-5" />
                </div>
                <div className="min-w-0 flex-1">
                    <div className="flex items-center justify-between gap-3">
                        <p className="text-sm font-semibold text-[var(--foreground)]">{label}</p>
                        <p className="text-sm font-black text-[var(--foreground)]">{value}%</p>
                    </div>
                    <div className="mt-2 h-3 rounded-full bg-slate-200" aria-hidden="true">
                        <div className={cn('h-3 rounded-full', toneStyles.bar)} style={{ width: `${value}%` }} />
                    </div>
                </div>
            </div>
        </div>
    );
}
