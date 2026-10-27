import { useCallback, useEffect } from "react";
import { createPortal } from "react-dom";

/**
 * Cangkang modal untuk memperbesar materi yang sedang dikerjakan peserta.
 * Isi badan dan tombol kakinya ditentukan pemanggil.
 *
 * Pemanggil wajib memastikan hanya satu salinan formulirnya yang ter-mount.
 * Pilihan jawaban dikelompokkan lewat atribut `name` per soal, sehingga dua
 * salinan yang hidup bersamaan akan saling menghapus pilihan.
 */
const ActivityModal = ({
    isOpen,
    onClose,
    eyebrow,
    title,
    // Formulir panjang lebih enak dibaca pada lebar sedang; pemutar materi
    // memakai lebar penuh lewat prop ini.
    widthClass = "max-w-[1100px]",
    children,
    footer,
}) => {
    const handleClose = useCallback(() => onClose?.(), [onClose]);

    useEffect(() => {
        if (!isOpen) return;

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";

        const onKeyDown = (event) => {
            if (event.key === "Escape") handleClose();
        };

        window.addEventListener("keydown", onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener("keydown", onKeyDown);
        };
    }, [handleClose, isOpen]);

    if (!isOpen || typeof document === "undefined") return null;

    return createPortal(
        <div
            className="fixed inset-0 z-[100] flex items-center justify-center overflow-hidden bg-slate-950/75 p-2 backdrop-blur-sm sm:p-5"
            role="dialog"
            aria-modal="true"
            aria-label={title}
        >
            <div className={`flex h-full max-h-[900px] w-full ${widthClass} flex-col overflow-hidden rounded-2xl bg-white shadow-2xl`}>
                <div className="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3 sm:px-6">
                    <div className="min-w-0">
                        <p className="text-xs font-bold uppercase tracking-wider text-primary">
                            {eyebrow}
                        </p>
                        <h2 className="truncate text-base font-bold text-gray-800 sm:text-lg">
                            {title}
                        </h2>
                    </div>
                    <button
                        type="button"
                        onClick={handleClose}
                        className="ml-4 flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-2xl text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
                        aria-label="Tutup"
                    >
                        ×
                    </button>
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto bg-gray-100 p-3 sm:p-5">
                    {children}
                </div>

                {footer && (
                    <div className="shrink-0 border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                        {footer}
                    </div>
                )}
            </div>
        </div>,
        document.body
    );
};

export default ActivityModal;
