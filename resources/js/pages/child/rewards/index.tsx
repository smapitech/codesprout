import { ProgressRing } from '@/components/dashboard/progress-ring';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { Award, Flame, Medal, Sparkles, Star } from 'lucide-react';
import type React from 'react';

interface ChildRewardsProps {
    child: { name: string; avatar_url: string | null };
    profile: {
        total_stars: number;
        total_experience: number;
        current_level: string;
        current_level_number: number;
        next_level: string | null;
        xp_to_next_level: number;
        current_streak: number;
        longest_streak: number;
        completed_missions: number;
        completed_worlds: number;
    };
    skills: Array<{ name: string; mastery: number; label: string; aria_label: string }>;
    recent_badges: Array<{ id: string; name: string; description: string; image: string | null; alt: string | null; awarded_at: string | null }>;
    celebrations: Array<{ id: string; heading: string; message: string; reward_summary: Record<string, unknown> | null }>;
}

export default function ChildRewards({ child, profile, skills, recent_badges, celebrations }: ChildRewardsProps) {
    const firstName = child.name.split(' ')[0] ?? 'Explorer';

    return (
        <div className="min-h-screen overflow-x-hidden bg-[#FFF8EA] p-4 text-[#243443] md:p-6">
            <Head title={`My Rewards | ${firstName}`} />

            <main className="mx-auto max-w-7xl space-y-6">
                <section className="relative overflow-hidden rounded-[2rem] bg-white p-5 shadow-[0_18px_60px_rgba(31,76,58,0.1)] md:p-8">
                    <div className="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div className="flex items-center gap-4">
                            <Avatar className="h-20 w-20 rounded-[2rem] border border-emerald-100 bg-emerald-50">
                                <AvatarImage src={child.avatar_url ?? undefined} alt={`${firstName} avatar`} />
                                <AvatarFallback className="rounded-[2rem] text-3xl font-black">{firstName.slice(0, 1)}</AvatarFallback>
                            </Avatar>
                            <div>
                                <p className="text-sm font-bold tracking-[0.18em] text-emerald-700 uppercase">My Rewards</p>
                                <h1 className="font-[family-name:var(--font-display)] text-3xl font-black md:text-5xl">Keep growing, {firstName}!</h1>
                                <p className="text-muted-foreground mt-2 max-w-2xl">Every practice day helps your CodeSprout skills grow.</p>
                            </div>
                        </div>
                        <Button asChild className="h-12 rounded-full bg-[#FF6B5E] px-6 text-base font-bold hover:bg-[#e95b50]">
                            <Link href={route('child.journey')}>Continue Journey</Link>
                        </Button>
                    </div>
                </section>

                {celebrations.length > 0 && (
                    <section aria-live="polite" className="rounded-[2rem] border-2 border-amber-200 bg-amber-50 p-5">
                        <h2 className="font-[family-name:var(--font-display)] text-2xl font-black">Celebration waiting</h2>
                        {celebrations.map((celebration) => (
                            <div key={celebration.id} className="mt-3 rounded-3xl bg-white p-4">
                                <p className="font-bold">{celebration.heading}</p>
                                <p className="text-muted-foreground mt-1 text-sm">{celebration.message}</p>
                            </div>
                        ))}
                    </section>
                )}

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <RewardCard icon={<Star />} label="Stars" value={profile.total_stars} note="Earned from completed learning." />
                    <RewardCard
                        icon={<Sparkles />}
                        label="XP"
                        value={profile.total_experience}
                        note={`${profile.xp_to_next_level} XP to ${profile.next_level ?? 'the top level'}.`}
                    />
                    <RewardCard icon={<Award />} label="Level" value={profile.current_level_number} note={profile.current_level} />
                    <RewardCard
                        icon={<Flame />}
                        label="Streak"
                        value={profile.current_streak}
                        note={`Longest streak: ${profile.longest_streak} days.`}
                    />
                </section>

                <section className="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                    <div className="rounded-[2rem] bg-white p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                        <h2 className="font-[family-name:var(--font-display)] text-2xl font-black">Skill garden</h2>
                        <div className="mt-5 grid gap-4 sm:grid-cols-2">
                            {skills.length > 0 ? (
                                skills.map((skill) => (
                                    <div key={skill.name} className="rounded-3xl bg-emerald-50/70 p-4">
                                        <div role="img" aria-label={skill.aria_label}>
                                            <ProgressRing value={skill.mastery} label={skill.name} />
                                        </div>
                                        <p className="mt-3 text-center text-sm font-semibold">{skill.label}</p>
                                    </div>
                                ))
                            ) : (
                                <p className="text-muted-foreground rounded-3xl bg-emerald-50/70 p-5 text-sm">
                                    Complete a mission to start growing your skill garden.
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="rounded-[2rem] bg-white p-6 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
                        <h2 className="font-[family-name:var(--font-display)] text-2xl font-black">Badge collection</h2>
                        <div className="mt-5 grid gap-4 sm:grid-cols-2">
                            {recent_badges.length > 0 ? (
                                recent_badges.map((badge) => (
                                    <article key={badge.id} className="rounded-3xl border border-emerald-100 bg-[#fffaf2] p-5">
                                        <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                                            <Medal className="h-8 w-8" aria-hidden="true" />
                                        </div>
                                        <h3 className="mt-4 font-[family-name:var(--font-display)] text-xl font-black">{badge.name}</h3>
                                        <p className="text-muted-foreground mt-2 text-sm">{badge.description}</p>
                                    </article>
                                ))
                            ) : (
                                <p className="text-muted-foreground rounded-3xl bg-amber-50 p-5 text-sm">
                                    Your first badge will appear here after a qualifying mission.
                                </p>
                            )}
                        </div>
                    </div>
                </section>
            </main>
        </div>
    );
}

function RewardCard({ icon, label, value, note }: { icon: React.ReactNode; label: string; value: number; note: string }) {
    return (
        <div className="rounded-[2rem] bg-white p-5 shadow-[0_12px_40px_rgba(38,84,63,0.08)]">
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">{icon}</div>
            <p className="mt-4 text-sm font-bold tracking-[0.16em] text-emerald-700 uppercase">{label}</p>
            <p className="font-[family-name:var(--font-display)] text-4xl font-black">{value}</p>
            <p className="text-muted-foreground mt-1 text-sm">{note}</p>
        </div>
    );
}
