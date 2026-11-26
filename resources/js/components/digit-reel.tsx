import { useEffect, useRef } from 'react';

export type DigitReelMode = 'idle' | 'racing' | 'landing' | 'locked';

type DigitReelProps = {
    targetDigit: string | null;
    mode: DigitReelMode;
    landingDurationMs: number;
    /** Stagger idle motion so reels feel independent. */
    idleDelayMs?: number;
};

/** Tall enough for land animation: idle loop + spin cycles + final digit. */
const STRIP = Array.from({ length: 80 }, (_, index) => String(index % 10));
/** Full 0–9 passes during the land animation. */
const SPIN_CYCLES = 5;

export default function DigitReel({
    targetDigit,
    mode,
    landingDurationMs,
    idleDelayMs = 0,
}: DigitReelProps) {
    const stripRef = useRef<HTMLDivElement>(null);
    const digit = targetDigit ?? '0';
    const finalIndex = SPIN_CYCLES * 10 + Number(digit);

    useEffect(() => {
        const strip = stripRef.current;

        if (!strip || mode !== 'landing') {
            return;
        }

        strip.style.animation = 'none';
        strip.style.transform = 'translateY(0)';
        void strip.offsetHeight;
        strip.style.setProperty(
            '--reel-land-to',
            `calc(var(--reel-cell-h) * -${finalIndex})`,
        );
        strip.style.animation = `reel-land ${landingDurationMs}ms cubic-bezier(0.12, 0.7, 0.2, 1) forwards`;
    }, [mode, finalIndex, landingDurationMs]);

    let transform = 'translateY(0)';
    let animation = 'none';
    let animationDelay = '0ms';

    if (mode === 'locked') {
        transform = `translateY(calc(var(--reel-cell-h) * -${finalIndex}))`;
    } else if (mode === 'idle') {
        animation = 'reel-roll 3.2s linear infinite';
        animationDelay = `-${idleDelayMs}ms`;
    } else if (mode === 'racing') {
        animation = 'reel-roll 0.28s linear infinite';
    }

    return (
        <div className="reel-window relative h-full w-full overflow-hidden">
            <div
                ref={stripRef}
                className="absolute inset-x-0 top-0 will-change-transform"
                style={{
                    transform: mode === 'landing' ? undefined : transform,
                    animation: mode === 'landing' ? undefined : animation,
                    animationDelay: mode === 'idle' ? animationDelay : undefined,
                    ['--reel-land-to' as string]: `calc(var(--reel-cell-h) * -${finalIndex})`,
                }}
            >
                {STRIP.map((value, index) => (
                    <div
                        key={`${value}-${index}`}
                        className="reel-digit flex items-center justify-center border-b border-white/[0.04] font-[family-name:var(--font-display)] text-gold"
                        style={{ height: 'var(--reel-cell-h)' }}
                    >
                        <span className="leading-none tracking-wide drop-shadow-[0_2px_8px_rgba(240,215,140,0.18)]">
                            {value}
                        </span>
                    </div>
                ))}
            </div>

            <div
                className="pointer-events-none absolute inset-0"
                aria-hidden
            >
                <div className="absolute inset-x-0 top-0 h-[28%] bg-gradient-to-b from-[#050b12] via-[#050b12]/70 to-transparent" />
                <div className="absolute inset-x-0 bottom-0 h-[28%] bg-gradient-to-t from-[#050b12] via-[#050b12]/70 to-transparent" />
                <div className="absolute inset-y-0 left-0 w-px bg-white/5" />
                <div className="absolute inset-y-0 right-0 w-px bg-black/40" />
            </div>

            {(mode === 'landing' || mode === 'locked') && (
                <div
                    className={`pointer-events-none absolute inset-0 rounded-[inherit] ${
                        mode === 'locked'
                            ? 'shadow-[inset_0_0_0_1.5px_rgba(240,215,140,0.55)]'
                            : 'shadow-[inset_0_0_0_1.5px_rgba(255,230,163,0.75)]'
                    }`}
                    aria-hidden
                />
            )}
        </div>
    );
}
