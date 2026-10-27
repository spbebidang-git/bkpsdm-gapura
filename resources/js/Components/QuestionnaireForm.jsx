import { useEffect } from "react";

/**
 * Form kuesioner untuk peserta. Tipe pertanyaannya mengikuti mod_questionnaire
 * Moodle sehingga kuesioner hasil impor .mbz tampil seperti aslinya.
 *
 * Bentuk state `answers`: { [questionId]: { value } | { choice_id } |
 * { choice_ids: [] } | { rates: { [choiceId]: nilai } } } - sama dengan yang
 * diharapkan QuestionnaireService di sisi server.
 */
const DEFAULT_RATE_SCALE = 5;

const normalizeSaved = (saved = {}) => {
    const next = {};

    Object.entries(saved).forEach(([questionId, answer]) => {
        if (!answer) return;

        if (Array.isArray(answer.choice_ids)) {
            next[questionId] = { choice_ids: answer.choice_ids.map(Number) };
            return;
        }

        if (answer.rates) {
            next[questionId] = {
                rates: Object.fromEntries(
                    Object.entries(answer.rates).map(([choiceId, value]) => [
                        Number(choiceId),
                        Number(value),
                    ])
                ),
            };
            return;
        }

        if (answer.choice_id) {
            next[questionId] = { choice_id: Number(answer.choice_id) };
            return;
        }

        next[questionId] = { value: answer.value ?? "" };
    });

    return next;
};

const QuestionnaireForm = ({
    questions = [],
    intro = null,
    isAnonymous = false,
    isAnswered = false,
    savedAnswers = {},
    answers = {},
    setAnswers,
    errors = {},
}) => {
    // Jawaban yang pernah dikirim dimuat ulang supaya peserta bisa
    // memperbaikinya, bukan mulai dari nol.
    useEffect(() => {
        const prefill = normalizeSaved(savedAnswers);

        if (Object.keys(prefill).length === 0) return;

        setAnswers((prev) => (Object.keys(prev).length > 0 ? prev : prefill));
    }, [savedAnswers, setAnswers]);

    const patchAnswer = (questionId, patch) => {
        setAnswers((prev) => ({ ...prev, [questionId]: patch }));
    };

    const toggleChoice = (questionId, choiceId) => {
        setAnswers((prev) => {
            const current = prev[questionId]?.choice_ids ?? [];
            const exists = current.includes(choiceId);

            return {
                ...prev,
                [questionId]: {
                    choice_ids: exists
                        ? current.filter((id) => id !== choiceId)
                        : [...current, choiceId],
                },
            };
        });
    };

    if (questions.length === 0) {
        return (
            <div className="flex h-full items-center justify-center p-8">
                <p className="text-center text-gray-500">
                    Kuesioner ini belum punya pertanyaan. Hubungi instruktur Anda.
                </p>
            </div>
        );
    }

    let number = 0;

    return (
        <div className="mx-auto w-full max-w-3xl p-2 sm:p-4">
            {intro && (
                <div
                    className="mb-5 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm leading-relaxed text-gray-600"
                    dangerouslySetInnerHTML={{ __html: intro }}
                />
            )}

            <div className="mb-5 flex flex-wrap gap-2">
                {isAnonymous && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                        Jawaban Anda anonim
                    </span>
                )}
                {isAnswered && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                        Sudah pernah diisi - boleh diperbarui
                    </span>
                )}
            </div>

            {questions.map((question) => {
                const error = errors[`answers.${question.id}`];
                const answer = answers[question.id] ?? {};

                if (question.type === "section") {
                    return (
                        <div
                            key={question.id}
                            className="mb-4 border-l-4 border-primary bg-primary/5 px-4 py-3 text-sm font-semibold text-gray-700"
                            dangerouslySetInnerHTML={{ __html: question.content }}
                        />
                    );
                }

                number += 1;

                return (
                    <div
                        key={question.id}
                        className={`mb-4 rounded-xl border bg-white p-4 shadow-sm ${error ? "border-red-300" : "border-gray-200"
                            }`}
                    >
                        <div className="mb-3 flex gap-2">
                            <span className="font-semibold text-gray-500">{number}.</span>
                            <div className="flex-1">
                                <div
                                    className="text-gray-800"
                                    dangerouslySetInnerHTML={{ __html: question.content }}
                                />
                                {question.is_required && (
                                    <span className="text-xs font-semibold text-red-500">
                                        Wajib dijawab
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Ya/Tidak */}
                        {question.type === "yesno" && (
                            <div className="flex gap-3">
                                {[
                                    { value: "y", label: "Ya" },
                                    { value: "n", label: "Tidak" },
                                ].map((option) => (
                                    <label
                                        key={option.value}
                                        className={`flex-1 cursor-pointer rounded-xl border px-4 py-2.5 text-center text-sm font-semibold transition-all ${answer.value === option.value
                                            ? "border-primary bg-primary text-white"
                                            : "border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                                            }`}
                                    >
                                        <input
                                            type="radio"
                                            className="hidden"
                                            name={`question-${question.id}`}
                                            checked={answer.value === option.value}
                                            onChange={() =>
                                                patchAnswer(question.id, { value: option.value })
                                            }
                                        />
                                        {option.label}
                                    </label>
                                ))}
                            </div>
                        )}

                        {/* Pilihan ganda */}
                        {question.type === "radio" && (
                            <ul className="grid gap-2">
                                {question.choices.map((choice) => (
                                    <li key={choice.id}>
                                        <label
                                            className={`flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-2.5 text-sm transition-all ${answer.choice_id === choice.id
                                                ? "border-primary bg-primary text-white"
                                                : "border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                                                }`}
                                        >
                                            <input
                                                type="radio"
                                                className="hidden"
                                                name={`question-${question.id}`}
                                                checked={answer.choice_id === choice.id}
                                                onChange={() =>
                                                    patchAnswer(question.id, { choice_id: choice.id })
                                                }
                                            />
                                            {choice.content}
                                        </label>
                                    </li>
                                ))}
                            </ul>
                        )}

                        {/* Pilihan jamak */}
                        {question.type === "check" && (
                            <ul className="grid gap-2">
                                {question.choices.map((choice) => {
                                    const checked = (answer.choice_ids ?? []).includes(choice.id);

                                    return (
                                        <li key={choice.id}>
                                            <label
                                                className={`flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-2.5 text-sm transition-all ${checked
                                                    ? "border-primary bg-primary/10 text-gray-800"
                                                    : "border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                                                    }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                    checked={checked}
                                                    onChange={() => toggleChoice(question.id, choice.id)}
                                                />
                                                {choice.content}
                                            </label>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}

                        {/* Dropdown */}
                        {question.type === "drop" && (
                            <select
                                className="form-control"
                                value={answer.choice_id ?? ""}
                                onChange={(e) =>
                                    patchAnswer(question.id, {
                                        choice_id: e.target.value ? Number(e.target.value) : null,
                                    })
                                }
                            >
                                <option value="">Pilih salah satu</option>
                                {question.choices.map((choice) => (
                                    <option key={choice.id} value={choice.id}>
                                        {choice.content}
                                    </option>
                                ))}
                            </select>
                        )}

                        {/* Skala penilaian: satu baris per pernyataan */}
                        {question.type === "rate" && (
                            <div className="grid gap-3">
                                {question.choices.map((choice) => {
                                    const scale = question.length > 0 ? question.length : DEFAULT_RATE_SCALE;
                                    const selected = answer.rates?.[choice.id];

                                    return (
                                        <div
                                            key={choice.id}
                                            className="flex flex-col gap-2 border-b border-gray-100 pb-2 last:border-0 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <span className="text-sm text-gray-600">{choice.content}</span>
                                            <div className="flex gap-1.5">
                                                {Array.from({ length: scale }, (_, i) => i + 1).map((step) => (
                                                    <button
                                                        key={step}
                                                        type="button"
                                                        onClick={() =>
                                                            patchAnswer(question.id, {
                                                                rates: { ...(answer.rates ?? {}), [choice.id]: step },
                                                            })
                                                        }
                                                        className={`h-9 w-9 rounded-lg border text-sm font-semibold transition-all ${selected === step
                                                            ? "border-primary bg-primary text-white"
                                                            : "border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
                                                            }`}
                                                    >
                                                        {step}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        {/* Isian singkat */}
                        {question.type === "text" && (
                            <input
                                type="text"
                                className="form-control"
                                maxLength={question.length > 0 ? question.length : 255}
                                placeholder="Tulis jawaban Anda"
                                value={answer.value ?? ""}
                                onChange={(e) => patchAnswer(question.id, { value: e.target.value })}
                            />
                        )}

                        {/* Esai */}
                        {question.type === "essay" && (
                            <textarea
                                className="form-control"
                                rows={5}
                                placeholder="Tulis jawaban Anda"
                                value={answer.value ?? ""}
                                onChange={(e) => patchAnswer(question.id, { value: e.target.value })}
                            />
                        )}

                        {/* Angka */}
                        {question.type === "numeric" && (
                            <input
                                type="number"
                                className="form-control"
                                placeholder="Masukkan angka"
                                value={answer.value ?? ""}
                                onChange={(e) => patchAnswer(question.id, { value: e.target.value })}
                            />
                        )}

                        {/* Tanggal */}
                        {question.type === "date" && (
                            <input
                                type="date"
                                className="form-control"
                                value={answer.value ?? ""}
                                onChange={(e) => patchAnswer(question.id, { value: e.target.value })}
                            />
                        )}

                        {error && (
                            <p className="mt-2 text-sm font-medium text-red-500">{error}</p>
                        )}

                        {question.type === "text" && question.length > 0 && (
                            <p className="mt-1 text-xs text-gray-400">
                                Maksimal {question.length} karakter.
                            </p>
                        )}

                        {question.type === "numeric" && question.length > 0 && (
                            <p className="mt-1 text-xs text-gray-400">
                                Maksimal {question.length} digit.
                            </p>
                        )}
                    </div>
                );
            })}

            <p className="mt-4 text-center text-xs text-gray-400">
                Tekan “Kirim Jawaban” setelah semua pertanyaan wajib terisi.
            </p>
        </div>
    );
};

export default QuestionnaireForm;
