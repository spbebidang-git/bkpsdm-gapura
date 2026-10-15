import { useEffect, useState } from 'react';

interface CountdownTimerProps {
    id?: any;
    initialSeconds?: number;
    currentSeconds?: number;
    onTimeout?: () => void;
    resetTrigger?: any;
    variant?: 'danger' | 'muted';
    display?: 'clock' | 'minutes';
}

const CountdownTimer = ({
    id,
    initialSeconds = 600,
    currentSeconds,
    onTimeout,
    resetTrigger,
    variant = 'danger',
    display = 'clock'
}: CountdownTimerProps) => {
    const isControlled = typeof currentSeconds === 'number';
    const [internalTimeLeft, setInternalTimeLeft] = useState(initialSeconds);
    const storageKey = `countdownStartTime_${id}`;
    const timeLeft = isControlled ? Math.max(currentSeconds, 0) : internalTimeLeft;

    // Fungsi untuk mereset timer ke awal
    const resetTimer = () => {
        const now = Math.floor(Date.now() / 1000);
        localStorage.setItem(storageKey, now.toString());
        setInternalTimeLeft(initialSeconds);
    };

    useEffect(() => {
        if (isControlled) {
            return;
        }

        const now = Math.floor(Date.now() / 1000);
        const storedStartTime = localStorage.getItem(storageKey);

        if (storedStartTime) {
            const elapsed = now - parseInt(storedStartTime, 10);
            const remainingTime = initialSeconds - elapsed;

            if (remainingTime > 0) {
                setInternalTimeLeft(remainingTime);
            } else {
                setInternalTimeLeft(0);
                if (onTimeout) onTimeout();
            }
        } else {
            resetTimer();
        }
    }, [id, initialSeconds, isControlled, onTimeout, resetTrigger]);

    useEffect(() => {
        if (isControlled) {
            return;
        }

        if (timeLeft <= 0) {
            localStorage.removeItem(storageKey);
            return;
        }

        const timer = setInterval(() => {
            setInternalTimeLeft(prevTime => {
                const newTime = prevTime - 1;
                if (newTime <= 0) {
                    clearInterval(timer);
                    localStorage.removeItem(storageKey);
                    if (onTimeout) onTimeout();
                    return 0;
                }
                return newTime;
            });
        }, 1000);

        return () => clearInterval(timer);
    }, [isControlled, onTimeout, storageKey, timeLeft]);

    const formatTime = (time) => {
        const minutes = Math.floor(time / 60).toString().padStart(2, '0');
        const seconds = (time % 60).toString().padStart(2, '0');
        return { minutes, seconds };
    };

    const minutesOnly = Math.ceil(timeLeft / 60);

    const variantClasses: Record<string, { wrapper: string; box: string; accent: string; helper?: string }> = {
        danger: {
            wrapper: 'text-red-500',
            box: 'bg-red-500 text-white shadow',
            accent: 'text-red-500',
            helper: 'text-red-400'
        },
        muted: {
            wrapper: 'bg-gray-100 border border-gray-200 rounded-lg px-3 py-2 text-gray-800 shadow-sm',
            box: 'text-gray-900',
            accent: 'text-gray-700',
            helper: 'text-gray-600'
        }
    };

    const { minutes, seconds } = formatTime(timeLeft);
    const selectedVariant = variantClasses[variant] ?? variantClasses.danger;

    if (display === 'minutes') {
        return (
            <div className={`flex items-center gap-2 ${selectedVariant.wrapper}`}>
                <div className={`min-w-[56px] h-11 px-4 rounded-md flex items-center justify-center text-xl font-bold ${selectedVariant.box}`}>
                    {minutesOnly}
                </div>
                <div className="leading-tight text-left">
                    <div className={`text-[11px] font-bold uppercase tracking-[0.18em] ${selectedVariant.accent}`}>
                        Menit
                    </div>
                    <div className={`text-[11px] ${selectedVariant.helper}`}>
                        tersisa
                    </div>
                </div>
            </div>
        );
    }

    if (variant === 'muted') {
        return (
            <div className={`inline-flex items-center ${selectedVariant.wrapper}`}>
                <div className="flex items-center gap-1.5">
                    <span className={`min-w-[34px] text-center text-lg font-bold tabular-nums ${selectedVariant.box}`}>
                        {minutes}
                    </span>
                    <span className={`text-lg font-bold tabular-nums ${selectedVariant.accent}`}>:</span>
                    <span className={`min-w-[34px] text-center text-lg font-bold tabular-nums ${selectedVariant.box}`}>
                        {seconds}
                    </span>
                </div>
            </div>
        );
    }

    return (
        <div className={`flex gap-2 items-center ${selectedVariant.wrapper}`}>
            <div className={`w-11 h-11 rounded-md flex items-center justify-center text-2xl font-bold ${selectedVariant.box}`}>
                {minutes}
            </div>
            <span className={`text-2xl font-bold ${selectedVariant.accent}`}>:</span>
            <div className={`w-11 h-11 rounded-md flex items-center justify-center text-2xl font-bold ${selectedVariant.box}`}>
                {seconds}
            </div>
        </div>
    );
};

export default CountdownTimer;