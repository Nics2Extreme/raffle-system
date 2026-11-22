import { useEffect, useRef } from 'react';

export type DigitReelMode = 'idle' | 'racing' | 'landing' | 'locked';

type DigitReelProps = {
    targetDigit: string | null;
    mode: DigitReelMode;
    landingDurationMs: number;
    /** Stagger idle motion so reels feel independent. */
    idleDelayMs?: number;
};

const STRIP = Array.from({ length: 70 }, (_, index) => String(index % 10));
const CELL_REM = 7;
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
    const landedOffset = finalIndex * CELL_REM;

    useEffect(() => {
        const strip = stripRef.current;

        if (!strip || mode !== 'landing') {
            return;
        }

        strip.style.animation = 'none';
        strip.style.transform = 'translateY(0)';
        void strip.offsetHeight;
        strip.style.setProperty('--reel-land-to', `-${landedOffset}rem`);
        strip.style.animation = `reel-land ${landingDurationMs}ms cubic-bezier(0.22, 0.61, 0.36, 1) forwards`;
    }, [mode, landedOffset, landingDurationMs]);

    let transform = 'translateY(0)';
    let animation = 'none';
    let animationDelay = '0ms';

    if (mode === 'locked') {
        transform = `translateY(-${landedOffset}rem)`;
    } else if (mode === 'idle') {
        animation = 'reel-roll 2.8s linear infinite';
        animationDelay = `-${idleDelayMs}ms`;
    } else if (mode === 'racing') {
        animation = 'reel-roll 0.32s linear infinite';
    }

    const ringClass =
        mode === 'locked'
            ? 'ring-2 ring-gold'
            : mode === 'landing'
              ? 'ring-2 ring-gold-bright'
              : mode === 'racing'
                ? 'ring-1 ring-stage-border'
                : 'ring-1 ring-stage-border/60';

    return (
        <div
            className={`relative h-full w-full overflow-hidden ${ringClass}`}
            style={{ height: `${CELL_REM}rem` }}
        >
            <div
                ref={stripRef}
                className="absolute inset-x-0 top-0 will-change-transform"
                style={{
                    transform: mode === 'landing' ? undefined : transform,
                    animation: mode === 'landing' ? undefined : animation,
                    animationDelay: mode === 'idle' ? animationDelay : undefined,
                    ['--reel-land-to' as string]: `-${landedOffset}rem`,
                }}
            >
                {STRIP.map((value, index) => (
                    <div
                        key={`${value}-${index}`}
                        className="flex items-center justify-center font-[family-name:var(--font-display)] text-5xl text-gold sm:text-6xl md:text-7xl"
                        style={{ height: `${CELL_REM}rem` }}
                    >
                        {value}
                    </div>
                ))}
            </div>
            <div className="pointer-events-none absolute inset-x-0 top-0 h-3 bg-[#071018]/50" />
            <div className="pointer-events-none absolute inset-x-0 bottom-0 h-3 bg-[#071018]/50" />
        </div>
    );
}
