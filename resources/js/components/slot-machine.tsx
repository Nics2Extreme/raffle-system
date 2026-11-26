import { Children, type ReactNode } from "react";
import luckySeven from "../../assets/prople-slot.png";

type SlotMachineProps = {
    children: ReactNode;
    /** Larger reels for the presentation stage. */
    size?: "default" | "stage";
    /** Animate marquee bulbs while spinning / celebrating. */
    lit?: boolean;
    title?: string;
};

function MarqueeLights({ count, lit }: { count: number; lit: boolean }) {
    return (
        <div className="flex items-center justify-center gap-2 sm:gap-2.5">
            {Array.from({ length: count }, (_, index) => (
                <span
                    key={index}
                    className={`slot-bulb size-2.5 rounded-full sm:size-3 ${
                        lit ? "slot-bulb-lit" : "bg-[#3a2a12] shadow-inner"
                    }`}
                    style={
                        lit ? { animationDelay: `${index * 90}ms` } : undefined
                    }
                />
            ))}
        </div>
    );
}

export default function SlotMachine({
    children,
    size = "default",
    lit = false,
    title = "RAFFLE",
}: SlotMachineProps) {
    const reels = Children.toArray(children);
    const isStage = size === "stage";
    const cellHeight = isStage ? "10rem" : "7.5rem";
    const bulbCount = isStage ? 9 : 7;

    return (
        <div
            className={`relative mx-auto w-full ${
                isStage ? "max-w-3xl" : "max-w-xl"
            }`}
        >
            {/* Crown — reserved space above the cabinet */}
            <div
                className={`relative z-20 flex justify-center ${
                    isStage ? "-mb-5 pt-2 sm:-mb-6" : "-mb-4 pt-1"
                }`}
            >
                <img
                    src={luckySeven}
                    alt=""
                    aria-hidden
                    className={`pointer-events-none block w-[60%] max-w-[14rem] drop-shadow-[0_12px_28px_rgba(0,0,0,0.55)] ${
                        isStage ? "sm:w-[36%]" : ""
                    }`}
                />
            </div>

            {/* Cabinet */}
            <div
                className={`relative z-10 isolate overflow-hidden
                            border-2 border-[#c9a84c]/70
                            bg-gradient-to-b from-[#1a0f14] via-[#2a1218] to-[#120a0e]
                            bg-clip-padding
                            shadow-[0_20px_60px_rgba(0,0,0,0.55),0_0_40px_rgba(240,215,140,0.08),inset_0_1px_0_rgba(255,255,255,0.12)]
                            ${isStage ? "rounded-[1.75rem] sm:rounded-[2.25rem]" : "rounded-3xl"}`}
            >
                <div
                    className={`space-y-3 ${isStage ? "p-4 sm:p-5" : "p-3 sm:p-4"}`}
                >
                    <MarqueeLights count={bulbCount} lit={lit} />

                    {/* Gold bezel + reel window */}
                    <div className="rounded-2xl border-[3px] border-[#c9a84c] bg-gradient-to-b from-[#3a2a12] via-[#1a1208] to-[#3a2a12] p-1.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.25),0_8px_24px_rgba(0,0,0,0.4)] sm:p-2">
                        <div className="rounded-xl border border-[#5c3d18] bg-[#050b12] p-2 sm:p-2.5">
                            <div
                                className={`flex min-w-0 gap-1.5 sm:gap-2 ${isStage ? "sm:gap-2.5" : ""}`}
                                style={{
                                    ["--reel-cell-h" as string]: cellHeight,
                                }}
                            >
                                {reels.map((child, index) => (
                                    <div
                                        key={index}
                                        className="relative min-w-0 flex-1 overflow-hidden rounded-lg bg-[#0a0608] shadow-[inset_0_12px_28px_rgba(0,0,0,0.75),inset_0_-10px_20px_rgba(0,0,0,0.5)] ring-1 ring-[#c9a84c]/25"
                                        style={{ height: cellHeight }}
                                    >
                                        {child}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <MarqueeLights count={bulbCount} lit={lit} />
                </div>

                {/* Lower console trim */}
                <div className="relative border-t border-[#c9a84c]/35 bg-gradient-to-b from-[#2a1218] to-[#120a0e] px-4 py-3 sm:px-6">
                    <div className="mx-auto h-1.5 max-w-xs rounded-full bg-gradient-to-r from-transparent via-[#c9a84c]/70 to-transparent" />
                </div>
            </div>
        </div>
    );
}
