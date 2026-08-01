import { MetricCard } from '@/components/dashboard/metric-card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BookOpenCheck, ShieldCheck, Users, Wand2 } from 'lucide-react';

interface ParentChildProfileProps {
    child: {
        id: number;
        name: string;
        learner_id: string;
        avatar_url: string | null;
        current_world: string;
        class_count: number;
    };
    relationship: {
        relationship_type: string;
        is_primary_contact: boolean;
        can_manage_pin: boolean;
        can_view_progress: boolean;
    };
    enrolledClasses: Array<{
        id: number;
        name: string;
        code: string;
        cohort: string | null;
        is_primary_class: boolean;
    }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Parent Dashboard',
        href: '/parent/dashboard',
    },
    {
        title: 'Child Profile',
        href: '#',
    },
];

export default function ParentChildProfile({ child, relationship, enrolledClasses }: ParentChildProfileProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${child.name} Profile`} />

            <div className="space-y-6 p-4 md:p-6">
                <section className="flex flex-col gap-4 rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)] md:flex-row md:items-center md:justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl bg-emerald-100 text-3xl font-black text-emerald-800">
                            {child.avatar_url ? (
                                <img src={child.avatar_url} alt={child.name} className="h-full w-full object-cover" />
                            ) : (
                                child.name.slice(0, 1)
                            )}
                        </div>
                        <div>
                            <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">Linked child</p>
                            <h1 className="mt-1 font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)]">
                                {child.name}
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                Learner ID {child.learner_id} - {relationship.relationship_type}
                            </p>
                        </div>
                    </div>

                    <Link
                        href={route('parent.dashboard')}
                        className="inline-flex items-center rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-800"
                    >
                        Back to parent dashboard
                    </Link>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    <MetricCard
                        title="Current world"
                        value={child.current_world}
                        description="The main learning world linked to this child right now."
                        icon={<BookOpenCheck className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Class count"
                        value={String(child.class_count)}
                        description="Classes visible to the linked parent account."
                        accent="from-sky-500/20 to-cyan-500/20"
                        icon={<Users className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Parent access"
                        value={relationship.can_view_progress ? 'Enabled' : 'Limited'}
                        description={
                            relationship.can_manage_pin ? 'PIN recovery can be managed here.' : 'PIN recovery is managed by another guardian.'
                        }
                        accent="from-amber-500/20 to-orange-500/20"
                        icon={<ShieldCheck className="h-5 w-5" />}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                    <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                        <h2 className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">Relationship summary</h2>
                        <div className="text-muted-foreground mt-4 space-y-3 text-sm">
                            <p>
                                Relationship type: <span className="font-semibold text-[var(--foreground)]">{relationship.relationship_type}</span>
                            </p>
                            <p>
                                Primary contact:{' '}
                                <span className="font-semibold text-[var(--foreground)]">{relationship.is_primary_contact ? 'Yes' : 'No'}</span>
                            </p>
                            <p>
                                Can manage PIN:{' '}
                                <span className="font-semibold text-[var(--foreground)]">{relationship.can_manage_pin ? 'Yes' : 'No'}</span>
                            </p>
                            <p>
                                Can view progress:{' '}
                                <span className="font-semibold text-[var(--foreground)]">{relationship.can_view_progress ? 'Yes' : 'No'}</span>
                            </p>
                        </div>
                    </div>

                    <div className="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                        <div className="flex items-center justify-between">
                            <h2 className="font-[family-name:var(--font-display)] text-xl font-black text-[var(--foreground)]">Enrolled classes</h2>
                            <Wand2 className="h-5 w-5 text-emerald-700" />
                        </div>
                        <div className="mt-4 space-y-3">
                            {enrolledClasses.map((classroom) => (
                                <div key={classroom.id} className="bg-muted/40 rounded-3xl p-4">
                                    <p className="font-semibold text-[var(--foreground)]">{classroom.name}</p>
                                    <p className="text-muted-foreground text-sm">{classroom.code}</p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Cohort: {classroom.cohort ?? 'Pending'}
                                        {classroom.is_primary_class ? ' - Primary class' : ''}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
