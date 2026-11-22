import { Form, Head } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';

export default function Login() {
    return (
        <>
            <Head title="Operator login" />
            <div className="flex min-h-screen items-center justify-center bg-[#0b1520] px-4">
                <main className="w-full max-w-md">
                    <p className="font-[family-name:var(--font-display)] text-center text-3xl tracking-wide text-[#f0d78c]">
                        Company Raffle
                    </p>
                    <h1 className="mt-2 text-center text-lg text-[#c8d6df]">
                        Operator login
                    </h1>

                    <Form
                        {...store.form()}
                        className="mt-8 space-y-4 border border-[#2a4558] bg-[#0f1c27]/90 p-6 shadow-[0_20px_60px_rgba(0,0,0,0.35)]"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div>
                                    <label
                                        htmlFor="email"
                                        className="mb-1 block text-sm text-[#9eb3c0]"
                                    >
                                        Email
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        autoComplete="username"
                                        className="w-full border border-[#2a4558] bg-[#071018] px-3 py-2 text-[#e8f0f5] outline-none focus:border-[#f0d78c]"
                                    />
                                    {errors.email && (
                                        <p className="mt-1 text-sm text-[#ff8f8f]">
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="password"
                                        className="mb-1 block text-sm text-[#9eb3c0]"
                                    >
                                        Password
                                    </label>
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        required
                                        autoComplete="current-password"
                                        className="w-full border border-[#2a4558] bg-[#071018] px-3 py-2 text-[#e8f0f5] outline-none focus:border-[#f0d78c]"
                                    />
                                    {errors.password && (
                                        <p className="mt-1 text-sm text-[#ff8f8f]">
                                            {errors.password}
                                        </p>
                                    )}
                                </div>

                                <label className="flex items-center gap-2 text-sm text-[#9eb3c0]">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        className="rounded border-[#2a4558]"
                                    />
                                    Remember me
                                </label>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full bg-[#f0d78c] px-4 py-2.5 font-semibold text-[#0b1520] transition hover:bg-[#ffe6a3] disabled:opacity-60"
                                >
                                    {processing ? 'Signing in…' : 'Sign in'}
                                </button>
                            </>
                        )}
                    </Form>
                </main>
            </div>
        </>
    );
}
