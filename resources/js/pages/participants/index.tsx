import { Form, Head, Link, router } from '@inertiajs/react';
import AppShell from '@/components/app-shell';
import {
    importMethod,
    importPrizes,
    index as participantsIndex,
    store,
} from '@/actions/App/Http/Controllers/ParticipantController';

type Participant = {
    id: number;
    full_name: string;
    employee_number: string;
    privacy_agreed: boolean;
    source: string;
    has_won: boolean;
    status: 'eligible' | 'won';
};

type Prize = {
    id: number;
    name: string;
    quantity: number;
    remaining: number;
    type: 'major' | 'minor';
};

type Paginated<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Props = {
    participants: Paginated<Participant>;
    filter: string;
    stats: {
        total: number;
        eligible: number;
        won: number;
    };
    prizes: Prize[];
};

const filters = [
    { key: 'all', label: 'All' },
    { key: 'eligible', label: 'Eligible' },
    { key: 'won', label: 'Won' },
];

export default function ParticipantsIndex({
    participants,
    filter,
    stats,
    prizes,
}: Props) {
    return (
        <AppShell wide>
            <Head title="Participants" />

            <div className="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="font-[family-name:var(--font-display)] text-4xl tracking-wide text-[#f0d78c]">
                        Participants
                    </h1>
                    <p className="text-sm text-[#9eb3c0]">
                        Import Google Form registrations — everyone who
                        submitted is eligible until they win.
                    </p>
                </div>
                <p className="text-sm text-[#9eb3c0]">
                    {stats.eligible} eligible · {stats.won} won · {stats.total}{' '}
                    total
                </p>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <section className="border border-[#2a4558] bg-[#0f1c27]/90 p-5">
                    <h2 className="text-lg text-[#c8d6df]">
                        Import registration CSV
                    </h2>
                    <p className="mt-1 text-sm text-[#9eb3c0]">
                        Expects columns: Full Name, ID Number. Privacy Notice is
                        optional.
                    </p>
                    <Form
                        {...importMethod.form()}
                        encType="multipart/form-data"
                        className="mt-4 space-y-3"
                    >
                        {({ errors, processing }) => (
                            <>
                                <input
                                    type="file"
                                    name="file"
                                    accept=".csv,text/csv"
                                    required
                                    className="block w-full text-sm text-[#9eb3c0] file:mr-3 file:border-0 file:bg-[#f0d78c] file:px-3 file:py-2 file:font-semibold file:text-[#0b1520]"
                                />
                                {errors.file && (
                                    <p className="text-sm text-[#ff8f8f]">
                                        {errors.file}
                                    </p>
                                )}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-[#f0d78c] px-4 py-2 font-semibold text-[#0b1520] hover:bg-[#ffe6a3] disabled:opacity-60"
                                >
                                    {processing
                                        ? 'Importing…'
                                        : 'Upload registration CSV'}
                                </button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="border border-[#2a4558] bg-[#0f1c27]/90 p-5">
                    <h2 className="text-lg text-[#c8d6df]">Add participant</h2>
                    <p className="mt-1 text-sm text-[#9eb3c0]">
                        Manually register someone for the next draw.
                    </p>
                    <Form {...store.form()} className="mt-4 space-y-3">
                        {({ errors, processing }) => (
                            <>
                                <div>
                                    <label
                                        htmlFor="full_name"
                                        className="mb-1 block text-sm text-[#9eb3c0]"
                                    >
                                        Full name
                                    </label>
                                    <input
                                        id="full_name"
                                        name="full_name"
                                        required
                                        className="w-full border border-[#2a4558] bg-[#071018] px-3 py-2 outline-none focus:border-[#f0d78c]"
                                    />
                                    {errors.full_name && (
                                        <p className="mt-1 text-sm text-[#ff8f8f]">
                                            {errors.full_name}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <label
                                        htmlFor="employee_number"
                                        className="mb-1 block text-sm text-[#9eb3c0]"
                                    >
                                        Employee number
                                    </label>
                                    <input
                                        id="employee_number"
                                        name="employee_number"
                                        required
                                        placeholder="e.g. 756 or 0756"
                                        className="w-full border border-[#2a4558] bg-[#071018] px-3 py-2 outline-none focus:border-[#f0d78c]"
                                    />
                                    {errors.employee_number && (
                                        <p className="mt-1 text-sm text-[#ff8f8f]">
                                            {errors.employee_number}
                                        </p>
                                    )}
                                </div>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-[#f0d78c] px-4 py-2 font-semibold text-[#0b1520] hover:bg-[#ffe6a3] disabled:opacity-60"
                                >
                                    {processing ? 'Saving…' : 'Add participant'}
                                </button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="border border-[#2a4558] bg-[#0f1c27]/90 p-5 lg:col-span-2">
                    <h2 className="text-lg text-[#c8d6df]">
                        Import prizes XLSX
                    </h2>
                    <p className="mt-1 text-sm text-[#9eb3c0]">
                        Expects columns: Prize Name, Prize Quantity, Prize Type
                        (major / minor).
                    </p>
                    <Form
                        {...importPrizes.form()}
                        encType="multipart/form-data"
                        className="mt-4 space-y-3 sm:max-w-md"
                    >
                        {({ errors, processing }) => (
                            <>
                                <input
                                    type="file"
                                    name="file"
                                    accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    required
                                    className="block w-full text-sm text-[#9eb3c0] file:mr-3 file:border-0 file:bg-[#f0d78c] file:px-3 file:py-2 file:font-semibold file:text-[#0b1520]"
                                />
                                {errors.file && (
                                    <p className="text-sm text-[#ff8f8f]">
                                        {errors.file}
                                    </p>
                                )}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-[#f0d78c] px-4 py-2 font-semibold text-[#0b1520] hover:bg-[#ffe6a3] disabled:opacity-60"
                                >
                                    {processing
                                        ? 'Importing…'
                                        : 'Upload prizes XLSX'}
                                </button>
                            </>
                        )}
                    </Form>

                    {prizes.length > 0 && (
                        <div className="mt-6 overflow-x-auto border border-[#2a4558]">
                            <table className="min-w-full text-left text-sm">
                                <thead className="bg-[#0f1c27] text-[#9eb3c0]">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Prize
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Type
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Remaining
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Quantity
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {prizes.map((prize) => (
                                        <tr
                                            key={prize.id}
                                            className="border-t border-[#2a4558]/70"
                                        >
                                            <td className="px-4 py-3">
                                                {prize.name}
                                            </td>
                                            <td className="px-4 py-3 capitalize text-[#9eb3c0]">
                                                {prize.type}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-[#f0d78c]">
                                                {prize.remaining}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-[#9eb3c0]">
                                                {prize.quantity}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>

            <div className="mt-8 flex flex-wrap gap-2">
                {filters.map((item) => (
                    <button
                        key={item.key}
                        type="button"
                        onClick={() =>
                            router.get(
                                participantsIndex.url(
                                    item.key === 'all'
                                        ? undefined
                                        : { query: { filter: item.key } },
                                ),
                                {},
                                { preserveState: true },
                            )
                        }
                        className={`px-3 py-1.5 text-sm ${
                            filter === item.key
                                ? 'bg-[#f0d78c] font-semibold text-[#0b1520]'
                                : 'border border-[#2a4558] text-[#9eb3c0] hover:border-[#f0d78c]'
                        }`}
                    >
                        {item.label}
                    </button>
                ))}
            </div>

            <div className="mt-4 overflow-x-auto border border-[#2a4558]">
                <table className="min-w-full text-left text-sm">
                    <thead className="bg-[#0f1c27] text-[#9eb3c0]">
                        <tr>
                            <th className="px-4 py-3 font-medium">Name</th>
                            <th className="px-4 py-3 font-medium">Emp No.</th>
                            <th className="px-4 py-3 font-medium">Source</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {participants.data.map((participant) => (
                            <tr
                                key={participant.id}
                                className="border-t border-[#2a4558]/70"
                            >
                                <td className="px-4 py-3">
                                    {participant.full_name}
                                </td>
                                <td className="px-4 py-3 font-mono tracking-wider text-[#f0d78c]">
                                    {participant.employee_number}
                                </td>
                                <td className="px-4 py-3 capitalize text-[#9eb3c0]">
                                    {participant.source}
                                </td>
                                <td className="px-4 py-3 capitalize">
                                    {participant.status}
                                </td>
                            </tr>
                        ))}
                        {participants.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={4}
                                    className="px-4 py-8 text-center text-[#9eb3c0]"
                                >
                                    No participants yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <div className="mt-4 flex flex-wrap gap-2">
                {participants.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url}
                            className={`px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-[#f0d78c] text-[#0b1520]'
                                    : 'border border-[#2a4558] text-[#9eb3c0]'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={`${link.label}-${index}`}
                            className="px-3 py-1 text-sm text-[#5d7382]"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </div>
        </AppShell>
    );
}
