import { MetricCard } from '@/components/dashboard/metric-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { BookUser, GraduationCap, Link2, School, ShieldCheck, UserPlus, Users } from 'lucide-react';
import { type FormEvent } from 'react';

type Option = { id: number; name: string; email?: string | null; learnerId?: string | null; staffCode?: string | null };
type Classroom = {
    id: number;
    name: string;
    code: string;
    active: boolean;
    cohort: string | null;
    teachersCount: number;
    learnersCount: number;
    teachers: Array<{ id: number; name: string; primary: boolean }>;
    learners: Array<{ id: number; name: string; learnerId: string | null }>;
};

interface SchoolManagementProps {
    summary: {
        teachers: number;
        parents: number;
        children: number;
        activeClasses: number;
        childrenWithoutClass: number;
        childrenWithoutParent: number;
        teachersWithoutClass: number;
    };
    users: Array<{
        id: number;
        name: string;
        email: string | null;
        role: string | null;
        roleLabel: string | null;
        active: boolean;
        learnerId: string | null;
        staffCode: string | null;
    }>;
    teachers: Option[];
    parents: Option[];
    children: Option[];
    classes: Classroom[];
    parentLinks: Array<{ id: number; parent: string | null; child: string | null; learnerId: string | null; relationship: string; primary: boolean }>;
    cohorts: Array<{ id: number; name: string; academic_year: string; is_current: boolean }>;
    roleOptions: Array<{ value: string; label: string }>;
    actions: { createUser: string; createClass: string; connect: string };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Dashboard', href: '/admin/dashboard' },
    { title: 'School Management', href: '/admin/school' },
];

const selectClass =
    'border-input bg-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none';

export default function SchoolManagement({
    summary,
    users,
    teachers,
    parents,
    children,
    classes,
    parentLinks,
    cohorts,
    roleOptions,
    actions,
}: SchoolManagementProps) {
    const userForm = useForm({
        role: 'teacher',
        name: '',
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        learner_id: '',
        pin: '',
        staff_code: '',
        job_title: 'Teacher',
        subject_focus: '',
    });
    const classForm = useForm({ academic_cohort_id: String(cohorts[0]?.id ?? ''), class_code: '', name: '', description: '', is_active: true });
    const connectionForm = useForm({
        connection_type: 'teacher_class',
        teacher_id: '',
        parent_id: '',
        child_id: '',
        class_id: '',
        relationship_type: 'guardian',
        role_label: 'Class teacher',
        is_primary: true as boolean,
    });

    const submitUser = (event: FormEvent) => {
        event.preventDefault();
        userForm.post(actions.createUser, { preserveScroll: true, onSuccess: () => userForm.reset() });
    };
    const submitClass = (event: FormEvent) => {
        event.preventDefault();
        classForm.post(actions.createClass, { preserveScroll: true, onSuccess: () => classForm.reset('class_code', 'name', 'description') });
    };
    const submitConnection = (event: FormEvent) => {
        event.preventDefault();
        connectionForm.post(actions.connect, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="School Management" />
            <div className="space-y-8 p-4 md:p-6">
                <section className="rounded-[2rem] bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-800 p-6 text-white shadow-xl md:p-8">
                    <p className="text-sm font-bold tracking-[0.2em] text-emerald-200 uppercase">School administration</p>
                    <h1 className="mt-2 text-3xl font-black tracking-tight md:text-4xl">Accounts, classes and family connections</h1>
                    <p className="mt-3 max-w-3xl text-sm leading-7 text-emerald-50/85 md:text-base">
                        Create secure role accounts, assign teachers, enrol children and link parents from one authorised workspace. Every change is
                        recorded in the audit trail.
                    </p>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        title="Teachers"
                        value={String(summary.teachers)}
                        description={`${summary.teachersWithoutClass} not yet assigned to a class.`}
                        icon={<GraduationCap className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Parents"
                        value={String(summary.parents)}
                        description="Secure adult accounts with child-scoped access."
                        accent="from-sky-500/20 to-cyan-500/20"
                        icon={<BookUser className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Children"
                        value={String(summary.children)}
                        description={`${summary.childrenWithoutParent} need a parent link; ${summary.childrenWithoutClass} need a class.`}
                        accent="from-amber-500/20 to-orange-500/20"
                        icon={<Users className="h-5 w-5" />}
                    />
                    <MetricCard
                        title="Active classes"
                        value={String(summary.activeClasses)}
                        description="Teacher and learner scopes are enforced per class."
                        accent="from-violet-500/20 to-fuchsia-500/20"
                        icon={<School className="h-5 w-5" />}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <Panel
                        title="Create a role account"
                        description="Adult passwords and child PINs are write-only and never displayed again."
                        icon={<UserPlus className="h-5 w-5" />}
                    >
                        <form onSubmit={submitUser} className="grid gap-4 md:grid-cols-2">
                            <Field label="Account role" error={userForm.errors.role}>
                                <select value={userForm.data.role} onChange={(e) => userForm.setData('role', e.target.value)} className={selectClass}>
                                    {roleOptions.map((role) => (
                                        <option key={role.value} value={role.value}>
                                            {role.label}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                            <Field label="Display name" error={userForm.errors.name}>
                                <Input value={userForm.data.name} onChange={(e) => userForm.setData('name', e.target.value)} required />
                            </Field>
                            <Field label="First name" error={userForm.errors.first_name}>
                                <Input value={userForm.data.first_name} onChange={(e) => userForm.setData('first_name', e.target.value)} required />
                            </Field>
                            <Field label="Last name" error={userForm.errors.last_name}>
                                <Input value={userForm.data.last_name} onChange={(e) => userForm.setData('last_name', e.target.value)} />
                            </Field>

                            {userForm.data.role !== 'child' && (
                                <>
                                    <Field label="Login email" error={userForm.errors.email}>
                                        <Input
                                            type="email"
                                            value={userForm.data.email}
                                            onChange={(e) => userForm.setData('email', e.target.value)}
                                            required
                                        />
                                    </Field>
                                    <Field label="Temporary password" error={userForm.errors.password}>
                                        <Input
                                            type="password"
                                            value={userForm.data.password}
                                            onChange={(e) => userForm.setData('password', e.target.value)}
                                            required
                                        />
                                    </Field>
                                </>
                            )}

                            {userForm.data.role === 'teacher' && (
                                <>
                                    <Field label="Staff code" error={userForm.errors.staff_code}>
                                        <Input
                                            value={userForm.data.staff_code}
                                            onChange={(e) => userForm.setData('staff_code', e.target.value)}
                                            required
                                        />
                                    </Field>
                                    <Field label="Subject focus" error={userForm.errors.subject_focus}>
                                        <Input
                                            value={userForm.data.subject_focus}
                                            onChange={(e) => userForm.setData('subject_focus', e.target.value)}
                                        />
                                    </Field>
                                </>
                            )}

                            {userForm.data.role === 'child' && (
                                <>
                                    <Field label="Learner ID" error={userForm.errors.learner_id}>
                                        <Input
                                            value={userForm.data.learner_id}
                                            onChange={(e) => userForm.setData('learner_id', e.target.value.toUpperCase())}
                                            placeholder="CB-LEARN-1003"
                                            required
                                        />
                                    </Field>
                                    <Field label="Private login PIN" error={userForm.errors.pin}>
                                        <Input
                                            type="password"
                                            inputMode="numeric"
                                            value={userForm.data.pin}
                                            onChange={(e) => userForm.setData('pin', e.target.value)}
                                            required
                                        />
                                    </Field>
                                </>
                            )}
                            <div className="md:col-span-2">
                                <Button disabled={userForm.processing} type="submit">
                                    {userForm.processing ? 'Creating…' : 'Create account'}
                                </Button>
                            </div>
                        </form>
                    </Panel>

                    <Panel
                        title="Create a class"
                        description="Classes define the scope teachers can access and the learning group children belong to."
                        icon={<School className="h-5 w-5" />}
                    >
                        <form onSubmit={submitClass} className="grid gap-4 md:grid-cols-2">
                            <Field label="Academic cohort" error={classForm.errors.academic_cohort_id}>
                                <select
                                    value={classForm.data.academic_cohort_id}
                                    onChange={(e) => classForm.setData('academic_cohort_id', e.target.value)}
                                    className={selectClass}
                                    required
                                >
                                    {cohorts.map((cohort) => (
                                        <option key={cohort.id} value={cohort.id}>
                                            {cohort.name} ({cohort.academic_year}){cohort.is_current ? ' — Current' : ''}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                            <Field label="Class code" error={classForm.errors.class_code}>
                                <Input
                                    value={classForm.data.class_code}
                                    onChange={(e) => classForm.setData('class_code', e.target.value.toUpperCase())}
                                    required
                                />
                            </Field>
                            <Field label="Class name" error={classForm.errors.name}>
                                <Input value={classForm.data.name} onChange={(e) => classForm.setData('name', e.target.value)} required />
                            </Field>
                            <Field label="Description" error={classForm.errors.description}>
                                <Input value={classForm.data.description} onChange={(e) => classForm.setData('description', e.target.value)} />
                            </Field>
                            <div className="md:col-span-2">
                                <Button disabled={classForm.processing || cohorts.length === 0} type="submit">
                                    {classForm.processing ? 'Creating…' : 'Create class'}
                                </Button>
                            </div>
                        </form>
                    </Panel>
                </section>

                <Panel
                    title="Connect the school community"
                    description="These connections power teacher scope, child enrolment and the parent dashboard."
                    icon={<Link2 className="h-5 w-5" />}
                >
                    <form onSubmit={submitConnection} className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <Field label="Connection">
                            <select
                                value={connectionForm.data.connection_type}
                                onChange={(e) => connectionForm.setData('connection_type', e.target.value)}
                                className={selectClass}
                            >
                                <option value="teacher_class">Teacher → class</option>
                                <option value="child_class">Child → class</option>
                                <option value="parent_child">Parent → child</option>
                            </select>
                        </Field>
                        {connectionForm.data.connection_type === 'teacher_class' && (
                            <Field label="Teacher" error={connectionForm.errors.teacher_id}>
                                <OptionSelect
                                    options={teachers}
                                    value={connectionForm.data.teacher_id}
                                    onChange={(value) => connectionForm.setData('teacher_id', value)}
                                />
                            </Field>
                        )}
                        {connectionForm.data.connection_type === 'parent_child' && (
                            <Field label="Parent" error={connectionForm.errors.parent_id}>
                                <OptionSelect
                                    options={parents}
                                    value={connectionForm.data.parent_id}
                                    onChange={(value) => connectionForm.setData('parent_id', value)}
                                />
                            </Field>
                        )}
                        {connectionForm.data.connection_type !== 'teacher_class' && (
                            <Field label="Child" error={connectionForm.errors.child_id}>
                                <OptionSelect
                                    options={children}
                                    value={connectionForm.data.child_id}
                                    onChange={(value) => connectionForm.setData('child_id', value)}
                                />
                            </Field>
                        )}
                        {connectionForm.data.connection_type !== 'parent_child' && (
                            <Field label="Class" error={connectionForm.errors.class_id}>
                                <select
                                    value={connectionForm.data.class_id}
                                    onChange={(e) => connectionForm.setData('class_id', e.target.value)}
                                    className={selectClass}
                                    required
                                >
                                    <option value="">Choose a class</option>
                                    {classes.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.name} ({item.code})
                                        </option>
                                    ))}
                                </select>
                            </Field>
                        )}
                        <Field label={connectionForm.data.connection_type === 'parent_child' ? 'Relationship' : 'Role label'}>
                            <Input
                                value={
                                    connectionForm.data.connection_type === 'parent_child'
                                        ? connectionForm.data.relationship_type
                                        : connectionForm.data.role_label
                                }
                                onChange={(e) =>
                                    connectionForm.data.connection_type === 'parent_child'
                                        ? connectionForm.setData('relationship_type', e.target.value)
                                        : connectionForm.setData('role_label', e.target.value)
                                }
                            />
                        </Field>
                        <div className="flex items-end gap-3 pb-1">
                            <label className="flex items-center gap-2 text-sm font-medium">
                                <input
                                    type="checkbox"
                                    checked={connectionForm.data.is_primary}
                                    onChange={(e) => connectionForm.setData('is_primary', e.target.checked)}
                                />{' '}
                                Primary connection
                            </label>
                        </div>
                        <div className="flex items-end">
                            <Button disabled={connectionForm.processing} type="submit">
                                {connectionForm.processing ? 'Connecting…' : 'Save connection'}
                            </Button>
                        </div>
                    </form>
                </Panel>

                <section className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <Panel
                        title="Class connections"
                        description="A live view of teacher and learner scope."
                        icon={<ShieldCheck className="h-5 w-5" />}
                    >
                        <div className="grid gap-4 md:grid-cols-2">
                            {classes.map((classroom) => (
                                <article key={classroom.id} className="rounded-3xl border border-emerald-100 bg-emerald-50/50 p-5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 className="font-bold text-emerald-950">{classroom.name}</h3>
                                            <p className="text-sm text-emerald-800">
                                                {classroom.code} · {classroom.cohort}
                                            </p>
                                        </div>
                                        <span className="rounded-full bg-white px-3 py-1 text-xs font-bold text-emerald-800">
                                            {classroom.active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                    <p className="mt-4 text-sm font-semibold text-slate-800">Teachers ({classroom.teachersCount})</p>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {classroom.teachers.map((teacher) => `${teacher.name}${teacher.primary ? ' · Primary' : ''}`).join(', ') ||
                                            'No teacher assigned'}
                                    </p>
                                    <p className="mt-4 text-sm font-semibold text-slate-800">Learners ({classroom.learnersCount})</p>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {classroom.learners
                                            .slice(0, 6)
                                            .map((child) => child.name)
                                            .join(', ') || 'No learners enrolled'}
                                        {classroom.learners.length > 6 ? ` and ${classroom.learners.length - 6} more` : ''}
                                    </p>
                                </article>
                            ))}
                        </div>
                    </Panel>

                    <Panel
                        title="Parent links"
                        description="Parents only see children explicitly connected here."
                        icon={<BookUser className="h-5 w-5" />}
                    >
                        <div className="space-y-3">
                            {parentLinks.map((link) => (
                                <div key={link.id} className="rounded-2xl bg-slate-50 p-4">
                                    <p className="font-semibold text-slate-900">
                                        {link.parent} → {link.child}
                                    </p>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {link.relationship} · {link.learnerId ?? 'Learner ID pending'}
                                        {link.primary ? ' · Primary contact' : ''}
                                    </p>
                                </div>
                            ))}
                            {parentLinks.length === 0 && (
                                <p className="rounded-2xl border border-dashed p-5 text-sm text-slate-600">No parent-child links yet.</p>
                            )}
                        </div>
                    </Panel>
                </section>

                <Panel
                    title="Account directory"
                    description="Suspend access without deleting learning history or relationship records."
                    icon={<Users className="h-5 w-5" />}
                >
                    <div className="overflow-x-auto rounded-2xl border">
                        <table className="w-full min-w-[760px] text-left text-sm">
                            <thead className="bg-slate-50 text-slate-700">
                                <tr>
                                    <th className="p-4">Name</th>
                                    <th className="p-4">Role</th>
                                    <th className="p-4">Login / ID</th>
                                    <th className="p-4">Status</th>
                                    <th className="p-4 text-right">Access</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.map((user) => (
                                    <tr key={user.id} className="border-t">
                                        <td className="p-4 font-semibold">{user.name}</td>
                                        <td className="p-4">{user.roleLabel ?? 'Unassigned'}</td>
                                        <td className="p-4 text-slate-600">{user.learnerId ?? user.staffCode ?? user.email ?? 'Not configured'}</td>
                                        <td className="p-4">
                                            <span
                                                className={`rounded-full px-3 py-1 text-xs font-bold ${user.active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'}`}
                                            >
                                                {user.active ? 'Active' : 'Suspended'}
                                            </span>
                                        </td>
                                        <td className="p-4 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    router.patch(
                                                        route('admin.school.users.status', user.id),
                                                        { active: !user.active },
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                {user.active ? 'Suspend' : 'Activate'}
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Panel>
            </div>
        </AppLayout>
    );
}

function Panel({ title, description, icon, children }: { title: string; description: string; icon: React.ReactNode; children: React.ReactNode }) {
    return (
        <section className="rounded-[2rem] border border-white/70 bg-white/95 p-5 shadow-[0_12px_40px_rgba(38,84,63,0.08)] md:p-6">
            <div className="mb-6 flex items-start gap-3">
                <div className="rounded-2xl bg-emerald-100 p-3 text-emerald-800">{icon}</div>
                <div>
                    <h2 className="text-xl font-black text-slate-950">{title}</h2>
                    <p className="mt-1 text-sm leading-6 text-slate-600">{description}</p>
                </div>
            </div>
            {children}
        </section>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
            {error && <p className="text-sm font-medium text-red-700">{error}</p>}
        </div>
    );
}

function OptionSelect({ options, value, onChange }: { options: Option[]; value: string; onChange: (value: string) => void }) {
    return (
        <select value={value} onChange={(e) => onChange(e.target.value)} className={selectClass} required>
            <option value="">Choose an account</option>
            {options.map((option) => (
                <option key={option.id} value={option.id}>
                    {option.name}
                    {option.learnerId ? ` (${option.learnerId})` : option.staffCode ? ` (${option.staffCode})` : ''}
                </option>
            ))}
        </select>
    );
}
