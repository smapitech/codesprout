import { MetricCard } from '@/components/dashboard/metric-card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { CalendarDays, Sparkles, Star, Users } from 'lucide-react';

interface LearnerProgress {
    id: number;
    name: string;
    learner_id: string | null;
    level: string;
    stars: number;
    experience: number;
    streak: number;
    completed_missions: number;
    last_learning_date: string | null;
}

interface TeacherProgressProps {
    classes: Array<{ id: number; name: string; learners_count: number }>;
    learners: LearnerProgress[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Learner Progress', href: '/teacher/progress' }];

export default function TeacherProgress({ classes, learners }: TeacherProgressProps) {
    const learnersNeedingEncouragement = learners.filter((learner) => !learner.last_learning_date).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teacher Progress" />
            <main className="space-y-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Teacher</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight">Class progress monitor</h1>
                    <p className="text-muted-foreground max-w-3xl">
                        Review encouraging, validated learning progress for children in your assigned classes only.
                    </p>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        title="Classes"
                        value={String(classes.length)}
                        description="Assigned teaching groups."
                        icon={<Users className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Learners"
                        value={String(learners.length)}
                        description="Scoped to your classes."
                        icon={<Sparkles className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Need encouragement"
                        value={String(learnersNeedingEncouragement)}
                        description="No recent meaningful activity recorded."
                        icon={<CalendarDays className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Stars earned"
                        value={String(learners.reduce((sum, learner) => sum + learner.stars, 0))}
                        description="From validated events."
                        icon={<Star className="h-5 w-5" />}
                    />
                </section>

                <section className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                    <h2 className="font-[family-name:var(--font-display)] text-xl font-bold">Learner progress</h2>
                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full min-w-[820px] text-left text-sm">
                            <thead className="text-xs tracking-[0.16em] text-emerald-700 uppercase">
                                <tr>
                                    <th className="py-3">Learner</th>
                                    <th>Level</th>
                                    <th>Stars</th>
                                    <th>XP</th>
                                    <th>Streak</th>
                                    <th>Missions</th>
                                    <th>State</th>
                                </tr>
                            </thead>
                            <tbody>
                                {learners.map((learner) => (
                                    <tr key={learner.id} className="border-t border-emerald-100">
                                        <td className="py-4">
                                            <p className="font-semibold">{learner.name}</p>
                                            <p className="text-muted-foreground text-xs">{learner.learner_id ?? 'Learner ID pending'}</p>
                                        </td>
                                        <td>{learner.level}</td>
                                        <td>{learner.stars}</td>
                                        <td>{learner.experience}</td>
                                        <td>{learner.streak} days</td>
                                        <td>{learner.completed_missions}</td>
                                        <td>
                                            <Badge variant="outline">
                                                {learner.last_learning_date ? 'Growing well' : 'May benefit from more practice'}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </AppLayout>
    );
}
