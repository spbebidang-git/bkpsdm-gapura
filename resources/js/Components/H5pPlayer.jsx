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
    const [height, setHeight] = useState(520);

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
                setHeight(h + 8); // sedikit ruang bawah
            }
        };
        window.addEventListener("message", onMessage);
        return () => window.removeEventListener("message", onMessage);
    }, []);

    if (!src) {
        return (
            <div className="flex items-center justify-center h-32 text-red-500 text-sm">
                Konten H5P tidak tersedia.
            </div>
        );
    }

    return (
        // Batasi lebar player supaya tinggi (mengikuti rasio video) muat di
        // area materi tanpa scroll. Konten H5P menyesuaikan ke lebar iframe.
        <div className="mx-auto w-full" style={{ maxWidth: "760px" }}>
            <iframe
                ref={iframeRef}
                src={src}
                title="Konten H5P"
                className="w-full rounded-xl border border-gray-100 block"
                style={{ height: `${height}px` }}
                allow="autoplay; fullscreen; encrypted-media; microphone; camera"
                allowFullScreen
                scrolling="no"
            />
        </div>
    );
};

export default H5pPlayer;
