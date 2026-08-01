import type { FormDataConvertible } from '@inertiajs/core';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface LoginForm extends Record<string, FormDataConvertible> {
    email: string;
    password: string;
    remember: boolean;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    childLoginUrl: string;
    demoCredentials?: Array<{
        label: string;
        email: string;
        password: string;
    }>;
}

export default function Login({ status, canResetPassword, childLoginUrl, demoCredentials = [] }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title="Adult sign in" description="Use your email and password to manage classes, children and account settings.">
            <Head title="Log in" />

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="email@example.com"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password">Password</Label>
                            {canResetPassword && (
                                <TextLink href={route('password.request')} className="ml-auto text-sm" tabIndex={5}>
                                    Forgot password?
                                </TextLink>
                            )}
                        </div>
                        <Input
                            id="password"
                            type="password"
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center space-x-3">
                        <Checkbox
                            id="remember"
                            name="remember"
                            tabIndex={3}
                            checked={data.remember}
                            onCheckedChange={(checked) => setData('remember', checked === true)}
                        />
                        <Label htmlFor="remember">Remember me</Label>
                    </div>

                    <Button type="submit" className="mt-4 w-full" tabIndex={4} disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Log in
                    </Button>
                </div>

                <div className="text-muted-foreground text-center text-sm">
                    Need the child PIN sign in?{' '}
                    <Link href={childLoginUrl} tabIndex={5} className="font-medium text-emerald-700 underline underline-offset-4">
                        Open learner login
                    </Link>
                </div>

                {demoCredentials.length > 0 && (
                    <section className="rounded-3xl border border-emerald-100 bg-emerald-50/70 p-4" aria-label="Local development login helpers">
                        <p className="text-sm font-semibold text-emerald-950">Local dashboard logins</p>
                        <p className="text-muted-foreground mt-1 text-xs">These helpers appear only in local/testing environments.</p>
                        <div className="mt-3 grid gap-2 sm:grid-cols-3">
                            {demoCredentials.map((credential) => (
                                <Button
                                    key={credential.email}
                                    type="button"
                                    variant="outline"
                                    className="h-auto rounded-2xl bg-white px-3 py-3 text-left"
                                    onClick={() => {
                                        setData('email', credential.email);
                                        setData('password', credential.password);
                                        setData('remember', false);
                                    }}
                                >
                                    <span className="block w-full">
                                        <span className="block font-semibold">{credential.label}</span>
                                        <span className="text-muted-foreground block text-xs">{credential.email}</span>
                                    </span>
                                </Button>
                            ))}
                        </div>
                    </section>
                )}
            </form>

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}
        </AuthLayout>
    );
}
