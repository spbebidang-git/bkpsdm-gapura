/**
 * Penyusun daftar pertanyaan kuesioner untuk instruktur.
 *
 * Dipakai bersama oleh halaman Tambah & Edit Kuesioner. Nilai dikirim ke
 * induknya sebagai array objek siap-simpan:
 * { id?, type, content, is_required, length, choices: [string] }
 */

const TYPE_OPTIONS = [
    { value: "radio", label: "Pilihan ganda (satu jawaban)" },
    { value: "check", label: "Pilihan jamak (boleh banyak)" },
    { value: "drop", label: "Dropdown" },
    { value: "yesno", label: "Ya/Tidak" },
    { value: "rate", label: "Skala penilaian" },
    { value: "text", label: "Isian singkat" },
    { value: "essay", label: "Esai" },
    { value: "numeric", label: "Angka" },
    { value: "date", label: "Tanggal" },
    { value: "section", label: "Teks bagian (bukan pertanyaan)" },
];

const CHOICE_TYPES = ["radio", "check", "drop", "rate"];
const LENGTH_TYPES = ["text", "numeric", "rate"];

const LENGTH_HINT = {
    text: "Batas jumlah karakter (0 = bawaan 255).",
    numeric: "Batas jumlah digit (0 = tanpa batas).",
    rate: "Jumlah tingkat skala (0 = 5 tingkat).",
};

const blankQuestion = () => ({
    type: "radio",
    content: "",
    is_required: true,
    length: 0,
    choices: [],
});

const QuestionnaireBuilder = ({ questions = [], onChange, error = null }) => {
    const update = (index, patch) => {
        onChange(questions.map((row, i) => (i === index ? { ...row, ...patch } : row)));
    };

    const add = () => onChange([...questions, blankQuestion()]);

    const remove = (index) => onChange(questions.filter((_, i) => i !== index));

    const move = (index, offset) => {
        const target = index + offset;
        if (target < 0 || target >= questions.length) return;

        const next = [...questions];
        [next[index], next[target]] = [next[target], next[index]];
        onChange(next);
    };

    let number = 0;

    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <div>
                    <label className="form-label mb-0">
                        Daftar Pertanyaan <span className="text-red-500">*</span>
                    </label>
                    <p className="text-xs text-gray-500">
                        {questions.length} baris — teks bagian tidak ikut dinomori.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={add}
                    className="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary/90"
                >
                    <box-icon name="plus" color="white" size="16px"></box-icon>
                    Tambah Pertanyaan
                </button>
            </div>

            {error && <p className="mb-3 text-sm font-medium text-red-500">{error}</p>}

            {questions.length === 0 && (
                <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                    Belum ada pertanyaan. Klik “Tambah Pertanyaan” untuk mulai menyusun.
                </div>
            )}

            <div className="grid gap-3">
                {questions.map((question, index) => {
                    const isSection = question.type === "section";
                    if (!isSection) number += 1;

                    return (
                        <div
                            key={index}
                            className="rounded-xl border border-gray-200 bg-gray-50/60 p-4"
                        >
                            <div className="mb-3 flex items-center justify-between gap-2">
                                <span className="text-sm font-bold text-gray-500">
                                    {isSection ? "Teks bagian" : `Pertanyaan ${number}`}
                                </span>
                                <div className="flex items-center gap-1">
                                    <button
                                        type="button"
                                        onClick={() => move(index, -1)}
                                        disabled={index === 0}
                                        title="Naikkan"
                                        className="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-semibold text-gray-600 disabled:opacity-40"
                                    >
                                        ↑
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => move(index, 1)}
                                        disabled={index === questions.length - 1}
                                        title="Turunkan"
                                        className="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-semibold text-gray-600 disabled:opacity-40"
                                    >
                                        ↓
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => remove(index)}
                                        className="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-100"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-[1fr_250px]">
                                <div>
                                    <label className="form-label">
                                        {isSection ? "Teks" : "Pertanyaan"}
                                    </label>
                                    <textarea
                                        className="form-control"
                                        rows={2}
                                        value={question.content}
                                        placeholder={
                                            isSection
                                                ? "Contoh: Bagian B — Penilaian Materi"
                                                : "Tulis pertanyaannya"
                                        }
                                        onChange={(e) => update(index, { content: e.target.value })}
                                    />
                                </div>

                                <div>
                                    <label className="form-label">Tipe</label>
                                    <select
                                        className="form-control"
                                        value={question.type}
                                        onChange={(e) => update(index, { type: e.target.value })}
                                    >
                                        {TYPE_OPTIONS.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>

                                    {!isSection && (
                                        <label className="mt-2 flex items-center gap-2 text-sm text-gray-600">
                                            <input
                                                type="checkbox"
                                                className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                checked={!!question.is_required}
                                                onChange={(e) =>
                                                    update(index, { is_required: e.target.checked })
                                                }
                                            />
                                            Wajib dijawab
                                        </label>
                                    )}
                                </div>
                            </div>

                            {CHOICE_TYPES.includes(question.type) && (
                                <div className="mt-3">
                                    <label className="form-label">
                                        {question.type === "rate"
                                            ? "Pernyataan yang dinilai (satu per baris)"
                                            : "Pilihan jawaban (satu per baris)"}
                                    </label>
                                    <textarea
                                        className="form-control"
                                        rows={Math.min(Math.max((question.choices ?? []).length + 1, 3), 10)}
                                        value={(question.choices ?? []).join("\n")}
                                        placeholder={"Pilihan pertama\nPilihan kedua"}
                                        onChange={(e) =>
                                            update(index, { choices: e.target.value.split("\n") })
                                        }
                                    />
                                </div>
                            )}

                            {LENGTH_TYPES.includes(question.type) && (
                                <div className="mt-3 max-w-xs">
                                    <label className="form-label">Batas</label>
                                    <input
                                        type="number"
                                        min="0"
                                        className="form-control"
                                        value={question.length ?? 0}
                                        onChange={(e) =>
                                            update(index, { length: Number(e.target.value) || 0 })
                                        }
                                    />
                                    <small className="mt-1 block text-xs text-gray-500">
                                        {LENGTH_HINT[question.type]}
                                    </small>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {questions.length > 0 && (
                <button
                    type="button"
                    onClick={add}
                    className="mt-3 w-full rounded-xl border border-dashed border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                >
                    + Tambah Pertanyaan
                </button>
            )}
        </div>
    );
};

export default QuestionnaireBuilder;
