import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const Edit = ({ slug, activityId, questionId, question }) => {
    const { errors } = usePage().props;

    const [description, setDescription] = useState(question.description);
    const [options, setOptions] = useState({
        option1: question.option1,
        option2: question.option2,
        option3: question.option3,
        option4: question.option4,
    });
    const [correct_answer, setCorrect_answer] = useState(question.is_correct);
    const [score, setScore] = useState(question.score);

    const handleOptionChange = (e) => {
        const { id, value } = e.target;
        setOptions({
            ...options,
            [id]: value,
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        const formData = {
            description,
            options,
            correct_answer,
            score,
        };

        router.put(
            `/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/${question.id}`,
            formData
        );
    };

    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">
                Edit Soal
            </div>

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">

                        {/* DESKRIPSI SOAL */}
                        <div className="mb-4">
                            <label htmlFor="description" className="form-label">
                                Deskripsi Soal <span className="text-red-500">*</span>
                            </label>

                            <textarea
                                id="description"
                                className={`form-control h-24 p-3 ${errors?.description ? 'error' : ''}`}
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Tuliskan isi atau pertanyaan dari soal ini"
                            />

                            <small className="text-gray-500 text-xs mt-1 block">
                                Berisi kalimat pertanyaan atau konteks soal. Tidak boleh kosong.
                            </small>

                            {errors?.description && (
                                <small className="text-red-500 text-xs mt-1 block">
                                    {errors.description}
                                </small>
                            )}
                        </div>

                        {/* KETERANGAN PENGISIAN OPSI */}
                        <div className="mb-4 text-sm text-gray-600 leading-relaxed">
                            Isi empat opsi jawaban pada kolom yang tersedia.
                            Pastikan tiap opsi berisi teks yang jelas.
                            Pilih salah satu sebagai{" "}
                            <span className="font-semibold text-primary">jawaban benar</span>
                            dengan menekan tombol radio.
                            Anda hanya dapat memilih <strong>satu</strong> jawaban benar.
                        </div>

                        {/* HEADER */}
                        <div className="grid grid-cols-4 items-center gap-5 py-3 font-semibold text-primary">
                            <div className="col-span-3">Opsi Jawaban</div>
                            <div className="text-center">Jawaban Benar</div>
                        </div>

                        {/* INPUT OPSI JAWABAN */}
                        {['option1', 'option2', 'option3', 'option4'].map((option, index) => (
                            <div
                                className="grid grid-cols-4 items-center justify-center mb-5"
                                key={option}
                            >
                                {/* Input teks */}
                                <div className="col-span-3">
                                    <input
                                        type="text"
                                        id={option}
                                        className={`form-control ${errors?.[`options.${option}`] || errors?.[option] ? 'error' : ''}`}
                                        value={options[option]}
                                        onChange={handleOptionChange}
                                        placeholder={`Opsi ${index + 1}`}
                                    />

                                    {(errors?.[`options.${option}`] || errors?.[option]) && (
                                        <small className="text-red-500 text-xs mt-1 block">
                                            {errors[`options.${option}`] || errors[option]}
                                        </small>
                                    )}
                                </div>

                                {/* Radio */}
                                <div className="flex justify-center">
                                    <input
                                        type="radio"
                                        name="correct_answer"
                                        value={options[option]}
                                        checked={correct_answer === options[option]}
                                        onChange={() => setCorrect_answer(options[option])}
                                        className="form-radio"
                                    />
                                </div>
                            </div>
                        ))}

                        {/* SKOR */}
                        <div className="mt-6">
                            <label htmlFor="score" className="form-label">
                                Skor Soal <span className="text-red-500">*</span>
                            </label>

                            <input
                                type="number"
                                id="score"
                                className={`form-control w-20 text-center ${errors?.score ? "error" : ""}`}
                                value={score}
                                onChange={(e) => setScore(e.target.value)}
                                placeholder="0"
                            />

                            <small className="text-gray-500 text-xs mt-1 block">
                                Masukkan skor untuk soal ini.
                                Hanya angka diperbolehkan, dan nilai harus lebih dari <strong>0</strong>.
                            </small>

                            {errors?.score && (
                                <small className="text-red-500 text-xs mt-1 block">
                                    {errors.score}
                                </small>
                            )}
                        </div>

                    </div>
                </div>

                {/* BUTTON */}
                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Simpan</button>

                    <Link
                        className="w-full"
                        href={route(
                            "instructor.course.activity.variant.question-item.index",
                            [slug, activityId, questionId]
                        )}
                    >
                        <button type="button" className="button-secondary">Kembali</button>
                    </Link>
                </div>
            </form>
        </App>
    );
};

export default Edit;
