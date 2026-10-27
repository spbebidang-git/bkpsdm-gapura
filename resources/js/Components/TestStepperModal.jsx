import { useEffect, useMemo, useState } from "react";
import ActivityModal from "@/Components/ActivityModal";

const isAnswered = (answers, index) => answers?.[index]?.option_select !== undefined;

/**
 * Versi modal untuk pre-test dan post-test: satu soal per langkah, dengan peta
 * nomor soal di sebelah kanan yang menandai soal terjawab dengan warna hijau.
 *
 * Pilihan jawabannya terkendali (`checked` diturunkan dari `answers`), karena
 * berpindah langkah melepas soal sebelumnya dari DOM - radio tak terkendali
 * akan kehilangan tanda pilihannya begitu peserta kembali ke soal itu.
 *
 * `questions` harus urutan yang sama dengan yang dipakai panel: `answers`
 * dikunci oleh indeks, jadi dua urutan berbeda akan menautkan jawaban peserta
 * ke soal yang salah.
 */
const TestStepperModal = ({
    isOpen,
    onClose,
    eyebrow,
    title,
    questions = [],
    answers = {},
    setAnswers,
    onSubmit,
}) => {
    const [current, setCurrent] = useState(0);

    // Peserta bisa mengulang tes; soalnya diacak ulang dan langkahnya kembali
    // ke awal supaya tidak menunjuk soal yang sudah tidak ada.
    useEffect(() => {
        setCurrent(0);
    }, [questions]);

    const total = questions.length;
    const answeredCount = useMemo(
        () => questions.reduce((count, _, index) => (isAnswered(answers, index) ? count + 1 : count), 0),
        [answers, questions]
    );

    const item = questions[current];

    const selectOption = (value) => {
        setAnswers((prev) => ({
            ...prev,
            [current]: {
                question: item.question,
                option_select: value,
                question_id: item.question_id,
            },
        }));
    };

    const footer = (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-center text-xs font-medium text-gray-500 sm:text-left">
                Soal {total === 0 ? 0 : current + 1} dari {total} - {answeredCount} terjawab
            </p>
            <div className="flex flex-col-reverse gap-2 sm:flex-row">
                <button
                    type="button"
                    onClick={() => setCurrent((step) => Math.max(step - 1, 0))}
                    disabled={current === 0}
                    className="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Sebelumnya
                </button>
                <button
                    type="button"
                    onClick={() => setCurrent((step) => Math.min(step + 1, total - 1))}
                    disabled={current >= total - 1}
                    className="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Berikutnya
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
        </div>
    );

    return (
        <ActivityModal
            isOpen={isOpen}
            onClose={onClose}
            eyebrow={eyebrow}
            title={title}
            widthClass="max-w-[1240px]"
            footer={footer}
        >
            {/* Tinggi dikunci hanya pada layar lebar, tempat kedua kolom
                bergulir sendiri-sendiri. Di layar sempit keduanya menumpuk dan
                ikut gulir badan modal. */}
            <div className="flex flex-col gap-4 lg:h-full lg:flex-row">
                <div className="min-w-0 flex-1 rounded-xl border border-gray-100 bg-white p-5 sm:p-7 lg:min-h-0 lg:overflow-y-auto">
                    {item ? (
                        <>
                            <h3 className="mb-5 text-lg font-medium leading-relaxed text-gray-800">
                                {current + 1 + '. ' + item.question}
                            </h3>
                            <ul className="grid w-full gap-4 md:grid-cols-1">
                                {Object.entries(item.option).map(([key, value]) => (
                                    <li key={key}>
                                        <input
                                            type="radio"
                                            id={`step-option-${current}-${key}`}
                                            name={`step-option-${current}`}
                                            value={key}
                                            className="hidden peer"
                                            checked={answers?.[current]?.option_select === value}
                                            onChange={() => selectOption(value)}
                                        />
                                        <label
                                            htmlFor={`step-option-${current}-${key}`}
                                            className="flex text-sm xl:text-base gap-4 p-3 xl:p-4 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100 transition-all"
                                        >
                                            <div className="block">
                                                <div className="w-full">{value}</div>
                                            </div>
                                        </label>
                                    </li>
                                ))}
                            </ul>
                        </>
                    ) : (
                        <p className="py-10 text-center text-sm text-gray-500">
                            Belum ada soal pada tes ini.
                        </p>
                    )}
                </div>

                <div className="shrink-0 rounded-xl border border-gray-100 bg-white p-4 lg:flex lg:w-72 lg:min-h-0 lg:flex-col">
                    <p className="mb-1 shrink-0 text-sm font-bold text-gray-800">Nomor Soal</p>
                    <p className="mb-4 shrink-0 text-xs text-gray-500">
                        Kotak hijau menandai soal yang sudah dijawab.
                    </p>
                    {/* Judulnya tetap di tempat; daftar nomornya yang bergulir,
                        karena satu tes bisa berisi puluhan soal. */}
                    {/* p-1: wadah gulir memotong apa pun di luar kotak isinya,
                        termasuk cincin penanda soal yang sedang dibuka. */}
                    <div className="grid grid-cols-6 content-start gap-2 lg:min-h-0 lg:flex-1 lg:grid-cols-5 lg:overflow-y-auto lg:p-1">
                        {questions.map((_, index) => {
                            const answered = isAnswered(answers, index);
                            const active = index === current;

                            return (
                                <button
                                    key={index}
                                    type="button"
                                    onClick={() => setCurrent(index)}
                                    aria-current={active ? "step" : undefined}
                                    aria-label={`Soal ${index + 1}, ${answered ? 'sudah dijawab' : 'belum dijawab'}`}
                                    className={`flex h-10 w-full items-center justify-center rounded-lg border-2 text-sm font-bold transition ${
                                        answered
                                            ? 'border-green-500 bg-green-500 text-white hover:bg-green-600'
                                            : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300'
                                    } ${active ? 'ring-2 ring-primary ring-offset-1' : ''}`}
                                >
                                    {index + 1}
                                </button>
                            );
                        })}
                    </div>
                </div>
            </div>
        </ActivityModal>
    );
};

export default TestStepperModal;
