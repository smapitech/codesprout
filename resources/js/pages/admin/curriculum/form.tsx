import { CurriculumForm } from '@/components/curriculum/curriculum-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface CurriculumFormPageProps {
    mode: 'create' | 'edit';
    curriculum: Record<string, string> | null;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    modeLabel: string;
    statusOptions: Array<{ value: string; label: string }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrator Dashboard', href: '/admin/dashboard' },
    { title: 'Curriculum', href: '/admin/curriculum' },
];

export default function CurriculumFormPage(props: CurriculumFormPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={props.mode === 'create' ? 'Create Curriculum' : 'Edit Curriculum'} />

            <div className="space-y-6 p-4 md:p-6">
                <section className="space-y-2">
                    <p className="text-sm font-semibold tracking-[0.2em] text-emerald-700 uppercase">{props.modeLabel}</p>
                    <h1 className="font-[family-name:var(--font-display)] text-3xl font-black tracking-tight text-[var(--foreground)]">
                        {props.mode === 'create' ? 'Create a curriculum' : 'Edit curriculum'}
                    </h1>
                    <p className="text-muted-foreground max-w-3xl">
                        Keep the curriculum root clean and descriptive. Worlds, units, lessons and stages are built from this foundation.
                    </p>
                </section>

                <CurriculumForm {...props} />
            </div>
        </AppLayout>
    );
}
