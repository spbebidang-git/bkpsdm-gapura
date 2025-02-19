import { useEffect, useState } from 'react';

const CountdownTimer = ({ initialSeconds = 600 }) => {
    const [timeLeft, setTimeLeft] = useState(initialSeconds);

    useEffect(() => {
        if (timeLeft <= 0) return;

        const timer = setInterval(() => {
            setTimeLeft(prevTime => prevTime - 1);
        }, 1000);

        return () => clearInterval(timer);
    }, [timeLeft]);

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
