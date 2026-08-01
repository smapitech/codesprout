import { LandingBrand } from '@/components/landing/brand';
import { LandingSectionHeading } from '@/components/landing/section-heading';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';

export default function Privacy() {
    return (
        <div className="min-h-screen bg-[linear-gradient(180deg,_#FFF8EA_0%,_#FFFDFA_38%,_#F3FBF8_100%)] px-4 py-8 text-[var(--codesprout-ink)] sm:px-6 lg:px-8">
            <Head title="Privacy | CodeSprout" />

            <div className="mx-auto max-w-4xl">
                <Link href={route('home')} className="inline-flex items-center">
                    <LandingBrand compact />
                </Link>

                <Card className="mt-8 rounded-[2.25rem] border border-white/80 bg-white/92 p-6 shadow-[0_16px_40px_rgba(36,52,67,0.08)] sm:p-8">
                    <LandingSectionHeading
                        eyebrow="Privacy"
                        title="Privacy"
                        description="CodeSprout keeps public pages simple and family-friendly. Private child progress stays inside the authenticated dashboards."
                    />

                    <div className="mt-6 space-y-4 text-sm leading-7 text-[var(--codesprout-ink)]/78">
                        <p>
                            Public landing pages can show published curriculum information, example previews and the support contact address from the
                            application settings.
                        </p>
                        <p>
                            Child names, learner IDs, progress reports and assignments remain inside protected areas that only authenticated parents,
                            teachers, administrators or child accounts can open.
                        </p>
                        <p>If you need help with account access or a privacy question, use the contact email shown in the footer on the homepage.</p>
                    </div>

                    <div className="mt-8">
                        <Button
                            asChild
                            className="h-12 rounded-full bg-[var(--codesprout-coral)] px-5 text-sm font-semibold text-white shadow-[0_12px_24px_rgba(255,107,94,0.24)]"
                        >
                            <Link href={route('home')}>Back to Home</Link>
                        </Button>
                    </div>
                </Card>
            </div>
        </div>
    );
}
