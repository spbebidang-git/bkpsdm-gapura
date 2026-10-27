import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import QuestionnaireBuilder from "@/Components/QuestionnaireBuilder";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

const Create = ({ slug }) => {
    const { errors } = usePage().props;

    const [values, setValues] = useState({
        title: "",
        sub_title: "",
        value: "",
        instruction: "",
        intro: "",
        is_anonymous: false,
        duration: 0,
    });
    const [questions, setQuestions] = useState([]);

    function handleChange(e) {
        const { id, type, value, checked } = e.target;
        setValues((values) => ({
            ...values,
            [id]: type === "checkbox" ? checked : value,
        }));
    }

    function handleSubmit(e) {
        e.preventDefault();

        router.post(`/instructor/course/${slug}/activity/questionnaire`, {
            ...values,
            questions: JSON.stringify(questions),
        });
    }

    return (
        <App>
            <ActivityFormLayout
                slug={slug}
                type="questionnaire"
                title="Tambah Kuesioner"
                description="Pertanyaan diisi peserta langsung di aplikasi; jawabannya terekap otomatis."
            />

            <form onSubmit={handleSubmit}>
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">
                        <FormInput
                            title={"title"}
                            label={"Judul Kuesioner"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            placeholder={"Contoh: Evaluasi Penyelenggaraan"}
                        />

                        <FormInput
                            title={"sub_title"}
                            label={"Sub Judul"}
                            onChange={handleChange}
                            value={values.sub_title}
                            error={errors.sub_title}
                            placeholder={"Opsional"}
                        />

                        <FormInput
                            title={"value"}
                            label={"Bobot Nilai"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                            type="number"
                            small={"Kuesioner tidak dinilai, tetapi bobot tetap dipakai pada rekap kegiatan."}
                        />

                        <div className="h-auto mb-3">
                            <FormLabel title={"Instruksi"} />
                            <ReactQuill
                                value={values.instruction}
                                onChange={(value) =>
                                    setValues((prev) => ({ ...prev, instruction: value }))
                                }
                            />
                            <small className="text-gray-500 text-xs mt-1 block">
                                Ditampilkan di halaman pengantar sebelum peserta membuka kuesioner.
                            </small>
                        </div>

                        <div className="h-auto mb-3">
                            <FormLabel title={"Pengantar di Atas Pertanyaan"} />
                            <ReactQuill
                                value={values.intro}
                                onChange={(value) =>
                                    setValues((prev) => ({ ...prev, intro: value }))
                                }
                            />
                        </div>

                        <label className="mb-5 flex items-start gap-2 text-sm text-gray-600">
                            <input
                                id="is_anonymous"
                                type="checkbox"
                                className="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                checked={values.is_anonymous}
                                onChange={handleChange}
                            />
                            <span>
                                Kuesioner anonim
                                <small className="block text-xs text-gray-500">
                                    Nama dan NIP responden tidak ditampilkan pada rekap maupun ekspor.
                                </small>
                            </span>
                        </label>

                        <QuestionnaireBuilder
                            questions={questions}
                            onChange={setQuestions}
                            error={errors.questions}
                        />
                    </div>
                </div>

                <div className="max-w-3xl mx-auto flex flex-col sm:flex-row justify-end gap-3 mt-8">
                    <Link href={`/instructor/course/${slug}/activity`} className="w-full sm:w-auto">
                        <button type="button" className="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-semibold transition-colors">
                            Kembali
                        </button>
                    </Link>
                    <button type="submit" className="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-primary text-white hover:bg-primary/90 font-semibold shadow-sm transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </App>
    );
};

export default Create;
