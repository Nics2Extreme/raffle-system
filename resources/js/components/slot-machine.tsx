import { Children, type ReactNode } from 'react';

type SlotMachineProps = {
    children: ReactNode;
    celebrating: boolean;
};

const CONFETTI_PIECES = [
    { left: '10%', delay: '0ms', color: '#f0d78c', rotate: '12deg' },
    { left: '20%', delay: '40ms', color: '#ffe6a3', rotate: '-20deg' },
    { left: '30%', delay: '80ms', color: '#c8d6df', rotate: '35deg' },
    { left: '40%', delay: '20ms', color: '#f0d78c', rotate: '-8deg' },
    { left: '50%', delay: '100ms', color: '#ffe6a3', rotate: '22deg' },
    { left: '60%', delay: '60ms', color: '#e8f0f5', rotate: '-30deg' },
    { left: '70%', delay: '10ms', color: '#f0d78c', rotate: '15deg' },
    { left: '80%', delay: '90ms', color: '#ffe6a3', rotate: '-12deg' },
    { left: '15%', delay: '120ms', color: '#c8d6df', rotate: '-25deg' },
    { left: '45%', delay: '70ms', color: '#f0d78c', rotate: '5deg' },
    { left: '75%', delay: '30ms', color: '#ffe6a3', rotate: '-18deg' },
    { left: '88%', delay: '50ms', color: '#e8f0f5', rotate: '28deg' },
];

export default function SlotMachine({
    children,
    celebrating,
}: SlotMachineProps) {
    const reels = Children.toArray(children);

    return (
        <div className="relative mx-auto w-full max-w-2xl">
            <div className="rounded-3xl border border-stage-border bg-stage-panel p-4 sm:p-6">
                <div className="flex min-w-0 gap-2 sm:gap-3">
                    {reels.map((child, index) => (
                        <div
                            key={index}
                            className="relative min-w-0 flex-1 overflow-hidden rounded-2xl bg-[#071018] shadow-[inset_0_0_24px_rgba(0,0,0,0.55)]"
                            style={{ height: '7rem' }}
                        >
                            {child}
                        </div>
                    ))}
                </div>
            </div>

            {celebrating && (
                <div
                    className="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl"
                    aria-hidden
                >
                    {CONFETTI_PIECES.map((piece, index) => (
                        <span
                            key={index}
                            className="slot-confetti absolute top-[-8%] h-3 w-2 rounded-sm"
                            style={{
                                left: piece.left,
                                backgroundColor: piece.color,
                                animationDelay: piece.delay,
                                transform: `rotate(${piece.rotate})`,
                            }}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
