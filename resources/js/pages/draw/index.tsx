import { Form, Head, Link, usePage } from "@inertiajs/react";
import { useEffect, useMemo, useRef, useState } from "react";
import { destroy as logout } from "@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController";
import { store } from "@/actions/App/Http/Controllers/DrawController";
import { index as participantsIndex } from "@/actions/App/Http/Controllers/ParticipantController";
import DigitReel, { type DigitReelMode } from "@/components/digit-reel";
import SlotMachine from "@/components/slot-machine";
import {
    FIREWORKS_DURATION_MS,
    fireConfettiFromElement,
    fireMajorPrizeFireworks,
} from "@/lib/fireworks";
import casinoBg from "../../../assets/casino.jpg";

type PrizeType = "major" | "minor";

type WinnerPayload = {
    full_name: string;
    employee_number: string;
    prize_label: string | null;
    prize_type?: PrizeType | null;
};

type WinnerRow = WinnerPayload & {
    id: number;
    drawn_at: string | null;
    pool_count: number;
    pool_hash: string | null;
    selected_index: number | null;
    algorithm: string;
};

type PrizeOption = {
    id: number;
    name: string;
    quantity: number;
    remaining: number;
    type: PrizeType;
    available: boolean;
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
    prizes: PrizeOption[];
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

type ReelTiming = {
    initialDelayMs: number;
    landMs: number;
    gapMs: number;
    nameRevealMs: number;
};

function formatDrawnAt(iso: string | null): string {
    if (!iso) {
        return "";
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    return date.toLocaleString(undefined, {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

function timingForPrizeType(type: PrizeType | null | undefined): ReelTiming {
    const budgetMs = type === "major" ? 10_000 : 2_000;
    const initialDelayMs = Math.round(budgetMs * 0.05);
    const nameRevealMs = Math.round(budgetMs * 0.08);
    const usable = budgetMs - initialDelayMs - nameRevealMs;
    const gapMs = Math.max(40, Math.round((usable * 0.08) / 3));
    const landMs = Math.max(120, Math.round((usable - gapMs * 3) / 4));

    return {
        initialDelayMs,
        landMs,
        gapMs,
        nameRevealMs,
    };
}

export default function DrawIndex({
    eligibleCount,
    winnerCount,
    winners,
    prizes,
    fairness,
    latestWinner,
}: Props) {
    const { auth, flash, name } = usePage().props;
    const animationKeyRef = useRef<string | null>(null);
    const winnersViewportRef = useRef<HTMLDivElement | null>(null);
    const winnersTrackRef = useRef<HTMLDivElement | null>(null);
    const winnersListRef = useRef<HTMLOListElement | null>(null);
    const [winnersMarquee, setWinnersMarquee] = useState(false);
    const revealedNameRef = useRef<HTMLParagraphElement | null>(null);
    const stopFireworksRef = useRef<(() => void) | null>(null);
    const availablePrizes = useMemo(
        () =>
            prizes
                .filter((prize) => prize.available)
                .slice()
                .sort((a, b) => {
                    if (b.remaining !== a.remaining) {
                        return b.remaining - a.remaining;
                    }

                    if (a.type !== b.type) {
                        return a.type === "minor" ? -1 : 1;
                    }

                    return a.name.localeCompare(b.name);
                }),
        [prizes],
    );
    const [selectedPrizeId, setSelectedPrizeId] = useState<string>(
        availablePrizes[0] ? String(availablePrizes[0].id) : "",
    );
    const [drawArmed, setDrawArmed] = useState(false);

    const selectedPrize = useMemo(
        () =>
            prizes.find((prize) => String(prize.id) === selectedPrizeId) ??
            null,
        [prizes, selectedPrizeId],
    );

    useEffect(() => {
        if (
            selectedPrizeId !== "" &&
            availablePrizes.some(
                (prize) => String(prize.id) === selectedPrizeId,
            )
        ) {
            return;
        }

        setSelectedPrizeId(
            availablePrizes[0] ? String(availablePrizes[0].id) : "",
        );
    }, [availablePrizes, selectedPrizeId]);

    useEffect(() => {
        setDrawArmed(false);
    }, [selectedPrizeId]);

    useEffect(() => {
        if (!drawArmed) {
            return;
        }

        const timeout = window.setTimeout(() => setDrawArmed(false), 3000);

        return () => window.clearTimeout(timeout);
    }, [drawArmed]);

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
    const [revealedEmployeeNumber, setRevealedEmployeeNumber] = useState<
        string | null
    >(null);
    const [prizeLabel, setPrizeLabel] = useState<string | null>(null);
    const [landDurationMs, setLandDurationMs] = useState(
        timingForPrizeType("minor").landMs,
    );
    const [animating, setAnimating] = useState(false);
    const [awaitingDraw, setAwaitingDraw] = useState(false);
    const [pendingEmployeeNumber, setPendingEmployeeNumber] = useState<
        string | null
    >(null);

    useEffect(() => {
        const winner = flash.drawn_winner;

        if (!winner) {
            return;
        }

        const animationKey = `${winner.employee_number}:${winner.full_name}:${winner.prize_label ?? ""}`;

        if (animationKeyRef.current === animationKey) {
            return;
        }

        animationKeyRef.current = animationKey;

        const nextDigits = winner.employee_number.padStart(4, "0").split("");
        const timeouts: number[] = [];
        const timing = timingForPrizeType(winner.prize_type);
        const isMajor = winner.prize_type === "major";

        stopFireworksRef.current?.();
        stopFireworksRef.current = null;

        setDigits(nextDigits);
        setRevealedName(null);
        setRevealedEmployeeNumber(null);
        setPrizeLabel(winner.prize_label);
        setLandDurationMs(timing.landMs);
        setPendingEmployeeNumber(winner.employee_number);
        setAwaitingDraw(false);
        setAnimating(true);
        setActiveReel(null);
        setRevealedFlags([false, false, false, false]);
        setLandingFlags([false, false, false, false]);

        let elapsed = timing.initialDelayMs;

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

            const landEnd = landStart + timing.landMs;

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

            elapsed = landEnd + timing.gapMs;
        });

        timeouts.push(
            window.setTimeout(
                () => {
                    setRevealedName(winner.full_name);
                    setRevealedEmployeeNumber(winner.employee_number);
                    setPendingEmployeeNumber(null);
                    setAnimating(false);

                    if (isMajor) {
                        stopFireworksRef.current = fireMajorPrizeFireworks(
                            FIREWORKS_DURATION_MS,
                        );
                    }
                },
                elapsed + timing.nameRevealMs - timing.gapMs,
            ),
        );

        if (isMajor) {
            timeouts.push(
                window.setTimeout(
                    () => {
                        stopFireworksRef.current = null;
                    },
                    elapsed +
                        timing.nameRevealMs -
                        timing.gapMs +
                        FIREWORKS_DURATION_MS,
                ),
            );
        }

        return () => {
            timeouts.forEach((timeout) => window.clearTimeout(timeout));
            stopFireworksRef.current?.();
            stopFireworksRef.current = null;
        };
    }, [flash.drawn_winner]);

    useEffect(() => {
        if (!revealedName || !revealedNameRef.current) {
            return;
        }

        // Let the fade-up settle, then burst from the name (ConfettiButton style).
        const timeout = window.setTimeout(() => {
            if (revealedNameRef.current) {
                fireConfettiFromElement(revealedNameRef.current);
            }
        }, 80);

        return () => {
            window.clearTimeout(timeout);
        };
    }, [revealedName]);

    const visibleWinners = useMemo(
        () =>
            pendingEmployeeNumber === null
                ? winners
                : winners.filter(
                      (winner) =>
                          winner.employee_number !== pendingEmployeeNumber,
                  ),
        [winners, pendingEmployeeNumber],
    );

    useEffect(() => {
        const viewport = winnersViewportRef.current;
        const list = winnersListRef.current;

        if (!viewport || !list || visibleWinners.length === 0) {
            setWinnersMarquee(false);
            return;
        }

        const update = () => {
            setWinnersMarquee(list.scrollHeight > viewport.clientHeight + 4);
        };

        update();
        const observer = new ResizeObserver(update);
        observer.observe(viewport);
        observer.observe(list);

        return () => observer.disconnect();
    }, [visibleWinners]);

    useEffect(() => {
        const viewport = winnersViewportRef.current;
        const track = winnersTrackRef.current;

        if (!viewport || !track) {
            return;
        }

        if (!winnersMarquee || visibleWinners.length === 0) {
            track.style.transform = "";
            return;
        }

        let frame = 0;
        let offset = 0;
        let last = performance.now();
        let hovering = false;
        const speedPxPerSec = 36;

        const onEnter = () => {
            hovering = true;
        };
        const onLeave = () => {
            hovering = false;
        };

        viewport.addEventListener("pointerenter", onEnter);
        viewport.addEventListener("pointerleave", onLeave);

        const tick = (now: number) => {
            const dt = Math.min(now - last, 48) / 1000;
            last = now;

            // Two identical copies; loop when the first copy has fully scrolled away.
            const loopHeight = track.scrollHeight / 2;

            if (!hovering && loopHeight > viewport.clientHeight) {
                offset += speedPxPerSec * dt;
                if (offset >= loopHeight) {
                    offset -= loopHeight;
                }
                track.style.transform = `translate3d(0, ${-offset}px, 0)`;
            }

            frame = window.requestAnimationFrame(tick);
        };

        frame = window.requestAnimationFrame(tick);

        return () => {
            window.cancelAnimationFrame(frame);
            viewport.removeEventListener("pointerenter", onEnter);
            viewport.removeEventListener("pointerleave", onLeave);
            track.style.transform = "";
        };
    }, [winnersMarquee, visibleWinners]);

    const isRacing = awaitingDraw || animating;
    const drawBusy = awaitingDraw || animating;
    const canDraw =
        eligibleCount > 0 &&
        availablePrizes.length > 0 &&
        selectedPrizeId !== "" &&
        !drawBusy;

    const reelMode = (index: number): DigitReelMode => {
        if (landingFlags[index]) {
            return "landing";
        }

        if (revealedFlags[index]) {
            return "locked";
        }

        if (isRacing) {
            return "racing";
        }

        return "idle";
    };

    return (
        <div className="relative h-dvh overflow-hidden bg-stage text-[#e8f0f5]">
            <Head title="Draw" />

            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-cover bg-center bg-no-repeat"
                style={{ backgroundImage: `url(${casinoBg})` }}
            />
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(11,21,32,0.45),rgba(11,21,32,0.78)_70%)]"
            />

            <div className="relative grid h-full min-h-0 grid-cols-1 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <main className="flex min-h-0 flex-col overflow-x-hidden overflow-y-auto px-4 py-4 sm:px-8 sm:py-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="mt-1 text-sm text-mist">
                                Year-End Recognition · {new Date().getFullYear()}
                            </p>
                            <p className="mt-1 text-xs uppercase tracking-[0.22em] text-mist-dim">
                                {eligibleCount} eligible · {winnerCount} drawn
                            </p>
                        </div>
                        <div className="flex items-center gap-3 text-xs text-mist-dim">
                            <span className="hidden sm:inline">
                                {auth.user?.name}
                            </span>
                            <Link
                                href={participantsIndex.url()}
                                className="border border-stage-border px-2.5 py-1.5 hover:border-gold hover:text-gold"
                            >
                                Manage
                            </Link>
                            <Link
                                href={logout.url()}
                                method="post"
                                as="button"
                                className="border border-stage-border px-2.5 py-1.5 hover:border-gold hover:text-gold"
                            >
                                Log out
                            </Link>
                        </div>
                    </div>

                    <div className="flex min-h-0 flex-1 flex-col items-center justify-center gap-5 py-4">
                        <SlotMachine
                            size="stage"
                            lit
                            title={name}
                        >
                            {digits.map((digit, index) => (
                                <DigitReel
                                    key={index}
                                    targetDigit={digit}
                                    mode={reelMode(index)}
                                    landingDurationMs={landDurationMs}
                                    idleDelayMs={index * 700}
                                />
                            ))}
                        </SlotMachine>

                        <div className="min-h-24 w-full max-w-3xl text-center">
                            {revealedName ? (
                                <div className="animate-[fade-up_500ms_ease-out]">
                                    <p className="text-sm uppercase tracking-[0.28em] text-[#d4a574]">
                                        Congratulations
                                    </p>
                                    <p
                                        ref={revealedNameRef}
                                        className="mt-2 font-[family-name:var(--font-display)] text-5xl tracking-wide text-gold-bright drop-shadow-[0_0_24px_rgba(240,215,140,0.35)] sm:text-6xl lg:text-7xl"
                                    >
                                        {revealedName}
                                    </p>
                                </div>
                            ) : awaitingDraw ? (
                                <p className="mt-3 text-mist-dim">
                                    Preparing the draw…
                                </p>
                            ) : animating ? (
                                <div>
                                    <p className="mt-3 text-mist-dim">
                                        {activeReel === null
                                            ? "Revealing employee number…"
                                            : `Locking digit ${activeReel + 1} of 4…`}
                                    </p>
                                </div>
                            ) : latestWinner ? (
                                <p className="mt-3 text-mist-dim">
                                    Last recognized: {latestWinner.full_name} (
                                    {latestWinner.employee_number})
                                </p>
                            ) : (
                                <p className="mt-3 text-mist-dim">
                                    {eligibleCount > 0
                                        ? "Select a prize, then press Draw to recognize the next winner."
                                        : "No eligible participants remaining."}
                                </p>
                            )}
                        </div>

                        <Form
                            {...store.form()}
                            className="flex w-full max-w-md flex-col items-center gap-3"
                            options={{ preserveScroll: true }}
                            onStart={() => {
                                setDrawArmed(false);
                                setAwaitingDraw(true);
                                setRevealedName(null);
                                setRevealedEmployeeNumber(null);
                                setPrizeLabel(null);
                                setLandDurationMs(
                                    timingForPrizeType(selectedPrize?.type)
                                        .landMs,
                                );
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
                                    <select
                                        name="prize_id"
                                        value={selectedPrizeId}
                                        onChange={(event) =>
                                            setSelectedPrizeId(
                                                event.target.value,
                                            )
                                        }
                                        required
                                        className="w-full rounded-xl border-2 border-[#c9a84c]/50 bg-[#120a0e] px-4 py-3.5 text-center text-base text-[#e8f0f5] outline-none focus:border-gold disabled:opacity-50 sm:text-lg"
                                        disabled={
                                            processing ||
                                            drawBusy ||
                                            availablePrizes.length === 0
                                        }
                                    >
                                        {availablePrizes.length === 0 && (
                                            <option value="">
                                                No prizes remaining
                                            </option>
                                        )}
                                        {availablePrizes.map((prize) => (
                                            <option
                                                key={prize.id}
                                                value={prize.id}
                                            >
                                                {prize.name} ({prize.remaining}{" "}
                                                left)
                                            </option>
                                        ))}
                                    </select>
                                    {errors.prize_id && (
                                        <p className="text-sm text-[#ff8f8f]">
                                            {errors.prize_id}
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
                                        onClick={(event) => {
                                            if (drawBusy || processing) {
                                                return;
                                            }

                                            if (!drawArmed) {
                                                event.preventDefault();
                                                setDrawArmed(true);
                                            }
                                        }}
                                        className={`min-w-56 rounded-full border-2 px-10 py-3.5 font-[family-name:var(--font-display)] text-3xl tracking-wide transition active:translate-y-1 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none disabled:active:translate-y-0 ${
                                            drawArmed
                                                ? "animate-pulse border-[#ffe6a3] bg-gradient-to-b from-[#fff0c2] via-gold-bright to-gold text-[#1a0f14] shadow-[0_8px_0_#9a6a12,0_12px_28px_rgba(240,215,140,0.45)]"
                                                : "border-[#c9a84c] bg-gradient-to-b from-gold-bright via-gold to-[#c9a84c] text-[#1a0f14] shadow-[0_8px_0_#7a5a1a,0_12px_24px_rgba(0,0,0,0.35)] hover:brightness-105 active:shadow-[0_4px_0_#7a5a1a]"
                                        }`}
                                    >
                                        {processing || drawBusy
                                            ? "Drawing…"
                                            : drawArmed
                                              ? "Press again"
                                              : "Draw"}
                                    </button>
                                    {!drawBusy &&
                                        !processing &&
                                        canDraw &&
                                        (drawArmed ? (
                                            <p className="text-xs text-gold">
                                                Press again within 3s to confirm
                                            </p>
                                        ) : (
                                            <p className="text-xs text-mist-dim">
                                                First press arms Draw — second
                                                press runs it
                                            </p>
                                        ))}
                                </>
                            )}
                        </Form>
                    </div>
                </main>

                <aside className="flex min-h-0 flex-col border-t border-stage-border bg-stage-panel/95 lg:border-t-0 lg:border-l">
                    <div className="shrink-0 border-b border-stage-border px-4 py-4">
                        <h2 className="font-[family-name:var(--font-display)] text-2xl tracking-wide text-gold">
                            Recognition board
                        </h2>
                        <p className="text-xs text-mist-dim">
                            {winnerCount} recognized so far
                        </p>
                    </div>

                    <div
                        ref={winnersViewportRef}
                        className="min-h-0 flex-1 overflow-hidden"
                    >
                        {visibleWinners.length === 0 ? (
                            <p className="px-4 py-10 text-center text-sm text-mist-dim">
                                No one recognized yet.
                            </p>
                        ) : (
                            <div
                                ref={winnersTrackRef}
                                className={
                                    winnersMarquee
                                        ? "will-change-transform"
                                        : undefined
                                }
                            >
                                {(winnersMarquee ? [0, 1] : [0]).map(
                                    (copy) => (
                                        <ol
                                            key={copy}
                                            ref={
                                                copy === 0
                                                    ? winnersListRef
                                                    : undefined
                                            }
                                            aria-hidden={copy === 1}
                                            className="divide-y divide-stage-border/70"
                                        >
                                            {visibleWinners.map(
                                                (winner, index) => (
                                                    <li
                                                        key={`${copy}-${winner.id}`}
                                                        className={`px-4 py-3 ${
                                                            copy === 0 &&
                                                            revealedName !==
                                                                null &&
                                                            index === 0 &&
                                                            winner.full_name ===
                                                                revealedName
                                                                ? "bg-[#1a2f3d] animate-[fade-up_500ms_ease-out]"
                                                                : ""
                                                        }`}
                                                    >
                                                        <div className="flex items-baseline justify-between gap-2">
                                                            <p className="font-medium text-[#e8f0f5]">
                                                                {
                                                                    winner.full_name
                                                                }
                                                            </p>
                                                            <p className="font-mono text-sm tracking-wider text-gold">
                                                                {
                                                                    winner.employee_number
                                                                }
                                                            </p>
                                                        </div>
                                                        <div className="mt-1 flex items-baseline justify-between gap-2">
                                                            <p className="text-xs uppercase tracking-[0.14em] text-mist-dim">
                                                                {winner.prize_label ??
                                                                    "Prize"}
                                                            </p>
                                                            {formatDrawnAt(
                                                                winner.drawn_at,
                                                            ) && (
                                                                <p className="text-[0.65rem] text-mist-dim/80">
                                                                    {formatDrawnAt(
                                                                        winner.drawn_at,
                                                                    )}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </li>
                                                ),
                                            )}
                                        </ol>
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                </aside>
            </div>
        </div>
    );
}
