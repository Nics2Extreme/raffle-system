import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { destroy as logout } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import { index as drawIndex } from '@/actions/App/Http/Controllers/DrawController';
import { index as participantsIndex } from '@/actions/App/Http/Controllers/ParticipantController';
import { index as winnersIndex } from '@/actions/App/Http/Controllers/WinnerController';

export default function AppShell({
    children,
    wide = false,
}: PropsWithChildren<{ wide?: boolean }>) {
    const { auth, flash } = usePage().props;

    return (
        <div className="min-h-screen bg-[#0b1520] text-[#e8f0f5]">
            <header className="border-b border-[#2a4558]/80 bg-[#0b1520]/70 backdrop-blur">
                <div
                    className={`mx-auto flex items-center justify-between gap-4 px-4 py-3 ${wide ? 'max-w-7xl' : 'max-w-5xl'}`}
                >
                    <div className="flex items-center gap-6">
                        <Link
                            href={drawIndex.url()}
                            className="font-[family-name:var(--font-display)] text-2xl tracking-wide text-[#f0d78c]"
                        >
                            Company Raffle
                        </Link>
                        <nav className="hidden gap-4 text-sm text-[#9eb3c0] sm:flex">
                            <Link
                                href={drawIndex.url()}
                                className="hover:text-[#f0d78c]"
                            >
                                Draw
                            </Link>
                            <Link
                                href={participantsIndex.url()}
                                className="hover:text-[#f0d78c]"
                            >
                                Participants
                            </Link>
                            <Link
                                href={winnersIndex.url()}
                                className="hover:text-[#f0d78c]"
                            >
                                Winners
                            </Link>
                        </nav>
                    </div>
                    <div className="flex items-center gap-3 text-sm text-[#9eb3c0]">
                        <span className="hidden sm:inline">
                            {auth.user?.name}
                        </span>
                        <Link
                            href={logout.url()}
                            method="post"
                            as="button"
                            className="border border-[#2a4558] px-3 py-1.5 hover:border-[#f0d78c] hover:text-[#f0d78c]"
                        >
                            Log out
                        </Link>
                    </div>
                </div>
            </header>

            {(flash.success || flash.error) && (
                <div className="mx-auto max-w-5xl px-4 pt-4">
                    {flash.success && (
                        <p className="border border-[#3d6b4f] bg-[#12301f] px-4 py-2 text-sm text-[#b7efc5]">
                            {flash.success}
                        </p>
                    )}
                    {flash.error && (
                        <p className="border border-[#6b3d3d] bg-[#301212] px-4 py-2 text-sm text-[#ffb4b4]">
                            {flash.error}
                        </p>
                    )}
                </div>
            )}

            <div className={`mx-auto px-4 py-6 ${wide ? 'max-w-7xl' : 'max-w-5xl'}`}>
                {children}
            </div>
        </div>
    );
}
