import { ProgressRing } from '@/components/dashboard/progress-ring';
import { OptimizedPicture } from '@/components/optimized-picture';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { type ResponsiveImageAsset, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Code2,
    Flame,
    Home,
    Keyboard,
    Lock,
    Map,
    Menu,
    MousePointer2,
    Play,
    Sparkles,
    Star,
    Target,
    Trophy,
    Wand2,
    type LucideIcon,
} from 'lucide-react';

interface DashboardMission {
    id: string;
    number: number;
    title: string;
    category: string;
    duration: string;
    stars: number;
    href: string;
    image: ResponsiveImageAsset;
}

interface ProgressPathStep {
    label: string;
    progress_state: string;
    status: string;
    aria_label: string;
}

interface ChildDashboardProps {
    role: string;
    branding: {
        mark: ResponsiveImageAsset;
        mascot: ResponsiveImageAsset;
    };
    child: {
        id: number;
        first_name: string;
        display_name: string;
        avatar_url: string | null;
        learner_id: string | null;
        current_level: number;
        role_label: string;
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
    missions: DashboardMission[];
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
    progressPath: ProgressPathStep[];
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
}

interface DashboardNavItem {
    label: string;
    href: string;
    icon: LucideIcon;
    active?: boolean;
}

const baseDashboardNavItems: DashboardNavItem[] = [
    { label: 'Home', href: '#home', icon: Home, active: true },
    { label: 'My Journey', href: route('child.journey'), icon: Map },
    { label: 'Missions', href: '#missions', icon: Target },
    { label: 'Rewards', href: route('child.rewards.index'), icon: Trophy },
];

export default function ChildDashboard({
    branding,
    child,
    currentWorld,
    missions,
    progress,
    summary,
    progressPath,
    badge,
    streak,
    teacherFeedback,
}: ChildDashboardProps) {
    const { auth, featureFlags } = usePage<SharedData & { featureFlags?: Record<string, boolean> }>().props;
    const htmlEnabled = featureFlags?.html_learning_engine ?? true;
    const dashboardNavItems = htmlEnabled
        ? [...baseDashboardNavItems.slice(0, 3), { label: 'HTML', href: route('child.html.index'), icon: Code2 }, ...baseDashboardNavItems.slice(3)]
        : baseDashboardNavItems;
    const displayName = child.first_name ?? child.display_name ?? auth.user?.profile?.display_name ?? 'Explorer';
    const learnerId = child.learner_id ?? auth.user?.child_profile?.learner_id ?? 'Pending';
    const currentLevel = child.current_level ?? summary.current_level;
    const avatarUrl = child.avatar_url ?? auth.user?.avatar_url;

    return (
        <div className="min-h-screen overflow-x-hidden bg-[radial-gradient(circle_at_top_left,_rgba(46,196,145,0.16),_transparent_24%),radial-gradient(circle_at_top_right,_rgba(255,207,102,0.18),_transparent_24%),linear-gradient(180deg,_#fffdf7_0%,_#f4fbf7_100%)] text-[var(--foreground)]">
            <Head title={`Child Dashboard | ${displayName}`} />

            <div className="mx-auto grid min-h-screen max-w-[1600px] gap-4 px-3 pt-3 pb-28 xl:grid-cols-[248px_minmax(0,1fr)_360px] xl:px-4 xl:pb-4">
                <aside className="hidden rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur xl:flex xl:flex-col xl:justify-between">
                    <div className="space-y-6">
                        <a href="#home" className="block">
                            <BrandBlock branding={branding} />
                        </a>

                        <nav aria-label="Child dashboard sections" className="space-y-2">
                            {dashboardNavItems.map((item) => (
                                <DashboardNavLink key={item.label} item={item} />
                            ))}
                        </nav>
                    </div>

                    <div className="rounded-[1.75rem] bg-emerald-50 p-4 shadow-sm">
                        <div className="mx-auto flex h-36 w-36 items-center justify-center">
                            <OptimizedPicture
                                asset={branding.mascot}
                                decorative
                                className="h-36 w-36"
                                imgClassName="object-contain"
                                loading="lazy"
                                sizes="144px"
                            />
                        </div>
                        <p className="mt-2 text-center font-[family-name:var(--font-display)] text-lg font-black">Grow your code!</p>
                        <p className="mt-1 text-center text-sm text-emerald-900/75">A gentle, game-based learning journey.</p>
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
                                    <SheetTitle className="sr-only">Child dashboard menu</SheetTitle>
                                    <SheetHeader className="items-start text-left">
                                        <BrandBlock branding={branding} compact />
                                    </SheetHeader>
                                    <nav aria-label="Child dashboard sections" className="mt-6 space-y-2">
                                        {dashboardNavItems.map((item) => (
                                            <DashboardNavLink key={item.label} item={item} compact />
                                        ))}
                                    </nav>
                                </SheetContent>
                            </Sheet>

                            <a href="#home" className="flex min-w-0 items-center gap-3">
                                <BrandBlock branding={branding} compact />
                            </a>

                            <Avatar className="h-11 w-11 rounded-2xl border border-emerald-100 bg-emerald-50 shadow-sm">
                                <AvatarImage src={avatarUrl ?? undefined} alt={displayName} />
                                <AvatarFallback className="rounded-2xl bg-emerald-100 text-base font-black text-emerald-900">
                                    {displayName.slice(0, 1)}
                                </AvatarFallback>
                            </Avatar>
                        </div>
                    </header>

                    <section
                        id="home"
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
                                        Hi, {displayName}! Ready to grow your code?
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-base">Your next mission is waiting in {currentWorld.name}.</p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        <Pill label={`Learner ID: ${learnerId}`} tone="emerald" />
                                        <Pill label={`Level ${currentLevel}`} tone="violet" icon={Sparkles} />
                                        <Pill label={`${summary.stars} stars`} tone="amber" icon={Star} />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 self-start rounded-[1.5rem] bg-emerald-50 px-4 py-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm">
                                    <Target className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-xs font-semibold tracking-[0.18em] text-emerald-700 uppercase">Current world</p>
                                    <p className="mt-1 text-sm font-bold text-[var(--foreground)]">{currentWorld.name}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        id="journey"
                        className="overflow-hidden rounded-[2rem] border border-white/70 bg-white/90 shadow-[0_20px_80px_rgba(31,76,58,0.08)] backdrop-blur"
                    >
                        <div className="p-4 sm:p-5 lg:hidden">
                            <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">World {currentWorld.number}</p>
                            <h2 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                {currentWorld.name}
                            </h2>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {currentWorld.missions_completed} of {currentWorld.missions_available} missions complete
                            </p>

                            <div className="mt-4 flex flex-wrap gap-3">
                                <Button asChild className="h-12 rounded-full px-6 text-base font-semibold shadow-lg shadow-emerald-500/20">
                                    <a href={currentWorld.continue_href}>
                                        Continue Adventure
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </a>
                                </Button>
                                <div className="rounded-full bg-white px-4 py-3 text-sm font-semibold text-emerald-900 shadow-sm">
                                    {currentWorld.completion_percent}% complete
                                </div>
                            </div>
                        </div>

                        <div className="relative isolate overflow-hidden">
                            <OptimizedPicture
                                asset={currentWorld.banner}
                                className="h-[280px] w-full sm:h-[340px] lg:h-[460px]"
                                imgClassName="object-cover object-[center_28%]"
                                loading="eager"
                                sizes="(min-width: 1280px) 760px, (min-width: 640px) 100vw, 100vw"
                            />

                            <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(90deg,_rgba(255,250,242,0.96)_0%,_rgba(255,250,242,0.75)_24%,_rgba(255,250,242,0.08)_52%,_rgba(255,250,242,0)_100%)]" />

                            <div className="absolute inset-0 hidden p-5 lg:flex lg:flex-col lg:justify-between xl:p-8">
                                <div className="max-w-[34rem] rounded-[1.75rem] bg-white/80 p-5 shadow-[0_16px_40px_rgba(31,76,58,0.12)] backdrop-blur">
                                    <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">World {currentWorld.number}</p>
                                    <h2 className="mt-2 font-[family-name:var(--font-display)] text-3xl font-black text-[var(--foreground)] xl:text-4xl">
                                        {currentWorld.name}
                                    </h2>
                                    <p className="mt-3 max-w-xl text-sm leading-7 text-slate-700">
                                        {currentWorld.missions_completed} of {currentWorld.missions_available} missions complete
                                    </p>
                                    <div className="mt-5 flex flex-wrap gap-3">
                                        <Button asChild className="h-12 rounded-full px-6 text-base font-semibold shadow-lg shadow-emerald-500/20">
                                            <a href={currentWorld.continue_href}>
                                                Continue Adventure
                                                <ArrowRight className="ml-2 h-4 w-4" />
                                            </a>
                                        </Button>
                                        <div className="rounded-full bg-white px-4 py-3 text-sm font-semibold text-emerald-900 shadow-sm">
                                            {currentWorld.completion_percent}% complete
                                        </div>
                                    </div>
                                </div>

                                <div className="ml-auto hidden items-center gap-2 rounded-full bg-white/85 px-4 py-3 shadow-[0_12px_30px_rgba(31,76,58,0.08)] backdrop-blur md:flex">
                                    <span className="text-sm font-semibold text-slate-700">Accessible path</span>
                                    <div className="flex items-center gap-2" aria-hidden="true">
                                        {Array.from({ length: currentWorld.missions_available }, (_, index) => (
                                            <WorldNode
                                                key={index}
                                                index={index + 1}
                                                completed={index < currentWorld.missions_completed}
                                                current={index === currentWorld.missions_completed}
                                                final={index === currentWorld.missions_available - 1}
                                            />
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        id="learning-path"
                        className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.08)] backdrop-blur"
                    >
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">My journey</p>
                                <h2 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    Learning progression
                                </h2>
                            </div>
                            <p className="text-muted-foreground text-sm">
                                {summary.missions_completed} of {summary.missions_available} missions complete
                            </p>
                        </div>

                        <ol className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            {progressPath.map((step, index) => (
                                <li
                                    key={step.label}
                                    className={cn(
                                        'rounded-[1.5rem] border p-4 shadow-sm transition',
                                        step.status === 'locked'
                                            ? 'border-slate-200 bg-slate-50/80 text-slate-500'
                                            : step.progress_state === 'current'
                                              ? 'border-amber-200 bg-amber-50/70 text-amber-900'
                                              : 'border-emerald-100 bg-emerald-50/80 text-emerald-950',
                                    )}
                                    aria-label={step.aria_label}
                                >
                                    <div className="flex items-start gap-3">
                                        <div
                                            className={cn(
                                                'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl',
                                                step.status === 'locked'
                                                    ? 'bg-white text-slate-400'
                                                    : step.progress_state === 'current'
                                                      ? 'bg-white text-amber-600'
                                                      : 'bg-white text-emerald-700',
                                            )}
                                            aria-hidden="true"
                                        >
                                            {step.status === 'locked' ? (
                                                <Lock className="h-4 w-4" />
                                            ) : index === 0 ? (
                                                <CheckCircle2 className="h-4 w-4" />
                                            ) : (
                                                <Sparkles className="h-4 w-4" />
                                            )}
                                        </div>

                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm leading-6 font-semibold text-inherit">
                                                {index + 1}. {step.label}
                                            </p>
                                            <p className="mt-1 text-xs font-semibold tracking-[0.18em] text-inherit/70 uppercase">
                                                {step.progress_state.replaceAll('_', ' ')}
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ol>
                    </section>

                    <section id="missions">
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">Today&apos;s missions</p>
                                <h2 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    Small steps, big confidence
                                </h2>
                            </div>
                            <p className="text-muted-foreground text-sm">{missions.length} ready missions</p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {missions.map((mission) => (
                                <Card
                                    key={mission.id}
                                    id={mission.id}
                                    className="overflow-hidden rounded-[1.75rem] border-white/80 bg-white/90 shadow-[0_12px_40px_rgba(38,84,63,0.08)]"
                                >
                                    <div className="relative">
                                        <OptimizedPicture
                                            asset={mission.image}
                                            className="h-[240px] w-full"
                                            imgClassName="object-cover object-center"
                                            loading="lazy"
                                            sizes="(min-width: 1280px) 360px, (min-width: 768px) 50vw, 100vw"
                                        />
                                        <div className="absolute inset-0 bg-[linear-gradient(180deg,_rgba(0,0,0,0)_0%,_rgba(0,0,0,0.08)_58%,_rgba(0,0,0,0.18)_100%)]" />
                                        <div className="absolute top-4 left-4 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-sm font-black text-white shadow-lg">
                                            {mission.number}
                                        </div>
                                        <div className="absolute bottom-4 left-4 rounded-full bg-white/95 px-3 py-1 text-xs font-semibold text-emerald-900 shadow-sm">
                                            {mission.category}
                                        </div>
                                    </div>

                                    <CardContent className="space-y-4 p-5">
                                        <div>
                                            <p className="text-lg font-black text-[var(--foreground)]">{mission.title}</p>
                                            <p className="text-muted-foreground mt-1 text-sm">{mission.duration}</p>
                                        </div>

                                        <div className="flex items-center justify-between gap-3">
                                            <span className="inline-flex items-center gap-1 text-sm font-semibold text-amber-700">
                                                <Star className="h-4 w-4 fill-amber-400 text-amber-500" />
                                                {mission.stars > 0 ? `${mission.stars} stars` : 'Published activity'}
                                            </span>

                                            <Button asChild size="icon" className="h-12 w-12 rounded-full">
                                                <a href={mission.href} aria-label={`Open ${mission.title}`}>
                                                    <Play className="h-4 w-4" />
                                                </a>
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                            {missions.length === 0 && (
                                <div className="rounded-[1.75rem] border border-dashed border-emerald-200 bg-white/80 p-6 text-sm text-emerald-900 md:col-span-2 xl:col-span-3">
                                    No mission is available yet. Your teacher can publish or assign the next activity without changing your saved
                                    progress.
                                </div>
                            )}
                        </div>
                    </section>

                    {htmlEnabled ? (
                        <section
                            id="projects"
                            className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.08)] backdrop-blur"
                        >
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div className="flex items-start gap-4">
                                    <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-sky-800 shadow-sm">
                                        <Wand2 className="h-6 w-6" />
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold tracking-[0.18em] text-sky-700 uppercase">Projects</p>
                                        <h2 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                            Continue your HTML Adventure
                                        </h2>
                                        <p className="text-muted-foreground mt-3 max-w-2xl text-sm leading-7">
                                            Build headings, paragraphs, lists and safe starter webpages with autosave and a sandboxed preview.
                                        </p>
                                    </div>
                                </div>

                                <a
                                    href={route('child.html.index')}
                                    className="flex items-center gap-2 self-start rounded-full bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-900 focus-visible:ring-4 focus-visible:ring-sky-200"
                                >
                                    <Code2 className="h-4 w-4" />
                                    Open HTML Adventure
                                </a>
                            </div>
                        </section>
                    ) : null}
                </main>

                <aside id="rewards" className="space-y-4 xl:sticky xl:top-4 xl:self-start">
                    <section className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">My progress</p>
                                <h3 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    Keep growing
                                </h3>
                            </div>
                            <div className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-900">
                                {summary.overall_progress}%
                            </div>
                        </div>

                        <div className="mt-5 flex justify-center">
                            <ProgressRing
                                value={progress.overall}
                                label="Overall progress"
                                sublabel={`${summary.missions_completed} of ${summary.missions_available} missions`}
                            />
                        </div>

                        <div className="mt-6 space-y-4">
                            <ProgressMetricRow label="Mouse control" value={progress.mouse_control} Icon={MousePointer2} tone="emerald" />
                            <ProgressMetricRow label="Typing accuracy" value={progress.typing_accuracy} Icon={Keyboard} tone="violet" />
                            <ProgressMetricRow label="Coding symbols" value={progress.coding_symbols} Icon={Code2} tone="amber" />
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">Learning streak</p>
                                <h3 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    {streak.days} day streak
                                </h3>
                            </div>
                            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shadow-sm">
                                <Flame className="h-6 w-6" />
                            </div>
                        </div>

                        <p className="text-muted-foreground mt-3 text-sm leading-7">{streak.description}</p>

                        <div className="mt-4 inline-flex items-center gap-2 rounded-full bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-900">
                            <CalendarDays className="h-4 w-4" />
                            Keep learning today
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">{badge.label}</p>
                                <h3 className="mt-2 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--foreground)]">
                                    {badge.title}
                                </h3>
                            </div>
                            <div className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Reward</div>
                        </div>

                        <div className="mt-4 flex items-center gap-4">
                            <OptimizedPicture
                                asset={badge.image}
                                className="h-24 w-24 shrink-0 rounded-[1.5rem] bg-gradient-to-br from-emerald-100 to-cyan-100 p-2 shadow-sm"
                                imgClassName="object-contain"
                                loading="lazy"
                                sizes="96px"
                            />

                            <div className="min-w-0">
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">Achievement date</p>
                                <p className="mt-2 text-sm text-[var(--foreground)]">{badge.earned_at}</p>
                                <p className="text-muted-foreground mt-1 text-sm font-medium">A new reward has been added to your journey.</p>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-white/70 bg-white/90 p-5 shadow-[0_20px_80px_rgba(31,76,58,0.1)] backdrop-blur">
                        <div className="flex items-start gap-4">
                            <OptimizedPicture
                                asset={teacherFeedback.guide}
                                className="h-24 w-24 shrink-0 rounded-[1.5rem] bg-amber-50 p-2 shadow-sm"
                                imgClassName="object-contain"
                                loading="lazy"
                                sizes="96px"
                            />

                            <div className="min-w-0">
                                <p className="text-sm font-semibold tracking-[0.18em] text-emerald-700 uppercase">{teacherFeedback.headline}</p>
                                <p className="mt-2 text-base leading-7 text-[var(--foreground)]">{teacherFeedback.message}</p>
                            </div>
                        </div>

                        <Button asChild className="mt-4 h-12 w-full rounded-full text-base font-semibold">
                            <a href={currentWorld.continue_href}>
                                Continue Adventure
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </a>
                        </Button>
                    </section>
                </aside>
            </div>

            <nav
                aria-label="Quick navigation"
                className="fixed inset-x-0 bottom-0 z-40 border-t border-white/70 bg-white/95 px-3 py-2 shadow-[0_-12px_40px_rgba(31,76,58,0.08)] backdrop-blur xl:hidden"
            >
                <div className="grid grid-cols-5 gap-2">
                    {dashboardNavItems.map((item) => (
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

function BrandBlock({ branding, compact = false }: { branding: ChildDashboardProps['branding']; compact?: boolean }) {
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

function DashboardNavLink({ item, compact = false }: { item: DashboardNavItem; compact?: boolean }) {
    return (
        <a
            href={item.href}
            className={cn(
                'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition',
                item.active ? 'bg-emerald-100 text-emerald-900 shadow-sm' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900',
                compact && 'py-4',
            )}
        >
            <item.icon className="h-5 w-5 shrink-0" />
            <span>{item.label}</span>
        </a>
    );
}

function Pill({ label, tone, icon: Icon }: { label: string; tone: 'emerald' | 'violet' | 'amber'; icon?: LucideIcon }) {
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

function ProgressMetricRow({ label, value, Icon, tone }: { label: string; value: number; Icon: LucideIcon; tone: 'emerald' | 'violet' | 'amber' }) {
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
        <div className="space-y-2">
            <div className="flex items-center gap-3">
                <div className={cn('flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl', toneStyles.icon)}>
                    <Icon className="h-5 w-5" />
                </div>
                <div className="min-w-0 flex-1">
                    <div className="flex items-center justify-between gap-3">
                        <p className="text-sm font-semibold text-[var(--foreground)]">{label}</p>
                        <p className="text-sm font-black text-[var(--foreground)]">{value}%</p>
                    </div>
                    <div
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={value}
                        aria-label={`${label} ${value}%`}
                        className="mt-2 h-3 rounded-full bg-slate-200"
                    >
                        <div className={cn('h-3 rounded-full', toneStyles.bar)} style={{ width: `${value}%` }} />
                    </div>
                </div>
            </div>
        </div>
    );
}

function WorldNode({ index, completed, current, final }: { index: number; completed: boolean; current: boolean; final: boolean }) {
    return (
        <span
            className={cn(
                'flex h-9 w-9 items-center justify-center rounded-full border-2 text-xs font-black shadow-sm',
                completed && 'border-emerald-500 bg-emerald-500 text-white',
                current && 'border-amber-400 bg-amber-400 text-white',
                !completed && !current && final && 'border-violet-400 bg-violet-500 text-white',
                !completed && !current && !final && 'border-slate-300 bg-slate-200 text-slate-500',
            )}
            aria-hidden="true"
        >
            {completed ? (
                <CheckCircle2 className="h-4 w-4" />
            ) : current ? (
                <span>{index}</span>
            ) : final ? (
                <Star className="h-4 w-4" />
            ) : (
                <Lock className="h-4 w-4" />
            )}
        </span>
    );
}
