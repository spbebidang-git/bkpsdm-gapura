import { useEffect, useMemo, useRef, useState } from "react";

/**
 * Memutar paket H5P di dalam <iframe> terisolasi (/h5p-view/{folder}).
 * Iframe menyesuaikan tingginya otomatis mengikuti pesan 'h5p-resize' dari
 * halaman pemutar, sehingga tidak ada scrollbar di dalam iframe.
 *
 * props.path = URL folder paket, mis. "/assets/media/h5p/h5p_abcd1234".
 */
const H5pPlayer = ({ path }) => {
    const iframeRef = useRef(null);
    const containerRef = useRef(null);
    const [contentHeight, setContentHeight] = useState(520);
    const [availableHeight, setAvailableHeight] = useState(0);

    const src = useMemo(() => {
        if (!path) return null;
        const folder = path.split("/").filter(Boolean).pop();
        return `/h5p-view/${folder}`;
    }, [path]);

    useEffect(() => {
        const onMessage = (ev) => {
            if (ev.source !== iframeRef.current?.contentWindow) return;
            const data = ev.data;
            if (data && data.type === "h5p-resize" && typeof data.height === "number") {
                // Batasi agar tidak terlalu kecil/besar tak wajar.
                const h = Math.min(Math.max(data.height, 320), 4000);
                setContentHeight(h + 8); // sedikit ruang bawah
            }
        };
        window.addEventListener("message", onMessage);
        return () => window.removeEventListener("message", onMessage);
    }, []);

    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;

        const updateAvailableHeight = () => {
            setAvailableHeight(container.clientHeight);
        };

        updateAvailableHeight();

        if (!window.ResizeObserver) {
            window.addEventListener("resize", updateAvailableHeight);
            return () => window.removeEventListener("resize", updateAvailableHeight);
        }

        const observer = new ResizeObserver(updateAvailableHeight);
        observer.observe(container);

        return () => observer.disconnect();
    }, []);

    if (!src) {
        return (
            <div className="flex items-center justify-center h-32 text-red-500 text-sm">
                Modul interaktif tidak tersedia.
            </div>
        );
    }

    const scale = availableHeight > 0 && contentHeight > availableHeight
        ? availableHeight / contentHeight
        : 1;

    return (
        // Render dengan lebar penuh agar layout H5P tidak terpotong, lalu
        // perkecil secara proporsional bila tingginya melebihi area popup.
        <div
            ref={containerRef}
            className="relative mx-auto h-full min-h-0 w-full overflow-hidden"
        >
            <iframe
                ref={iframeRef}
                src={src}
                title="Modul Interaktif"
                className="absolute left-0 top-0 block w-full rounded-xl border border-gray-100"
                style={{
                    height: `${contentHeight}px`,
                    transform: `scale(${scale})`,
                    transformOrigin: "top center",
                }}
                allow="autoplay; fullscreen; encrypted-media; microphone; camera"
                allowFullScreen
                scrolling="no"
            />
        </div>
    );
};

export default H5pPlayer;
