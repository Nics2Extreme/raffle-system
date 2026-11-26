import confetti, { type Options as ConfettiOptions } from 'canvas-confetti';

const FIREWORKS_DURATION_MS = 5_000;

/**
 * Magic UI ConfettiButton-style burst from an element's center.
 */
export function fireConfettiFromElement(
    element: HTMLElement,
    options: ConfettiOptions = {},
): void {
    const rect = element.getBoundingClientRect();
    const origin = {
        x: (rect.left + rect.width / 2) / window.innerWidth,
        y: (rect.top + rect.height / 2) / window.innerHeight,
    };

    void confetti({
        particleCount: 100,
        spread: 70,
        startVelocity: 35,
        zIndex: 9999,
        colors: ['#f0d78c', '#ffe6a3', '#e8f0f5', '#c8d6df', '#ff8f8f'],
        ...options,
        origin,
    });
}

/**
 * Magic UI–style fireworks burst for major prize reveals.
 * Returns a cleanup function that stops the interval early.
 */
export function fireMajorPrizeFireworks(
    durationMs = FIREWORKS_DURATION_MS,
): () => void {
    const animationEnd = Date.now() + durationMs;
    const defaults = {
        startVelocity: 30,
        spread: 360,
        ticks: 60,
        zIndex: 40,
        colors: ['#f0d78c', '#ffe6a3', '#e8f0f5', '#c8d6df', '#ff8f8f'],
    };

    const randomInRange = (min: number, max: number): number =>
        Math.random() * (max - min) + min;

    const interval = window.setInterval(() => {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            window.clearInterval(interval);

            return;
        }

        const particleCount = 50 * (timeLeft / durationMs);

        confetti({
            ...defaults,
            particleCount,
            origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
        });
        confetti({
            ...defaults,
            particleCount,
            origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
        });
    }, 250);

    return () => {
        window.clearInterval(interval);
    };
}

export { FIREWORKS_DURATION_MS };
