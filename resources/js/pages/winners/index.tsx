import { Head, Link, router } from '@inertiajs/react';
import { index as winnersIndex } from '@/actions/App/Http/Controllers/WinnerController';
import AppShell from '@/components/app-shell';

type Winner = {
    id: number;
    full_name: string;
    employee_number: string;
    prize_label: string | null;
    drawn_at: string | null;
    drawn_by: string;
    selected_index: number | null;
    pool_count: number;
    pool_hash: string | null;
    algorithm: string;
};

type Paginated<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type SortKey =
    | 'full_name'
    | 'employee_number'
    | 'prize_label'
    | 'selected_index'
    | 'pool_hash'
    | 'drawn_at'
    | 'drawn_by';

type Props = {
    winners: Paginated<Winner>;
    filters: {
        sort: SortKey;
        direction: 'asc' | 'desc';
    };
};

const columns: Array<{ key: SortKey; label: string }> = [
    { key: 'full_name', label: 'Name' },
    { key: 'employee_number', label: 'Emp No.' },
    { key: 'prize_label', label: 'Prize' },
    { key: 'selected_index', label: 'Index' },
    { key: 'pool_hash', label: 'Pool hash' },
    { key: 'drawn_at', label: 'Drawn at' },
    { key: 'drawn_by', label: 'Operator' },
];

export default function WinnersIndex({ winners, filters }: Props) {
    const sortBy = (key: SortKey) => {
        const direction =
            filters.sort === key && filters.direction === 'asc'
                ? 'desc'
                : 'asc';

        router.get(
            winnersIndex.url({
                query: {
                    sort: key,
                    direction,
                },
            }),
            {},
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AppShell>
            <Head title="Winners" />

            <h1 className="font-[family-name:var(--font-display)] text-4xl tracking-wide text-[#f0d78c]">
                Winners
            </h1>
            <p className="mt-1 text-sm text-[#9eb3c0]">
                Drawn employees are removed from the eligible pool. Click a
                column header to sort.
            </p>

            <div className="mt-6 overflow-x-auto border border-[#2a4558]">
                <table className="min-w-full text-left text-sm">
                    <thead className="bg-[#0f1c27] text-[#9eb3c0]">
                        <tr>
                            {columns.map((column) => {
                                const active = filters.sort === column.key;
                                const indicator = active
                                    ? filters.direction === 'asc'
                                        ? ' ↑'
                                        : ' ↓'
                                    : '';

                                return (
                                    <th
                                        key={column.key}
                                        className="px-4 py-3 font-medium"
                                    >
                                        <button
                                            type="button"
                                            onClick={() => sortBy(column.key)}
                                            className={`inline-flex items-center gap-1 hover:text-[#f0d78c] ${
                                                active
                                                    ? 'text-[#f0d78c]'
                                                    : ''
                                            }`}
                                        >
                                            {column.label}
                                            <span className="font-mono text-xs">
                                                {indicator || ' ↕'}
                                            </span>
                                        </button>
                                    </th>
                                );
                            })}
                        </tr>
                    </thead>
                    <tbody>
                        {winners.data.map((winner) => (
                            <tr
                                key={winner.id}
                                className="border-t border-[#2a4558]/70"
                            >
                                <td className="px-4 py-3">
                                    {winner.full_name}
                                </td>
                                <td className="px-4 py-3 font-mono tracking-wider text-[#f0d78c]">
                                    {winner.employee_number}
                                </td>
                                <td className="px-4 py-3 text-[#9eb3c0]">
                                    {winner.prize_label ?? '—'}
                                </td>
                                <td className="px-4 py-3 font-mono text-[#9eb3c0]">
                                    {winner.selected_index !== null
                                        ? `${winner.selected_index}/${winner.pool_count}`
                                        : '—'}
                                </td>
                                <td
                                    className="max-w-[10rem] truncate px-4 py-3 font-mono text-xs text-[#9eb3c0]"
                                    title={winner.pool_hash ?? undefined}
                                >
                                    {winner.pool_hash
                                        ? `${winner.pool_hash.slice(0, 12)}…`
                                        : '—'}
                                </td>
                                <td className="px-4 py-3 text-[#9eb3c0]">
                                    {winner.drawn_at
                                        ? new Date(
                                              winner.drawn_at,
                                          ).toLocaleString()
                                        : '—'}
                                </td>
                                <td className="px-4 py-3 text-[#9eb3c0]">
                                    {winner.drawn_by}
                                </td>
                            </tr>
                        ))}
                        {winners.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-8 text-center text-[#9eb3c0]"
                                >
                                    No winners drawn yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <div className="mt-4 flex flex-wrap gap-2">
                {winners.links.map((link, index) =>
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
