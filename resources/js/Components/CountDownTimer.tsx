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

    const { minutes, seconds } = formatTime(timeLeft);
    const urgent = timeLeft <= 60 && timeLeft > 0;
    return <div className={`learn-timer${urgent ? ' is-urgent' : ''}`} role="timer" aria-label={`Sisa waktu ${minutes} menit ${seconds} detik`}>
        <i className="bx bx-time-five" aria-hidden="true" />
        <span className="learn-timer__label">Sisa waktu</span>
        <strong className="learn-timer__value">{display === 'minutes' ? `${Math.ceil(timeLeft / 60)} menit` : `${minutes}:${seconds}`}</strong>
    </div>;
};

export default CountdownTimer;
