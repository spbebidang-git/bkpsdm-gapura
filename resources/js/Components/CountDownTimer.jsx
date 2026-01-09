import { useEffect, useState } from 'react';

const CountdownTimer = ({ id, initialSeconds = 600, onTimeout, resetTrigger }) => {
    const [timeLeft, setTimeLeft] = useState(initialSeconds);
    const storageKey = `countdownStartTime_${id}`;

    // Fungsi untuk mereset timer ke awal
    const resetTimer = () => {
        const now = Math.floor(Date.now() / 1000);
        localStorage.setItem(storageKey, now.toString());
        setTimeLeft(initialSeconds);
    };

    useEffect(() => {
        const now = Math.floor(Date.now() / 1000);
        const storedStartTime = localStorage.getItem(storageKey);

        if (storedStartTime) {
            const elapsed = now - parseInt(storedStartTime, 10);
            const remainingTime = initialSeconds - elapsed;

            if (remainingTime > 0) {
                setTimeLeft(remainingTime);
            } else {
                setTimeLeft(0);
                if (onTimeout) onTimeout();
            }
        } else {
            resetTimer();
        }
    }, [id, initialSeconds, resetTrigger]); // resetTrigger ditambahkan di sini

    useEffect(() => {
        if (timeLeft <= 0) {
            localStorage.removeItem(storageKey);
            return;
        }

        const timer = setInterval(() => {
            setTimeLeft(prevTime => {
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
    }, [timeLeft, onTimeout, storageKey]);

    const formatTime = (time) => {
        const minutes = Math.floor(time / 60).toString().padStart(2, '0');
        const seconds = (time % 60).toString().padStart(2, '0');
        return { minutes, seconds };
    };

    const { minutes, seconds } = formatTime(timeLeft);

    return (
        <div className="flex gap-2 items-center text-red-500">
            <div className="w-11 h-11 text-white bg-red-500 rounded-md shadow flex items-center justify-center text-2xl font-bold">
                {minutes}
            </div>
            <span className="text-2xl font-bold">:</span>
            <div className="w-11 h-11 text-white bg-red-500 rounded-md shadow flex items-center justify-center text-2xl font-bold">
                {seconds}
            </div>
        </div>
    );
};

export default CountdownTimer;