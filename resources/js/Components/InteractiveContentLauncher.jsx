import Activity from "@/Components/Activity";
import { useEffect, useState } from "react";
import { createPortal } from "react-dom";

const InteractiveContentLauncher = ({
    activity,
    activityId,
    courseSlug,
    schedule,
    questionResult,
    answers,
    setAnswers,
    file,
    setFile,
    onFinish,
    isFinishing = false,
}) => {
    const [isOpen, setIsOpen] = useState(false);
    const [isContentCompleted, setIsContentCompleted] = useState(
        activity.status === "done" || activity.status === 1
    );
    const label = activity.type === "h5p"
        ? "H5P"
        : (activity.type === "scorm" ? "SCORM" : "VIDEO");
    const contentPath = activity.type === "h5p"
        ? activity.data?.h5p_path
        : (activity.type === "scorm"
            ? activity.data?.scorm_path
            : activity.data?.src_video);

    useEffect(() => {
        setIsOpen(false);
        setIsContentCompleted(activity.status === "done" || activity.status === 1);
    }, [activity.status, activityId]);

    useEffect(() => {
        if (activity.type === "video") return;

        const expectedMessage = activity.type === "h5p"
            ? "h5p-completed"
            : "scorm-completed";

        const handleCompletionMessage = (event) => {
            if (event.origin !== window.location.origin) return;
            if (event.data?.type !== expectedMessage) return;

            setIsContentCompleted(true);
        };

        window.addEventListener("message", handleCompletionMessage);
        return () => window.removeEventListener("message", handleCompletionMessage);
    }, [activity.type]);

    useEffect(() => {
        if (!isOpen) return;

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";

        const onKeyDown = (event) => {
            if (event.key === "Escape" && !isFinishing) {
                setIsOpen(false);
            }
        };

        window.addEventListener("keydown", onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener("keydown", onKeyDown);
        };
    }, [isFinishing, isOpen]);

    const handleFinish = () => {
        if (isFinishing) return;

        // Modal tetap terbuka selama request agar peserta mendapat indikator
        // penyimpanan. Navigasi sukses akan melepas iframe dan memicu pagehide
        // runtime untuk mengirim state terakhir.
        onFinish();
    };

    const bodyClass = "min-h-0 flex-1 overflow-hidden bg-gray-100 p-2 sm:p-4";

    return (
        <>
            <div className="flex min-h-[280px] flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-primary/25 bg-white px-6 py-10 text-center">
                <div className="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <svg viewBox="0 0 24 24" className="h-8 w-8" fill="currentColor" aria-hidden="true">
                        <path d="M8 5.6v12.8c0 .8.9 1.3 1.6.8l9.1-6.4a1 1 0 0 0 0-1.6L9.6 4.8A1 1 0 0 0 8 5.6Z" />
                    </svg>
                </div>
                <h3 className="text-lg font-bold text-gray-800">Konten {label}</h3>
                <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
                    Player baru dimuat setelah tombol Jalankan diklik agar halaman lebih ringan.
                    Progres terakhir tetap disimpan ketika player ditutup.
                </p>
                <button
                    type="button"
                    onClick={() => setIsOpen(true)}
                    disabled={!contentPath || isFinishing}
                    className="mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-7 py-3 font-bold text-white shadow-md transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:shadow-none"
                >
                    Jalankan {label}
                </button>
                {!contentPath && (
                    <p className="mt-3 text-xs font-medium text-red-500">
                        Paket {label} belum tersedia.
                    </p>
                )}
            </div>

            {isOpen && typeof document !== "undefined" && createPortal(
                <div
                    className="fixed inset-0 z-[100] flex items-center justify-center overflow-hidden bg-slate-950/75 p-2 backdrop-blur-sm sm:p-5"
                    role="dialog"
                    aria-modal="true"
                    aria-label={"Player " + label}
                >
                    <div className="flex h-full max-h-[900px] w-full max-w-[1440px] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                        <div className="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3 sm:px-6">
                            <div className="min-w-0">
                                <p className="text-xs font-bold uppercase tracking-wider text-primary">
                                    {label}
                                </p>
                                <h2 className="truncate text-base font-bold text-gray-800 sm:text-lg">
                                    {activity.title}
                                </h2>
                            </div>
                            <button
                                type="button"
                                onClick={() => setIsOpen(false)}
                                disabled={isFinishing}
                                className="ml-4 flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-2xl text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 disabled:opacity-50"
                                aria-label="Tutup player"
                            >
                                ×
                            </button>
                        </div>

                        <div className={bodyClass}>
                            <Activity
                                question_result={questionResult}
                                slug={courseSlug}
                                id={activityId}
                                type={activity.type}
                                data={activity.data}
                                status={activity.status}
                                answers={answers}
                                file={file}
                                setFile={setFile}
                                setAnswers={setAnswers}
                                schedule={schedule}
                                onVideoCompleted={() => setIsContentCompleted(true)}
                            />
                        </div>

                        <div className="flex shrink-0 flex-col gap-3 border-t border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <p className={`text-xs font-medium ${
                                isContentCompleted ? "text-green-600" : "text-gray-500"
                            }`}>
                                {isContentCompleted
                                    ? `Konten ${label} telah selesai.`
                                    : `Selesaikan konten ${label} agar tombol Selesai muncul.`}
                            </p>
                            <div className="flex flex-col-reverse gap-2 sm:flex-row">
                                <button
                                    type="button"
                                    onClick={() => setIsOpen(false)}
                                    disabled={isFinishing}
                                    className="rounded-xl border border-gray-300 px-6 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                                >
                                    Tutup
                                </button>
                                {isContentCompleted && (
                                    <button
                                        type="button"
                                        onClick={handleFinish}
                                        disabled={isFinishing}
                                        className="rounded-xl bg-primary px-7 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-wait disabled:opacity-60"
                                    >
                                        {isFinishing ? "Menyimpan..." : "Selesai"}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>,
                document.body
            )}
        </>
    );
};

export default InteractiveContentLauncher;
