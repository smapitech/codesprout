import { MetricCard } from '@/components/dashboard/metric-card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Award, BarChart3, Medal, Sparkles, Star } from 'lucide-react';

interface RewardRule {
    id: number;
    name: string;
    event_type: string;
    reward_type: string;
    reward_amount: number;
    repeat_policy: string;
    status: string;
    version: number;
    badge: string | null;
}

interface BadgeDefinition {
    id: number;
    name: string;
    category: string;
    description: string;
    status: string;
    awards_count: number;
}

interface LearnerLevel {
    id: number;
    name: string;
    level_number: number;
    xp_threshold: number;
    status: string;
}

interface AdminRewardsProps {
    summary: {
        totals: {
            profiles: number;
            ledger_entries: number;
            badges_awarded: number;
            curriculum_progress_records: number;
            skill_progress_records: number;
        };
    };
    rules: { data: RewardRule[] };
    badges: BadgeDefinition[];
    levels: LearnerLevel[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Rewards', href: '/admin/rewards' }];

export default function AdminRewards({ summary, rules, badges, levels }: AdminRewardsProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reward Rules and Progress" />

            <main className="space-y-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Administrator</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)]">
                        Rewards and learner progress
                    </h1>
                    <p className="text-muted-foreground max-w-3xl">
                        Manage safe, server-controlled reward rules, badge definitions and level thresholds. Rewards are awarded from validated
                        events, never from child-submitted totals.
                    </p>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <MetricCard
                        title="Profiles"
                        value={String(summary.totals.profiles)}
                        description="Cached learner projections."
                        icon={<Sparkles className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Ledger"
                        value={String(summary.totals.ledger_entries)}
                        description="Append-focused reward history."
                        icon={<BarChart3 className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Badges"
                        value={String(summary.totals.badges_awarded)}
                        description="Historical badge awards."
                        icon={<Medal className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Curriculum"
                        value={String(summary.totals.curriculum_progress_records)}
                        description="Completion records."
                        icon={<Award className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Skills"
                        value={String(summary.totals.skill_progress_records)}
                        description="Validated evidence records."
                        icon={<Star className="h-5 w-5" />}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                    <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                        <h2 className="font-[family-name:var(--font-display)] text-xl font-bold text-[var(--foreground)]">
                            Published and draft rules
                        </h2>
                        <div className="mt-4 overflow-x-auto">
                            <table className="w-full min-w-[760px] text-left text-sm">
                                <thead className="text-xs tracking-[0.16em] text-emerald-700 uppercase">
                                    <tr>
                                        <th className="py-3">Rule</th>
                                        <th>Event</th>
                                        <th>Reward</th>
                                        <th>Repeat</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rules.data.map((rule) => (
                                        <tr key={rule.id} className="border-t border-emerald-100">
                                            <td className="py-4 font-semibold">
                                                {rule.name} v{rule.version}
                                            </td>
                                            <td>{rule.event_type}</td>
                                            <td>{rule.badge ?? `${rule.reward_amount} ${rule.reward_type}`}</td>
                                            <td>{rule.repeat_policy.replaceAll('_', ' ')}</td>
                                            <td>
                                                <Badge variant="outline">{rule.status}</Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                            <h2 className="font-[family-name:var(--font-display)] text-xl font-bold">Badge definitions</h2>
                            <div className="mt-4 space-y-3">
                                {badges.slice(0, 8).map((badge) => (
                                    <div key={badge.id} className="rounded-3xl bg-emerald-50/60 p-4">
                                        <div className="flex items-center justify-between gap-3">
                                            <p className="font-semibold">{badge.name}</p>
                                            <Badge variant="outline">{badge.status}</Badge>
                                        </div>
                                        <p className="text-muted-foreground mt-1 text-sm">{badge.description}</p>
                                        <p className="mt-2 text-xs font-semibold text-emerald-800">{badge.awards_count} awards</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                            <h2 className="font-[family-name:var(--font-display)] text-xl font-bold">Level thresholds</h2>
                            <ol className="mt-4 space-y-2">
                                {levels.map((level) => (
                                    <li key={level.id} className="flex items-center justify-between rounded-2xl bg-amber-50/70 px-4 py-3 text-sm">
                                        <span className="font-semibold">
                                            {level.level_number}. {level.name}
                                        </span>
                                        <span>{level.xp_threshold} XP</span>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </div>
                </section>
            </main>
        </AppLayout>
    );
}
