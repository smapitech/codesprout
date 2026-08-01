import { MetricCard } from '@/components/dashboard/metric-card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, ClipboardCheck, Code2, Layers3, ShieldCheck, UserRoundCheck, Users } from 'lucide-react';

interface AdminDashboardProps {
    totals: {
        users: number;
        administrators: number;
        teachers: number;
        parents: number;
        children: number;
    };
    classes: {
        total: number;
        active: number;
        cohorts: number;
        current_cohort: string | null;
    };
    setupStatus: {
        configuredSettings: number;
        platformReady: boolean;
    };
    recentActivity: Array<{
        id: number;
        action: string;
        actor: string;
        subject: string;
        created_at: string | null;
    }>;
    operations: {
        assignmentReviews: number;
        htmlProjectReviews: number;
        htmlCompletionsToday: number;
        childrenWithoutClass: number;
        childrenWithoutParent: number;
        teachersWithoutClass: number;
    };
    quickActions: Array<{ label: string; description: string; href: string }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Administrator Dashboard',
        href: '/admin/dashboard',
    },
];

export default function AdminDashboard({ totals, classes, setupStatus, recentActivity, operations, quickActions }: AdminDashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Administrator Dashboard" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Administrator</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)]">
                        Platform overview
                    </h1>
                    <p className="text-muted-foreground max-w-2xl">
                        Manage the full academy operation from connected accounts and classes to assignments, progress and Phase 7 HTML projects.
                    </p>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        title="Assignment reviews"
                        value={String(operations.assignmentReviews)}
                        description="Submitted work awaiting an authorised teacher."
                        icon={<ClipboardCheck className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="HTML project reviews"
                        value={String(operations.htmlProjectReviews)}
                        description={`${operations.htmlCompletionsToday} HTML exercises completed today.`}
                        accent="from-sky-500/20 to-cyan-500/20"
                        icon={<Code2 className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Unconnected children"
                        value={String(Math.max(operations.childrenWithoutClass, operations.childrenWithoutParent))}
                        description={`${operations.childrenWithoutClass} without a class; ${operations.childrenWithoutParent} without a parent link.`}
                        accent="from-amber-500/20 to-orange-500/20"
                        icon={<Users className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Unassigned teachers"
                        value={String(operations.teachersWithoutClass)}
                        description="Teachers requiring a class connection."
                        accent="from-violet-500/20 to-fuchsia-500/20"
                        icon={<UserRoundCheck className="h-5 w-5" />}
                    />
                </section>

                <section className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                    <div className="mb-5">
                        <h2 className="font-[family-name:var(--font-display)] text-xl font-bold">Management workspace</h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            All primary administrator workflows are now connected from this dashboard.
                        </p>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {quickActions.map((action) => (
                            <Link
                                key={action.href}
                                href={action.href}
                                className="group rounded-3xl border border-emerald-100 bg-emerald-50/60 p-5 transition hover:-translate-y-0.5 hover:bg-emerald-50"
                            >
                                <p className="font-bold text-emerald-950">{action.label}</p>
                                <p className="mt-2 text-sm leading-6 text-emerald-800">{action.description}</p>
                                <span className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700">
                                    Open <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" />
                                </span>
                            </Link>
                        ))}
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        title="Total users"
                        value={String(totals.users)}
                        description="All active platform accounts."
                        icon={<Users className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Class totals"
                        value={String(classes.total)}
                        description={`${classes.active} active class groups across ${classes.cohorts} cohorts.`}
                        accent="from-sky-500/20 to-cyan-500/20"
                        icon={<Layers3 className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Setup status"
                        value={setupStatus.platformReady ? 'Ready' : 'Pending'}
                        description={`${setupStatus.configuredSettings} settings records are configured.`}
                        accent="from-amber-500/20 to-orange-500/20"
                        icon={<ShieldCheck className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Current cohort"
                        value={classes.current_cohort ?? 'Not set'}
                        description="The active academic cohort for the school year."
                        accent="from-violet-500/20 to-fuchsia-500/20"
                        icon={<BookOpen className="h-5 w-5" />}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
                    <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                        <div className="mb-6 flex items-center justify-between">
                            <div>
                                <h2 className="font-[family-name:var(--font-display)] text-xl font-bold text-[var(--foreground)]">
                                    Recent audit activity
                                </h2>
                                <p className="text-muted-foreground text-sm">Track sensitive platform actions as the system grows.</p>
                            </div>
                            <ShieldCheck className="h-5 w-5 text-emerald-700" />
                        </div>

                        <div className="space-y-4">
                            {recentActivity.length > 0 ? (
                                recentActivity.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex flex-col gap-2 rounded-3xl border border-emerald-100 bg-emerald-50/60 p-4 md:flex-row md:items-center md:justify-between"
                                    >
                                        <div>
                                            <p className="font-semibold text-emerald-950">{item.action}</p>
                                            <p className="text-sm text-emerald-800">
                                                {item.actor} - {item.subject}
                                            </p>
                                        </div>
                                        <p className="text-xs font-medium text-emerald-700">
                                            {item.created_at ? new Date(item.created_at).toLocaleString() : 'Just now'}
                                        </p>
                                    </div>
                                ))
                            ) : (
                                <div className="rounded-3xl border border-dashed border-emerald-200 bg-emerald-50/40 p-6 text-sm text-emerald-900">
                                    No audit records yet. Seeded data will appear here once the platform starts tracking actions.
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                        <h2 className="font-[family-name:var(--font-display)] text-xl font-bold text-[var(--foreground)]">Platform setup</h2>
                        <p className="text-muted-foreground mt-2 text-sm">This panel gives administrators a quick readiness check.</p>

                        <div className="mt-6 space-y-4">
                            <div className="bg-muted/40 rounded-3xl p-4">
                                <p className="text-sm font-semibold text-[var(--foreground)]">Accounts</p>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {totals.administrators} administrators, {totals.teachers} teachers, {totals.parents} parents and {totals.children}{' '}
                                    children.
                                </p>
                            </div>
                            <div className="bg-muted/40 rounded-3xl p-4">
                                <p className="text-sm font-semibold text-[var(--foreground)]">Learning structure</p>
                                <p className="text-muted-foreground mt-1 text-sm">{classes.total} classes prepared across the current cohort.</p>
                            </div>
                            <div className="bg-muted/40 rounded-3xl p-4">
                                <p className="text-sm font-semibold text-[var(--foreground)]">Readiness</p>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {setupStatus.platformReady
                                        ? 'Core records are configured and the operational modules are connected.'
                                        : 'Add cohorts and settings to complete the setup.'}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
