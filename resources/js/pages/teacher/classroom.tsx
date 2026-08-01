import { MetricCard } from '@/components/dashboard/metric-card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ClipboardList, Users, Wand2 } from 'lucide-react';

interface TeacherClassroomProps {
    classroom: {
        id: number;
        name: string;
        code: string;
        cohort: string | null;
        is_active: boolean;
        learners_count: number;
    };
    learners: Array<{
        id: number;
        name: string;
        learner_id: string | null;
    }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teacher Dashboard',
        href: '/teacher/dashboard',
    },
    {
        title: 'Classroom',
        href: '#',
    },
];

export default function TeacherClassroom({ classroom, learners }: TeacherClassroomProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${classroom.name} Classroom`} />

            <div className="space-y-6 p-4 md:p-6">
                <section className="flex flex-col gap-4 rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)] md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Teacher classroom</p>
                        <h1 className="mt-1 font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)]">
                            {classroom.name}
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            Class code {classroom.code} - Cohort {classroom.cohort ?? 'Pending'}
                        </p>
                    </div>

                    <Link
                        href={route('teacher.dashboard')}
                        className="inline-flex items-center rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-800"
                    >
                        Back to teacher dashboard
                    </Link>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    <MetricCard
                        title="Learners"
                        value={String(classroom.learners_count)}
                        description="Only assigned learners appear here."
                        icon={<Users className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Class status"
                        value={classroom.is_active ? 'Active' : 'Paused'}
                        description="The sample class is ready for phase 1 testing."
                        accent="from-sky-500/20 to-cyan-500/20"
                        icon={<ClipboardList className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Upcoming work"
                        value="Preview"
                        description="Assignments and learner feedback will live here later."
                        accent="from-amber-500/20 to-orange-500/20"
                        icon={<Wand2 className="h-5 w-5" />}
                    />
                </section>

                <section className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                    <h2 className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">Assigned learners</h2>
                    <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {learners.map((learner) => (
                            <div key={learner.id} className="bg-muted/40 rounded-3xl p-4">
                                <p className="font-semibold text-[var(--foreground)]">{learner.name}</p>
                                <p className="text-muted-foreground mt-1 text-sm">{learner.learner_id ?? 'Learner ID pending'}</p>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
