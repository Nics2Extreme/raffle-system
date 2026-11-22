import { Form, Head, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/DrawController';
import AppShell from '@/components/app-shell';
import DigitReel, { type DigitReelMode } from '@/components/digit-reel';
import SlotMachine from '@/components/slot-machine';

type WinnerPayload = {
    full_name: string;
    employee_number: string;
    prize_label: string | null;
};

type WinnerRow = WinnerPayload & {
    id: number;
    drawn_at: string | null;
    pool_count: number;
    pool_hash: string | null;
    selected_index: number | null;
    algorithm: string;
};

type Fairness = {
    algorithm: string;
    pool_count: number;
    pool_hash: string | null;
    explanation: string;
};

type Props = {
    eligibleCount: number;
    winnerCount: number;
    winners: WinnerRow[];
    fairness: Fairness;
    latestWinner:
        | (WinnerPayload & {
              drawn_at: string | null;
              pool_count: number;
              pool_hash: string | null;
              selected_index: number | null;
              algorithm: string;
          })
        | null;
};

/** How long the active digit decelerates before locking. */
const REEL_LAND_MS = 5200;
/** Pause after a digit lands before the next one starts landing. */
const REEL_GAP_MS = 650;
/** Delay after the last digit before showing the name. */
const NAME_REVEAL_MS = 700;
/** How long CSS confetti plays after the name appears. */
const CELEBRATE_MS = 2200;

export default function DrawIndex({
    eligibleCount,
    winnerCount,
    winners,
    fairness,
    latestWinner,
}: Props) {
    const { flash } = usePage().props;
    const animationKeyRef = useRef<string | null>(null);

    const [digits, setDigits] = useState<Array<string | null>>([
        null,
        null,
        null,
        null,
    ]);
    const [landingFlags, setLandingFlags] = useState([
        false,
        false,
        false,
        false,
    ]);
    const [revealedFlags, setRevealedFlags] = useState([
        false,
        false,
        false,
        false,
    ]);
    const [activeReel, setActiveReel] = useState<number | null>(null);
    const [revealedName, setRevealedName] = useState<string | null>(null);
    const [prizeLabel, setPrizeLabel] = useState<string | null>(null);
    const [animating, setAnimating] = useState(false);
    const [awaitingDraw, setAwaitingDraw] = useState(false);
    const [celebrating, setCelebrating] = useState(false);
    const [pendingEmployeeNumber, setPendingEmployeeNumber] = useState<
        string | null
    >(null);

    useEffect(() => {
        const winner = flash.drawn_winner;

        if (!winner) {
            return;
        }

        const animationKey = `${winner.employee_number}:${winner.full_name}:${winner.prize_label ?? ''}`;

        if (animationKeyRef.current === animationKey) {
            return;
        }

        animationKeyRef.current = animationKey;

        const nextDigits = winner.employee_number.padStart(4, '0').split('');
        const timeouts: number[] = [];

        setDigits(nextDigits);
        setRevealedName(null);
        setPrizeLabel(winner.prize_label);
        setPendingEmployeeNumber(winner.employee_number);
        setAwaitingDraw(false);
        setCelebrating(false);
        setAnimating(true);
        setActiveReel(null);
        setRevealedFlags([false, false, false, false]);
        setLandingFlags([false, false, false, false]);

        let elapsed = 500;

        nextDigits.forEach((_, index) => {
            const landStart = elapsed;

            timeouts.push(
                window.setTimeout(() => {
                    setActiveReel(index);
                    setLandingFlags((current) => {
                        const next = [...current];
                        next[index] = true;

                        return next;
                    });
                }, landStart),
            );

            const landEnd = landStart + REEL_LAND_MS;

            timeouts.push(
                window.setTimeout(() => {
                    setLandingFlags((current) => {
                        const next = [...current];
                        next[index] = false;

                        return next;
                    });
                    setRevealedFlags((current) => {
                        const next = [...current];
                        next[index] = true;

                        return next;
                    });
                    setActiveReel(null);
                }, landEnd),
            );

            elapsed = landEnd + REEL_GAP_MS;
        });

        timeouts.push(
            window.setTimeout(() => {
                setRevealedName(winner.full_name);
                setPendingEmployeeNumber(null);
                setAnimating(false);
                setCelebrating(true);
            }, elapsed + NAME_REVEAL_MS - REEL_GAP_MS),
        );

        timeouts.push(
            window.setTimeout(
                () => {
                    setCelebrating(false);
                },
                elapsed + NAME_REVEAL_MS - REEL_GAP_MS + CELEBRATE_MS,
            ),
        );

        return () => {
            timeouts.forEach((timeout) => window.clearTimeout(timeout));
        };
    }, [flash.drawn_winner]);

    const visibleWinners =
        pendingEmployeeNumber === null
            ? winners
            : winners.filter(
                  (winner) =>
                      winner.employee_number !== pendingEmployeeNumber,
              );

    const isRacing = awaitingDraw || animating;
    const drawBusy = awaitingDraw || animating;
    const canDraw = eligibleCount > 0 && !drawBusy;

    const reelMode = (index: number): DigitReelMode => {
        if (landingFlags[index]) {
            return 'landing';
        }

        if (revealedFlags[index]) {
            return 'locked';
        }

        if (isRacing) {
            return 'racing';
        }

        return 'idle';
    };

    return (
        <AppShell wide>
            <Head title="Draw" />

            <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem] xl:grid-cols-[minmax(0,1fr)_20rem]">
                <div>
                    <div className="mt-8">
                        <SlotMachine celebrating={celebrating}>
                            {digits.map((digit, index) => (
                                <DigitReel
                                    key={index}
                                    targetDigit={digit}
                                    mode={reelMode(index)}
                                    landingDurationMs={REEL_LAND_MS}
                                    idleDelayMs={index * 700}
                                />
                            ))}
                        </SlotMachine>
                    </div>

                    <div className="mt-8 min-h-20 text-center">
                        {prizeLabel && (
                            <p className="text-sm uppercase tracking-[0.2em] text-mist-dim">
                                {prizeLabel}
                            </p>
                        )}
                        {revealedName ? (
                            <p className="mt-2 animate-[fade-up_500ms_ease-out] font-[family-name:var(--font-display)] text-4xl tracking-wide text-gold-bright sm:text-5xl">
                                {revealedName}
                            </p>
                        ) : awaitingDraw ? (
                            <p className="mt-2 text-mist-dim">Speeding up…</p>
                        ) : animating ? (
                            <p className="mt-2 text-mist-dim">
                                {activeReel === null
                                    ? 'Spinning…'
                                    : `Locking digit ${activeReel + 1} of 4…`}
                            </p>
                        ) : latestWinner ? (
                            <p className="mt-2 text-mist-dim">
                                Last winner: {latestWinner.full_name} (
                                {latestWinner.employee_number})
                            </p>
                        ) : (
                            <p className="mt-2 text-mist-dim">
                                Press Draw to spin the employee number.
                            </p>
                        )}
                    </div>

                    <Form
                        {...store.form()}
                        className="mx-auto mt-8 flex max-w-md flex-col items-center gap-3"
                        options={{ preserveScroll: true }}
                        onStart={() => {
                            setAwaitingDraw(true);
                            setCelebrating(false);
                            setRevealedName(null);
                            setPrizeLabel(null);
                            setRevealedFlags([false, false, false, false]);
                            setLandingFlags([false, false, false, false]);
                            setDigits([null, null, null, null]);
                            setActiveReel(null);
                        }}
                        onError={() => {
                            setAwaitingDraw(false);
                        }}
                        onCancel={() => {
                            setAwaitingDraw(false);
                        }}
                    >
                        {({ errors, processing }) => (
                            <>
                                <input
                                    type="text"
                                    name="prize_label"
                                    placeholder="Optional prize label"
                                    className="w-full border border-stage-border bg-stage px-3 py-2 text-center text-[#e8f0f5] outline-none focus:border-gold"
                                    disabled={processing || drawBusy}
                                />
                                {errors.prize_label && (
                                    <p className="text-sm text-[#ff8f8f]">
                                        {errors.prize_label}
                                    </p>
                                )}
                                {errors.draw && (
                                    <p className="text-sm text-[#ff8f8f]">
                                        {errors.draw}
                                    </p>
                                )}
                                <button
                                    type="submit"
                                    disabled={!canDraw || processing}
                                    className="min-w-48 bg-gold px-8 py-3 font-[family-name:var(--font-display)] text-2xl tracking-wide text-stage transition hover:bg-gold-bright disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processing || drawBusy
                                        ? 'Drawing…'
                                        : 'Draw'}
                                </button>
                            </>
                        )}
                    </Form>
                </div>

                <aside className="border border-stage-border bg-stage-panel/90 lg:sticky lg:top-6 lg:max-h-[calc(100vh-6rem)] lg:self-start lg:overflow-hidden">
                    <div className="border-b border-stage-border px-4 py-3">
                        <h2 className="font-[family-name:var(--font-display)] text-2xl tracking-wide text-gold">
                            Winners
                        </h2>
                        <p className="text-xs text-mist-dim">
                            {winnerCount} drawn so far
                        </p>
                    </div>

                    <ol className="max-h-[24rem] divide-y divide-stage-border/70 overflow-y-auto lg:max-h-[calc(100vh-11rem)]">
                        {visibleWinners.length === 0 && (
                            <li className="px-4 py-8 text-center text-sm text-mist-dim">
                                No winners yet.
                            </li>
                        )}
                        {visibleWinners.map((winner, index) => (
                            <li
                                key={winner.id}
                                className={`px-4 py-3 ${
                                    revealedName !== null &&
                                    index === 0 &&
                                    winner.full_name === revealedName
                                        ? 'bg-[#1a2f3d] animate-[fade-up_500ms_ease-out]'
                                        : ''
                                }`}
                            >
                                <div className="flex items-baseline justify-between gap-2">
                                    <p className="font-medium text-[#e8f0f5]">
                                        {winner.full_name}
                                    </p>
                                    <p className="font-mono text-sm tracking-wider text-gold">
                                        {winner.employee_number}
                                    </p>
                                </div>
                                {winner.prize_label && (
                                    <p className="mt-1 text-xs uppercase tracking-[0.14em] text-mist-dim">
                                        {winner.prize_label}
                                    </p>
                                )}
                                {winner.selected_index !== null && (
                                    <p className="mt-1 font-mono text-[11px] text-mist-dim/80">
                                        index {winner.selected_index}/
                                        {winner.pool_count}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ol>
                </aside>
            </div>
        </AppShell>
    );
}
