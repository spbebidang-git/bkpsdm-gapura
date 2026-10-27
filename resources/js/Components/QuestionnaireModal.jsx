import ActivityModal from "@/Components/ActivityModal";

/**
 * Jendela besar untuk mengisi kuesioner. Formulir yang panjang sempit dibaca
 * di panel materi, jadi peserta bisa memperbesarnya tanpa kehilangan isian:
 * state `answers` tetap dipegang halaman, formulirnya hanya berpindah tempat
 * render.
 */
const QuestionnaireModal = ({ isOpen, onClose, title, onSubmit, children }) => (
    <ActivityModal
        isOpen={isOpen}
        onClose={onClose}
        eyebrow="Kuesioner"
        title={title}
        footer={
            <div className="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end">
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded-xl border border-gray-300 px-6 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50"
                >
                    Tutup
                </button>
                <button
                    type="button"
                    onClick={onSubmit}
                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-7 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary/90"
                >
                    Kirim Jawaban
                    <box-icon name="send" color="white" size="18px"></box-icon>
                </button>
            </div>
        }
    >
        {children}
    </ActivityModal>
);

export default QuestionnaireModal;
