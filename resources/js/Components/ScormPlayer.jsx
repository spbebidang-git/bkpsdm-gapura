import { useMemo } from "react";

/**
 * Menjalankan paket SCORM di dalam <iframe> terisolasi (/scorm-view/{folder}).
 * Halaman pemutar menyediakan SCORM API (window.API / API_1484_11) dan
 * melaporkan penyelesaian lewat pesan 'scorm-completed'.
 *
 * props.path = URL folder paket, mis. "/assets/media/scorm/scorm_abcd1234".
 */
const ScormPlayer = ({ path }) => {
    const src = useMemo(() => {
        if (!path) return null;
        const folder = path.split("/").filter(Boolean).pop();
        return `/scorm-view/${folder}`;
    }, [path]);

    if (!src) {
        return (
            <div className="flex items-center justify-center h-32 text-red-500 text-sm">
                Modul interaktif tidak tersedia.
            </div>
        );
    }

    return (
        // Ikuti tinggi panel materi agar player tidak membuat scrollbar halaman.
        // Konten SCORM (mis. Storyline) akan melakukan scaling di dalam iframe.
        <div className="mx-auto h-full min-h-0 w-full" style={{ maxWidth: "1280px" }}>
            <div className="relative h-full min-h-0 w-full overflow-hidden rounded-xl border border-gray-100 bg-black">
                <iframe
                    src={src}
                    title="Modul Interaktif"
                    className="absolute inset-0 block h-full w-full"
                    allow="autoplay; fullscreen; encrypted-media; microphone; camera"
                    allowFullScreen
                />
            </div>
        </div>
    );
};

export default ScormPlayer;
