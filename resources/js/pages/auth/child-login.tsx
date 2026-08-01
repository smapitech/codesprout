import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, Sparkles, Wand2 } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface ChildLoginForm extends Record<string, FormDataConvertible> {
    learner_id: string;
    pin: string;
}

interface ChildLoginProps {
    status?: string;
    demoLearners?: Array<{
        label: string;
        learner_id: string;
        pin: string;
    }>;
}

export default function ChildLogin({ status, demoLearners = [] }: ChildLoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<ChildLoginForm>({
        learner_id: '',
        pin: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('child.login'), {
            onFinish: () => reset('pin'),
        });
    };

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(46,196,145,0.16),_transparent_30%),radial-gradient(circle_at_top_right,_rgba(255,207,102,0.18),_transparent_28%),linear-gradient(180deg,_#fffaf2_0%,_#f5fbf8_100%)] px-4 py-8 sm:px-6 lg:px-8">
            <Head title="Learner login" />

            <div className="mx-auto grid min-h-[calc(100vh-4rem)] max-w-6xl items-center gap-8 lg:grid-cols-[1.05fr_0.95fr]">
                <div className="space-y-6 rounded-[2rem] border border-white/70 bg-white/70 p-8 shadow-[0_20px_80px_rgba(31,76,58,0.12)] backdrop-blur">
                    <div className="inline-flex items-center gap-3 rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-900">
                        <Sparkles className="h-4 w-4" />
                        Learner login made simple
                    </div>

                    <div className="space-y-4">
                        <h1 className="max-w-lg font-[family-name:var(--font-display)] text-4xl font-black tracking-tight text-[var(--foreground)] sm:text-5xl">
                            Hi, learner. Enter your ID and PIN to continue your adventure.
                        </h1>
                        <p className="text-muted-foreground max-w-xl text-lg leading-8">
                            Use your learner ID card or classroom name with your 4-digit PIN. Grown-ups can always help recover access.
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Card className="rounded-3xl border-white/70 bg-white/90">
                            <CardHeader className="space-y-2 pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Wand2 className="h-4 w-4 text-emerald-600" />
                                    Step 1
                                </CardTitle>
                                <CardDescription>Type your learner ID or classroom identity.</CardDescription>
                            </CardHeader>
                        </Card>

                        <Card className="rounded-3xl border-white/70 bg-white/90">
                            <CardHeader className="space-y-2 pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Wand2 className="h-4 w-4 text-amber-600" />
                                    Step 2
                                </CardTitle>
                                <CardDescription>Enter your 4-digit PIN to unlock your dashboard.</CardDescription>
                            </CardHeader>
                        </Card>
                    </div>
                </div>

                <Card className="rounded-[2rem] border-white/80 bg-white/95 shadow-[0_20px_80px_rgba(31,76,58,0.12)]">
                    <CardHeader className="space-y-2 px-8 pt-8">
                        <CardTitle className="text-2xl font-bold">Learner sign in</CardTitle>
                        <CardDescription>Big buttons. Clear instructions. Safe access only.</CardDescription>
                    </CardHeader>
                    <CardContent className="px-8 pb-8">
                        <form className="space-y-6" onSubmit={submit}>
                            <div className="grid gap-2">
                                <Label htmlFor="learner_id">Learner ID or classroom name</Label>
                                <Input
                                    id="learner_id"
                                    type="text"
                                    required
                                    autoFocus
                                    value={data.learner_id}
                                    onChange={(event) => setData('learner_id', event.target.value)}
                                    placeholder="CB-LEARN-1001"
                                    className="h-12 rounded-2xl text-base"
                                />
                                <InputError message={errors.learner_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="pin">4-digit PIN</Label>
                                <Input
                                    id="pin"
                                    type="password"
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    maxLength={4}
                                    required
                                    value={data.pin}
                                    onChange={(event) => setData('pin', event.target.value.replace(/\D/g, '').slice(0, 4))}
                                    placeholder="1234"
                                    className="h-12 rounded-2xl text-center text-xl tracking-[0.5em]"
                                />
                                <InputError message={errors.pin} />
                            </div>

                            <Button type="submit" className="h-12 w-full rounded-2xl text-base font-semibold" disabled={processing}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                Open My Dashboard
                            </Button>
                        </form>

                        {demoLearners.length > 0 && (
                            <section
                                className="mt-6 rounded-3xl border border-emerald-100 bg-emerald-50/70 p-4"
                                aria-label="Local development learner helpers"
                            >
                                <p className="text-sm font-semibold text-emerald-950">Local learner logins</p>
                                <p className="text-muted-foreground mt-1 text-xs">These helpers appear only in local/testing environments.</p>
                                <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                    {demoLearners.map((learner) => (
                                        <Button
                                            key={learner.learner_id}
                                            type="button"
                                            variant="outline"
                                            className="h-auto rounded-2xl bg-white px-3 py-3"
                                            onClick={() => {
                                                setData('learner_id', learner.learner_id);
                                                setData('pin', learner.pin);
                                            }}
                                        >
                                            <span className="block w-full text-left">
                                                <span className="block font-semibold">{learner.label}</span>
                                                <span className="text-muted-foreground block text-xs">{learner.learner_id}</span>
                                            </span>
                                        </Button>
                                    ))}
                                </div>
                            </section>
                        )}

                        <div className="text-muted-foreground mt-6 flex items-center justify-between gap-3 text-sm">
                            <span>{status ?? 'Need help? Ask your parent or teacher to reset your PIN.'}</span>
                            <Link href={route('login')} className="font-medium text-emerald-700 underline underline-offset-4">
                                Adult login
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
