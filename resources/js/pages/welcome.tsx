import { LandingBrand } from '@/components/landing/brand';
import { LandingParentPreview } from '@/components/landing/parent-preview';
import { LandingPathTimeline } from '@/components/landing/path-timeline';
import { LandingSectionHeading } from '@/components/landing/section-heading';
import { LandingWorldCard } from '@/components/landing/world-card';
import { OptimizedPicture } from '@/components/optimized-picture';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Sheet, SheetClose, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { type ResponsiveImageAsset } from '@/types';
import { type LandingPathWorld, type LandingWorldCardData } from '@/types/landing';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    Ban,
    BarChart3,
    BookOpen,
    CheckCircle2,
    ChevronDown,
    CodeXml,
    FileCode2,
    Gamepad2,
    GraduationCap,
    Keyboard,
    Menu,
    MessageSquareQuote,
    MonitorSmartphone,
    MousePointerClick,
    Play,
    Repeat2,
    ShieldCheck,
    Sparkles,
    TimerReset,
    UsersRound,
    Volume2,
    Wand2,
    type LucideIcon,
} from 'lucide-react';

interface WelcomePageProps {
    page: {
        title: string;
        description: string;
        canonical: string;
        image: string;
    };
    anchors: {
        adventures: string;
        howItWorks: string;
        parents: string;
        learningPath: string;
    };
    links: {
        home: string;
        login: string;
        childLogin: string;
        startAdventure: string;
        dashboard: string | null;
        privacy: string;
        terms: string;
    };
    authState: {
        authenticated: boolean;
        role: string | null;
        dashboard: string | null;
    };
    supportEmail: string | null;
    curriculum: {
        title: string;
        slug: string | null;
        is_fallback: boolean;
        world_count: number;
        published_world_count: number;
    };
    heroAsset: ResponsiveImageAsset;
    introAsset: ResponsiveImageAsset;
    featuredWorlds: LandingWorldCardData[];
    learningWorlds: LandingPathWorld[];
}

interface NavItem {
    title: string;
    href: string;
}

interface StepItem {
    number: string;
    title: string;
    description: string;
    icon: LucideIcon;
    tone: string;
}

interface BenefitItem {
    title: string;
    description: string;
    icon: LucideIcon;
    tone: string;
}

interface ProjectItem {
    title: string;
    description: string;
    icon: LucideIcon;
    tone: string;
}

const navItems: NavItem[] = [
    { title: 'Adventures', href: '#adventures' },
    { title: 'How It Works', href: '#how-it-works' },
    { title: 'For Parents', href: '#parents' },
    { title: 'Learning Path', href: '#learning-path' },
];

const trustPoints = [
    { title: 'Made for ages 6-7', icon: BadgeCheck },
    { title: 'Safe child accounts', icon: ShieldCheck },
    { title: 'Parent progress reports', icon: BarChart3 },
];

const learningSteps: StepItem[] = [
    {
        number: '01',
        title: 'Play',
        description: 'Explore fun games and build confidence.',
        icon: Play,
        tone: 'leaf',
    },
    {
        number: '02',
        title: 'Practise',
        description: 'Repeat skills in new and exciting ways.',
        icon: Repeat2,
        tone: 'sky',
    },
    {
        number: '03',
        title: 'Complete',
        description: 'Fill missing information and repair simple code.',
        icon: CheckCircle2,
        tone: 'sun',
    },
    {
        number: '04',
        title: 'Create',
        description: 'Build webpages and mini-games independently.',
        icon: Sparkles,
        tone: 'coral',
    },
];

const safetyBenefits: BenefitItem[] = [
    { title: 'Spoken instructions', description: 'Children hear clear directions as they learn.', icon: Volume2, tone: 'leaf' },
    { title: 'Large child-friendly controls', description: 'Buttons and touch targets stay easy to use.', icon: MonitorSmartphone, tone: 'sky' },
    { title: 'Extra Slow, Slow and Normal modes', description: 'Families can choose the pace that fits best.', icon: TimerReset, tone: 'sun' },
    { title: 'Encouraging feedback', description: 'Every step is designed to keep confidence growing.', icon: MessageSquareQuote, tone: 'lavender' },
    { title: 'No public leaderboard', description: 'Focus stays on learning rather than competition.', icon: Ban, tone: 'coral' },
    { title: 'Parent-managed child accounts', description: 'Adults stay in control of access and recovery.', icon: UsersRound, tone: 'leaf' },
    { title: 'Teacher progress monitoring', description: 'Teachers can see how learning is developing.', icon: GraduationCap, tone: 'sky' },
    { title: 'Safe restricted coding preview', description: 'Children only work inside the guided experience.', icon: CodeXml, tone: 'sun' },
];

const projectOutcomes: ProjectItem[] = [
    { title: 'A name card', description: 'A cheerful first design that helps children type their name.', icon: BadgeCheck, tone: 'leaf' },
    { title: 'A colourful personal webpage', description: 'A simple page with headings, text and images.', icon: FileCode2, tone: 'sky' },
    { title: 'An interactive button page', description: 'A playful page that responds to clicks and taps.', icon: MousePointerClick, tone: 'coral' },
    { title: 'A keyboard challenge', description: 'A mission that builds confidence with important keys.', icon: Keyboard, tone: 'sun' },
    { title: 'A simple character animation', description: 'An early coding creation with movement and timing.', icon: Wand2, tone: 'lavender' },
    { title: 'A small mini-game', description: 'A guided project that brings skills together.', icon: Gamepad2, tone: 'leaf' },
];

const milestoneCards = [
    {
        title: 'Discover the computer',
        description: 'Learn the parts and how to use them carefully.',
        icon: BookOpen,
    },
    {
        title: 'Control the mouse',
        description: 'Move, click and drag with growing confidence.',
        icon: MousePointerClick,
    },
    {
        title: 'Find the keys',
        description: 'Explore the keyboard and important controls.',
        icon: Keyboard,
    },
    {
        title: 'Type letters and symbols',
        description: 'Build accuracy with real typing practice.',
        icon: Sparkles,
    },
    {
        title: 'Build webpages',
        description: 'Move from blocks and tags to real creations.',
        icon: FileCode2,
    },
];

export default function Welcome({
    page,
    links,
    authState,
    supportEmail,
    curriculum,
    heroAsset,
    introAsset,
    featuredWorlds,
    learningWorlds,
}: WelcomePageProps) {
    const signInLabel = authState.authenticated ? 'Dashboard' : 'Sign In';

    return (
        <div className="relative isolate overflow-x-clip bg-[linear-gradient(180deg,_#FFF8EA_0%,_#FFFDFA_34%,_#F3FBF8_100%)] text-[var(--codesprout-ink)]">
            <Head title={page.title}>
                <meta name="description" content={page.description} />
                <link rel="canonical" href={page.canonical} />
                <meta name="robots" content="index,follow" />
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content="CodeSprout" />
                <meta property="og:title" content={page.title} />
                <meta property="og:description" content={page.description} />
                <meta property="og:url" content={page.canonical} />
                <meta property="og:image" content={page.image} />
                <meta
                    property="og:image:alt"
                    content="CodeSprout hero adventure illustration showing children and a robot following a learning path."
                />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={page.title} />
                <meta name="twitter:description" content={page.description} />
                <meta name="twitter:image" content={page.image} />
            </Head>

            <DecorativeBackground />
            <LandingHeader links={links} signInLabel={signInLabel} />

            <main className="mx-auto flex w-full max-w-[1520px] flex-col gap-12 px-4 pt-4 pb-20 sm:px-6 lg:px-8">
                <section id="home" className="scroll-mt-28">
                    <div className="relative overflow-hidden rounded-[2.75rem] border border-white/80 bg-white/90 px-5 py-6 shadow-[0_22px_80px_rgba(36,52,67,0.08)] sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                        <div className="grid items-center gap-10 lg:grid-cols-[1.02fr_0.98fr]">
                            <div className="order-1 space-y-7">
                                <span className="inline-flex items-center rounded-full border border-[rgba(19,138,114,0.24)] bg-[rgba(19,138,114,0.08)] px-4 py-2 text-sm font-semibold text-[var(--codesprout-leaf)]">
                                    Game-based coding for ages 6-7
                                </span>

                                <div className="space-y-5">
                                    <h1
                                        id="hero-heading"
                                        className="max-w-3xl font-[family-name:var(--font-display)] text-5xl font-black tracking-tight text-[var(--codesprout-ink)] sm:text-6xl lg:text-[4.35rem] lg:leading-[1.02]"
                                    >
                                        Where Little Fingers Grow Big Ideas
                                    </h1>
                                    <p className="max-w-2xl text-lg leading-8 text-[var(--codesprout-ink)]/82 sm:text-xl">
                                        A playful one-year journey from mouse skills and typing to building real webpages and mini-games.
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <Button
                                        asChild
                                        className="h-14 rounded-full bg-[var(--codesprout-coral)] px-6 text-base font-semibold text-white shadow-[0_14px_34px_rgba(255,107,94,0.28)] transition-transform hover:-translate-y-0.5 hover:bg-[var(--codesprout-coral)]/90"
                                    >
                                        <Link href={links.startAdventure}>Start the Adventure</Link>
                                    </Button>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="h-14 rounded-full border-[rgba(19,138,114,0.22)] bg-white px-6 text-base font-semibold text-[var(--codesprout-leaf)] shadow-sm transition-transform hover:-translate-y-0.5 hover:bg-[rgba(19,138,114,0.04)]"
                                    >
                                        <a href="#how-it-works">Watch How It Works</a>
                                    </Button>
                                </div>

                                <div className="flex flex-wrap items-center gap-3 text-sm">
                                    <span className="rounded-full bg-white px-4 py-2 font-semibold text-[var(--codesprout-ink)] shadow-sm ring-1 ring-[rgba(36,52,67,0.08)]">
                                        Safe child accounts
                                    </span>
                                    <span className="rounded-full bg-white px-4 py-2 font-semibold text-[var(--codesprout-ink)] shadow-sm ring-1 ring-[rgba(36,52,67,0.08)]">
                                        Parent progress reports
                                    </span>
                                    <span className="rounded-full bg-white px-4 py-2 font-semibold text-[var(--codesprout-ink)] shadow-sm ring-1 ring-[rgba(36,52,67,0.08)]">
                                        Structured one-year path
                                    </span>
                                </div>

                                <p className="text-sm leading-7 text-[var(--codesprout-ink)]/70">
                                    Already have an account?{' '}
                                    <Link
                                        href={links.login}
                                        className="font-semibold text-[var(--codesprout-leaf)] underline decoration-2 underline-offset-4"
                                    >
                                        {authState.authenticated ? 'Open your dashboard' : 'Sign in'}
                                    </Link>{' '}
                                    for parent, teacher and administrator access.
                                </p>
                            </div>

                            <div className="order-2">
                                <div className="relative mx-auto max-w-[760px]">
                                    <div
                                        className="absolute top-2 left-4 hidden size-16 rounded-full bg-[rgba(247,201,72,0.22)] blur-2xl sm:block"
                                        aria-hidden="true"
                                    />
                                    <div
                                        className="absolute top-8 right-2 hidden size-20 rounded-full bg-[rgba(84,183,211,0.18)] blur-2xl sm:block"
                                        aria-hidden="true"
                                    />
                                    <div className="absolute top-8 -left-3 hidden rounded-full bg-white px-4 py-2 text-sm font-semibold text-[var(--codesprout-ink)] shadow-[0_10px_24px_rgba(36,52,67,0.08)] lg:flex">
                                        {curriculum.is_fallback ? 'Static preview path' : `${curriculum.published_world_count} published worlds`}
                                    </div>
                                    <div className="absolute bottom-5 left-4 rounded-2xl bg-white/95 px-4 py-3 shadow-[0_12px_28px_rgba(36,52,67,0.1)]">
                                        <p className="text-xs font-semibold tracking-[0.24em] text-[var(--codesprout-leaf)] uppercase">
                                            One-year adventure
                                        </p>
                                        <p className="mt-1 text-sm font-semibold text-[var(--codesprout-ink)]">
                                            {curriculum.world_count} worlds, guided from first clicks to creative coding
                                        </p>
                                    </div>
                                    <OptimizedPicture
                                        asset={heroAsset}
                                        className="relative w-full"
                                        imgClassName="landing-rise rounded-[2.25rem] object-cover object-center shadow-[0_18px_44px_rgba(36,52,67,0.12)]"
                                        loading="eager"
                                        sizes="(min-width: 1280px) 720px, (min-width: 768px) 48vw, 100vw"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section aria-label="Trust points">
                    <ul className="grid gap-3 md:grid-cols-3">
                        {trustPoints.map((point) => {
                            const Icon = point.icon;

                            return (
                                <li
                                    key={point.title}
                                    className="rounded-[1.75rem] border border-white/80 bg-white/92 p-4 shadow-[0_10px_28px_rgba(36,52,67,0.06)]"
                                >
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-[rgba(19,138,114,0.1)] text-[var(--codesprout-leaf)]">
                                            <Icon className="size-5" />
                                        </span>
                                        <div>
                                            <p className="font-semibold text-[var(--codesprout-ink)]">{point.title}</p>
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </section>

                <section id="adventures" className="scroll-mt-28">
                    <LandingSectionHeading
                        eyebrow="Learning adventures"
                        title="Every Skill Becomes an Adventure"
                        description="Children move from watching and exploring to building real webpages and mini-games with confidence. Each world adds one small, meaningful leap forward."
                    />

                    <div className="mt-8 grid gap-8 lg:grid-cols-[0.95fr_1.05fr]">
                        <div className="order-1 lg:order-none">
                            <div className="relative overflow-hidden rounded-[2.5rem] border border-white/80 bg-white/92 p-4 shadow-[0_18px_44px_rgba(36,52,67,0.08)]">
                                <OptimizedPicture
                                    asset={introAsset}
                                    className="w-full"
                                    imgClassName="landing-rise rounded-[2rem] object-cover object-center"
                                    loading="lazy"
                                    sizes="(min-width: 1280px) 640px, (min-width: 768px) 48vw, 100vw"
                                />
                                <div className="absolute bottom-4 left-4 rounded-full bg-white/95 px-4 py-2 text-sm font-semibold text-[var(--codesprout-ink)] shadow-[0_10px_22px_rgba(36,52,67,0.08)]">
                                    From mouse control to webpage creation
                                </div>
                            </div>
                        </div>

                        <div className="order-2 space-y-5 lg:order-none">
                            <div className="rounded-[2rem] border border-white/80 bg-white/92 p-6 shadow-[0_12px_32px_rgba(36,52,67,0.07)]">
                                <p className="text-sm font-semibold tracking-[0.24em] text-[var(--codesprout-leaf)] uppercase">A guided journey</p>
                                <p className="mt-3 max-w-2xl text-base leading-8 text-[var(--codesprout-ink)]/80 sm:text-lg">
                                    Children start with discovery and control, then move into typing, symbols, block coding and real web building.
                                    Each step is playful, structured and carefully sequenced.
                                </p>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                {milestoneCards.map((milestone, index) => {
                                    const Icon = milestone.icon;

                                    return (
                                        <div
                                            key={milestone.title}
                                            className="flex items-start gap-4 rounded-[1.5rem] border border-white/80 bg-white/92 p-4 shadow-[0_10px_28px_rgba(36,52,67,0.06)]"
                                        >
                                            <span
                                                className={cn(
                                                    'flex size-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-[0_10px_20px_rgba(36,52,67,0.12)]',
                                                    index === 0 && 'bg-[var(--codesprout-leaf)]',
                                                    index === 1 && 'bg-[var(--codesprout-sky)]',
                                                    index === 2 && 'bg-[var(--codesprout-sun)]',
                                                    index === 3 && 'bg-[var(--codesprout-lavender)]',
                                                    index === 4 && 'bg-[var(--codesprout-coral)]',
                                                )}
                                            >
                                                <Icon className="size-5" />
                                            </span>
                                            <div className="min-w-0">
                                                <p className="font-semibold text-[var(--codesprout-ink)]">{milestone.title}</p>
                                                <p className="mt-1 text-sm leading-6 text-[var(--codesprout-ink)]/72">{milestone.description}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    <div className="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        {featuredWorlds.map((world) => (
                            <LandingWorldCard key={world.slug} world={world} />
                        ))}
                    </div>
                </section>

                <section id="how-it-works" className="scroll-mt-28">
                    <LandingSectionHeading
                        eyebrow="How learning works"
                        title="Small Steps. Real Coding Skills."
                        description="Children progress from playful practice to real problem solving through short, age-appropriate missions that build confidence in sequence."
                    />

                    <div className="relative mt-8 overflow-hidden rounded-[2.5rem] border border-white/80 bg-white/92 p-5 shadow-[0_18px_44px_rgba(36,52,67,0.08)] sm:p-6">
                        <div className="pointer-events-none absolute inset-x-6 top-1/2 hidden -translate-y-1/2 lg:block" aria-hidden="true">
                            <svg className="landing-draw h-48 w-full" viewBox="0 0 1200 220" preserveAspectRatio="none">
                                <path
                                    d="M 40 130 C 200 30, 260 200, 410 110 S 640 30, 810 120 S 1010 195, 1160 70"
                                    fill="none"
                                    stroke="rgba(19,138,114,0.28)"
                                    strokeDasharray="12 14"
                                    strokeLinecap="round"
                                    strokeWidth="8"
                                />
                            </svg>
                        </div>

                        <ol className="relative grid gap-4 lg:grid-cols-4">
                            {learningSteps.map((step) => {
                                const Icon = step.icon;

                                return (
                                    <li
                                        key={step.number}
                                        className="rounded-[1.6rem] border border-[rgba(36,52,67,0.08)] bg-white p-5 shadow-[0_10px_28px_rgba(36,52,67,0.06)] transition-transform duration-300 hover:-translate-y-1"
                                    >
                                        <div className="flex items-start gap-4">
                                            <span
                                                className="flex size-12 shrink-0 items-center justify-center rounded-2xl text-white"
                                                style={{
                                                    backgroundColor:
                                                        step.tone === 'leaf'
                                                            ? 'var(--codesprout-leaf)'
                                                            : step.tone === 'sky'
                                                              ? 'var(--codesprout-sky)'
                                                              : step.tone === 'sun'
                                                                ? 'var(--codesprout-sun)'
                                                                : 'var(--codesprout-coral)',
                                                }}
                                            >
                                                <Icon className="size-5" />
                                            </span>
                                            <div className="min-w-0">
                                                <p className="text-xs font-semibold tracking-[0.24em] text-[var(--codesprout-ink)]/55 uppercase">
                                                    {step.number}
                                                </p>
                                                <h3 className="mt-1 font-[family-name:var(--font-display)] text-2xl font-black text-[var(--codesprout-ink)]">
                                                    {step.title}
                                                </h3>
                                                <p className="mt-2 text-sm leading-7 text-[var(--codesprout-ink)]/76">{step.description}</p>
                                            </div>
                                        </div>
                                    </li>
                                );
                            })}
                        </ol>
                    </div>
                </section>

                <section id="learning-path" className="scroll-mt-28">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <LandingSectionHeading
                            eyebrow="One-year learning path"
                            title="From First Click to Young Creator"
                            description="The public homepage mirrors the published one-year programme whenever it exists and falls back to a safe static preview only when no published curriculum is available."
                            className="max-w-4xl"
                        />

                        <div className="flex flex-wrap gap-2 lg:justify-end">
                            <span className="rounded-full bg-[rgba(19,138,114,0.1)] px-4 py-2 text-sm font-semibold text-[var(--codesprout-leaf)]">
                                {curriculum.world_count} worlds
                            </span>
                            <span className="rounded-full bg-[rgba(255,107,94,0.12)] px-4 py-2 text-sm font-semibold text-[#d94f45]">
                                {curriculum.is_fallback ? 'Static preview' : `${curriculum.published_world_count} published worlds`}
                            </span>
                        </div>
                    </div>

                    <div className="mt-8">
                        <LandingPathTimeline worlds={learningWorlds} />
                    </div>

                    <details className="group mt-5 rounded-[2rem] border border-white/80 bg-white/92 p-5 shadow-[0_12px_32px_rgba(36,52,67,0.07)]">
                        <summary className="flex cursor-pointer list-none items-center justify-between gap-4 outline-hidden">
                            <div>
                                <p className="text-xs font-semibold tracking-[0.24em] text-[var(--codesprout-leaf)] uppercase">
                                    {curriculum.is_fallback ? 'Static learning path preview' : 'Published curriculum snapshot'}
                                </p>
                                <p className="mt-2 font-semibold text-[var(--codesprout-ink)]">
                                    {curriculum.is_fallback
                                        ? 'A safe fallback is shown until the first published curriculum is available.'
                                        : `The ${curriculum.title} is visible here without draft or archived worlds.`}
                                </p>
                            </div>
                            <ChevronDown className="size-5 shrink-0 text-[var(--codesprout-leaf)] transition group-open:rotate-180" />
                        </summary>

                        <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {learningWorlds.map((world) => (
                                <div key={world.slug} className="rounded-[1.35rem] border border-[rgba(36,52,67,0.08)] bg-white p-4">
                                    <p className="text-xs font-semibold tracking-[0.24em] text-[var(--codesprout-ink)]/55 uppercase">
                                        World {world.number}
                                    </p>
                                    <p className="mt-2 font-semibold text-[var(--codesprout-ink)]">{world.title}</p>
                                    {world.shortDescription ? (
                                        <p className="mt-1 text-sm leading-6 text-[var(--codesprout-ink)]/72">{world.shortDescription}</p>
                                    ) : null}
                                </div>
                            ))}
                        </div>
                    </details>
                </section>

                <section id="parents" className="scroll-mt-28">
                    <div className="grid gap-8 xl:grid-cols-[0.95fr_1.05fr] xl:items-center">
                        <div className="space-y-6">
                            <LandingSectionHeading
                                eyebrow="Progress for parents"
                                title="Parents See the Progress, Too"
                                description="The public homepage previews the kind of family dashboard that makes learning visible without exposing any real child information."
                            />

                            <ul className="grid gap-3 sm:grid-cols-2">
                                {[
                                    'Completed learning worlds',
                                    'Typing accuracy',
                                    'Skills being practised',
                                    'Assignments',
                                    'Teacher feedback',
                                    'Projects',
                                    'Time spent learning',
                                    'Achievements',
                                ].map((item) => (
                                    <li
                                        key={item}
                                        className="rounded-[1.35rem] border border-white/80 bg-white/92 px-4 py-3 text-sm font-semibold text-[var(--codesprout-ink)] shadow-[0_10px_24px_rgba(36,52,67,0.05)]"
                                    >
                                        {item}
                                    </li>
                                ))}
                            </ul>

                            <p className="text-sm leading-7 text-[var(--codesprout-ink)]/72">
                                This preview keeps the public page reassuring and informative while leaving private child data inside protected
                                dashboards.
                            </p>
                        </div>

                        <LandingParentPreview />
                    </div>
                </section>

                <section className="scroll-mt-28">
                    <LandingSectionHeading
                        eyebrow="Safe and age-appropriate"
                        title="Built Around Young Learners"
                        description="CodeSprout is designed to feel inviting for children while staying reassuring for parents and teachers."
                    />

                    <div className="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {safetyBenefits.map((benefit) => {
                            const Icon = benefit.icon;

                            return (
                                <Card
                                    key={benefit.title}
                                    className="rounded-[1.55rem] border border-white/80 bg-white/92 p-5 shadow-[0_10px_24px_rgba(36,52,67,0.05)] transition-transform duration-300 hover:-translate-y-1"
                                >
                                    <div className="flex items-start gap-4">
                                        <span
                                            className={cn(
                                                'flex size-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-[0_10px_22px_rgba(36,52,67,0.08)]',
                                                benefit.tone === 'leaf' && 'bg-[var(--codesprout-leaf)]',
                                                benefit.tone === 'sky' && 'bg-[var(--codesprout-sky)]',
                                                benefit.tone === 'sun' && 'bg-[var(--codesprout-sun)] text-[var(--codesprout-ink)]',
                                                benefit.tone === 'lavender' && 'bg-[var(--codesprout-lavender)]',
                                                benefit.tone === 'coral' && 'bg-[var(--codesprout-coral)]',
                                            )}
                                        >
                                            <Icon className="size-5" />
                                        </span>
                                        <div className="min-w-0">
                                            <p className="font-semibold text-[var(--codesprout-ink)]">{benefit.title}</p>
                                            <p className="mt-1 text-sm leading-6 text-[var(--codesprout-ink)]/72">{benefit.description}</p>
                                        </div>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                </section>

                <section id="outcomes" className="scroll-mt-28">
                    <LandingSectionHeading
                        eyebrow="Project outcomes"
                        title="What Will Your Child Create?"
                        description="Children progress at a guided pace. The projects below are examples of the kinds of creative work they can build along the journey."
                    />

                    <div className="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {projectOutcomes.map((project) => {
                            const Icon = project.icon;

                            return (
                                <Card
                                    key={project.title}
                                    className="overflow-hidden rounded-[1.6rem] border border-white/80 bg-white/92 shadow-[0_10px_24px_rgba(36,52,67,0.05)] transition-transform duration-300 hover:-translate-y-1"
                                >
                                    <div className="flex items-start gap-4 p-5">
                                        <span
                                            className={cn(
                                                'flex size-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-[0_10px_22px_rgba(36,52,67,0.08)]',
                                                project.tone === 'leaf' && 'bg-[var(--codesprout-leaf)]',
                                                project.tone === 'sky' && 'bg-[var(--codesprout-sky)]',
                                                project.tone === 'sun' && 'bg-[var(--codesprout-sun)] text-[var(--codesprout-ink)]',
                                                project.tone === 'lavender' && 'bg-[var(--codesprout-lavender)]',
                                                project.tone === 'coral' && 'bg-[var(--codesprout-coral)]',
                                            )}
                                        >
                                            <Icon className="size-5" />
                                        </span>
                                        <div className="min-w-0">
                                            <p className="font-semibold text-[var(--codesprout-ink)]">{project.title}</p>
                                            <p className="mt-1 text-sm leading-6 text-[var(--codesprout-ink)]/72">{project.description}</p>
                                        </div>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                </section>

                <section className="scroll-mt-28">
                    <div className="relative overflow-hidden rounded-[2.75rem] bg-[linear-gradient(135deg,_rgba(19,138,114,0.96)_0%,_rgba(19,138,114,0.88)_56%,_rgba(84,183,211,0.82)_100%)] px-6 py-10 text-white shadow-[0_22px_60px_rgba(19,138,114,0.18)] sm:px-8 lg:px-10 lg:py-12">
                        <div
                            className="absolute top-8 right-8 hidden size-20 rounded-full bg-[rgba(255,255,255,0.14)] blur-2xl sm:block"
                            aria-hidden="true"
                        />
                        <div
                            className="absolute top-10 left-10 hidden size-16 rounded-full bg-[rgba(247,201,72,0.18)] blur-2xl sm:block"
                            aria-hidden="true"
                        />

                        <div className="relative grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                            <div className="space-y-4">
                                <p className="text-sm font-semibold tracking-[0.24em] text-white/80 uppercase">Let their coding adventure begin</p>
                                <h2 className="font-[family-name:var(--font-display)] text-4xl font-black tracking-tight sm:text-5xl">
                                    Let Their Coding Adventure Begin
                                </h2>
                                <p className="max-w-2xl text-lg leading-8 text-white/90">
                                    Give your child a playful and confident first step into typing, computer skills and real coding.
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-3 lg:justify-end">
                                <Button
                                    asChild
                                    className="h-14 rounded-full bg-[var(--codesprout-coral)] px-6 text-base font-semibold text-white shadow-[0_14px_34px_rgba(255,107,94,0.28)] hover:bg-[var(--codesprout-coral)]/90"
                                >
                                    <Link href={links.startAdventure}>Start the Adventure</Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    className="h-14 rounded-full border-white/30 bg-white/12 px-6 text-base font-semibold text-white hover:bg-white/18"
                                >
                                    <Link href={links.login}>{signInLabel}</Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <LandingFooter supportEmail={supportEmail} links={links} signInLabel={signInLabel} />
        </div>
    );
}

function LandingHeader({ links, signInLabel }: { links: WelcomePageProps['links']; signInLabel: string }) {
    return (
        <header className="sticky top-0 z-50 border-b border-white/70 bg-[rgba(255,248,234,0.92)] backdrop-blur-md">
            <div className="mx-auto flex w-full max-w-[1520px] items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <Link href={links.home} className="min-w-0" aria-label="CodeSprout home">
                    <LandingBrand compact />
                </Link>

                <nav aria-label="Primary" className="ml-auto hidden items-center gap-1 lg:flex">
                    {navItems.map((item) => (
                        <a
                            key={item.title}
                            href={item.href}
                            className="rounded-full px-4 py-2 text-sm font-semibold text-[var(--codesprout-ink)]/82 transition hover:bg-white hover:text-[var(--codesprout-leaf)] focus-visible:ring-2 focus-visible:ring-[var(--codesprout-leaf)] focus-visible:ring-offset-2 focus-visible:outline-hidden"
                        >
                            {item.title}
                        </a>
                    ))}
                </nav>

                <div className="ml-auto hidden items-center gap-3 lg:flex">
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 rounded-full border-[rgba(19,138,114,0.22)] bg-white px-5 text-sm font-semibold text-[var(--codesprout-leaf)] hover:bg-[rgba(19,138,114,0.04)]"
                    >
                        <Link href={links.login}>{signInLabel}</Link>
                    </Button>
                    <Button
                        asChild
                        className="h-11 rounded-full bg-[var(--codesprout-coral)] px-5 text-sm font-semibold text-white shadow-[0_12px_24px_rgba(255,107,94,0.24)] hover:bg-[var(--codesprout-coral)]/90"
                    >
                        <Link href={links.startAdventure}>Start the Adventure</Link>
                    </Button>
                </div>

                <div className="ml-auto lg:hidden">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="size-11 rounded-2xl bg-white text-[var(--codesprout-leaf)] shadow-sm">
                                <Menu className="size-5" />
                                <span className="sr-only">Open menu</span>
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right" className="w-full max-w-sm border-l-0 bg-[var(--codesprout-cream)] p-5">
                            <SheetTitle className="sr-only">Landing page navigation</SheetTitle>
                            <SheetHeader className="items-start text-left">
                                <LandingBrand compact />
                            </SheetHeader>

                            <div className="mt-6 space-y-2">
                                {navItems.map((item) => (
                                    <SheetClose asChild key={item.title}>
                                        <a
                                            href={item.href}
                                            className="flex items-center justify-between rounded-2xl bg-white px-4 py-4 text-sm font-semibold text-[var(--codesprout-ink)] shadow-sm"
                                        >
                                            {item.title}
                                            <ArrowRight className="size-4 text-[var(--codesprout-leaf)]" />
                                        </a>
                                    </SheetClose>
                                ))}
                            </div>

                            <div className="mt-6 grid gap-3">
                                <SheetClose asChild>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="h-12 rounded-full border-[rgba(19,138,114,0.22)] bg-white px-5 text-sm font-semibold text-[var(--codesprout-leaf)]"
                                    >
                                        <Link href={links.login}>{signInLabel}</Link>
                                    </Button>
                                </SheetClose>
                                <SheetClose asChild>
                                    <Button
                                        asChild
                                        className="h-12 rounded-full bg-[var(--codesprout-coral)] px-5 text-sm font-semibold text-white shadow-[0_12px_24px_rgba(255,107,94,0.24)]"
                                    >
                                        <Link href={links.startAdventure}>Start the Adventure</Link>
                                    </Button>
                                </SheetClose>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    );
}

function LandingFooter({ supportEmail, links, signInLabel }: { supportEmail: string | null; links: WelcomePageProps['links']; signInLabel: string }) {
    return (
        <footer className="border-t border-white/70 bg-white/82">
            <div className="mx-auto grid w-full max-w-[1520px] gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8">
                <div className="space-y-4">
                    <LandingBrand compact />
                    <p className="max-w-xl text-sm leading-7 text-[var(--codesprout-ink)]/74">
                        CodeSprout is the one-year, game-based computer readiness and early coding programme from ChildsBridge Academy for children
                        aged 6-7.
                    </p>
                    {supportEmail ? (
                        <p className="text-sm text-[var(--codesprout-ink)]/74">
                            Contact:{' '}
                            <a
                                href={`mailto:${supportEmail}`}
                                className="font-semibold text-[var(--codesprout-leaf)] underline decoration-2 underline-offset-4"
                            >
                                {supportEmail}
                            </a>
                        </p>
                    ) : null}
                </div>

                <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                    <FooterLinkGroup
                        title="Explore"
                        links={[
                            { label: 'Adventures', href: '#adventures' },
                            { label: 'How It Works', href: '#how-it-works' },
                            { label: 'Learning Path', href: '#learning-path' },
                        ]}
                    />
                    <FooterLinkGroup
                        title="For families"
                        links={[
                            { label: 'For Parents', href: '#parents' },
                            { label: signInLabel, href: links.login },
                        ]}
                    />
                    <FooterLinkGroup
                        title="Policy"
                        links={[
                            { label: 'Privacy', href: links.privacy },
                            { label: 'Terms', href: links.terms },
                        ]}
                    />
                    {supportEmail ? <FooterLinkGroup title="ChildsBridge" links={[{ label: 'Contact', href: `mailto:${supportEmail}` }]} /> : null}
                </div>
            </div>
        </footer>
    );
}

function FooterLinkGroup({ title, links }: { title: string; links: Array<{ label: string; href: string }> }) {
    return (
        <div className="space-y-3">
            <p className="text-xs font-semibold tracking-[0.24em] text-[var(--codesprout-ink)]/58 uppercase">{title}</p>
            <ul className="space-y-2">
                {links.map((link) => (
                    <li key={link.label}>
                        <a
                            href={link.href}
                            className="text-sm font-medium text-[var(--codesprout-ink)]/78 underline-offset-4 transition hover:text-[var(--codesprout-leaf)] hover:underline focus-visible:ring-2 focus-visible:ring-[var(--codesprout-leaf)] focus-visible:ring-offset-2 focus-visible:outline-hidden"
                        >
                            {link.label}
                        </a>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function DecorativeBackground() {
    return (
        <div className="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <span className="landing-float absolute top-28 left-4 size-10 rounded-full bg-[rgba(247,201,72,0.16)] blur-2xl" />
            <span className="landing-float absolute top-36 right-10 size-14 rounded-full bg-[rgba(84,183,211,0.18)] blur-3xl [animation-delay:1.1s]" />
            <span className="landing-float absolute top-[32rem] left-[42%] hidden size-12 rounded-full bg-[rgba(255,107,94,0.14)] blur-2xl [animation-delay:2.2s] lg:block" />
            <span className="absolute top-[48rem] right-[12%] hidden size-8 rounded-full bg-[rgba(19,138,114,0.14)] blur-xl lg:block" />
        </div>
    );
}
