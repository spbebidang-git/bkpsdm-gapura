import { useEffect, useState } from 'react';

const CountdownTimer = ({ initialSeconds = 600, onTimeout }) => {
    const [timeLeft, setTimeLeft] = useState(initialSeconds);

    useEffect(() => {
        const storedStartTime = localStorage.getItem('countdownStartTime');
        const now = Math.floor(Date.now() / 1000);

        if (storedStartTime) {
            const elapsed = now - parseInt(storedStartTime, 10);
            const remainingTime = initialSeconds - elapsed;

            if (remainingTime > 0) {
                setTimeLeft(remainingTime);
            } else {
                setTimeLeft(0);
                if (onTimeout) onTimeout(); // Kirim event timeout ke parent
            }
        } else {
            localStorage.setItem('countdownStartTime', now.toString());
        }
    }, [initialSeconds]);

    useEffect(() => {
        if (timeLeft <= 0) {
            localStorage.removeItem('countdownStartTime');
            if (onTimeout) onTimeout();
            return;
        }

        const timer = setInterval(() => {
            setTimeLeft(prevTime => {
                const newTime = prevTime - 1;
                if (newTime <= 0) {
                    clearInterval(timer);
                    localStorage.removeItem('countdownStartTime');
                    if (onTimeout) onTimeout();
                }
                return newTime;
            });
        }, 1000);

        return () => clearInterval(timer);
    }, [timeLeft, onTimeout]);

    const formatTime = (time) => {
        const minutes = Math.floor(time / 60).toString().padStart(2, '0');
        const seconds = (time % 60).toString().padStart(2, '0');
        return { minutes, seconds };
    };

    const { minutes, seconds } = formatTime(timeLeft);

    return (
        <div className="flex gap-2 items-center text-red-500 text-3xl">
            <div className="w-11 h-11 text-white bg-red-500 rounded-md shadow flex items-center justify-center text-2xl font-bold">
                {minutes}
            </div>

            <div className="w-11 h-11 text-white bg-red-500 rounded-md shadow flex items-center justify-center text-2xl font-bold">
                {seconds}
            </div>
        </div>
    );
};

export default CountdownTimer;
