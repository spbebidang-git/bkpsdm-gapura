import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

const Create = ({ slug }) => {
    const { errors } = usePage().props;

    const [values, setValues] = useState({
        title: "",
        sub_title: "",
        value: "",
        instruction: "",
        duration: "",
        content: null,
    });

    function handleChange(e) {
        const { id, type, value, checked } = e.target;

        setValues((values) => ({
            ...values,
            [id]: type === "checkbox" ? checked : value,
        }));
    }

    function handleFileChange(e) {
        const file = e.target.files[0];
        if (!file) return;
        setValues((prev) => ({ ...prev, content: file }));
    }

    function handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append("title", values.title);
        formData.append("sub_title", values.subtitle);
        formData.append("value", values.value);
        formData.append("duration", values.duration);
        formData.append("instruction", values.instruction);
        formData.append("content", values.content);

        router.post(`/instructor/course/${slug}/activity/literature`, formData);
    }

    return (
        <App>
            <ActivityFormLayout
                slug={slug}
                type="literature"
                title="Literatur"
                description="Upload file PDF literatur yang dapat diakses dan dibaca oleh peserta."
            />

            <form onSubmit={handleSubmit} encType="multipart/form-data">
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">

                        {/* JUDUL */}
                        <FormInput
                            title="title"
                            label="Judul"
                            placeholder="Masukkan judul materi"
                            small="Judul harus singkat dan jelas, misalnya: 'Modul Dasar Manajemen Risiko'."
                            isRequired
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                        />

                        {/* SUBTITLE */}
                        <FormInput
                            title="sub_title"
                            label="Sub Judul"
                            placeholder="Masukkan sub judul materi"
                            small="Sub judul digunakan untuk menjelaskan detail tambahan dari materi."
                            isRequired
                            onChange={handleChange}
                            value={values.sub_title}
                            error={errors.sub_title}
                        />

                        {/* NILAI */}
                        <FormInput
                            title="value"
                            label="Bobot Nilai"
                            placeholder="Contoh: 10, 20, 100"
                            small="Bobot nilai digunakan jika literatur ini menjadi bagian penilaian."
                            isRequired
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                        />

                        {/* INSTRUKSI */}
                        <div className="h-auto mb-3">
                            <FormLabel title="Instruksi Tambahan" />
                            <p className="text-xs text-gray-500 mb-1">
                                Berikan penjelasan tambahan, catatan penting, atau instruksi terkait literatur ini.
                            </p>

                            <ReactQuill
                                value={values.instruction}
                                onChange={(value) =>
                                    setValues((prev) => ({ ...prev, instruction: value }))
                                }
                            />

                            {errors.instruction && (
                                <small className="text-red-500">{errors.instruction}</small>
                            )}
                        </div>

                        {/* DURASI */}
                        <FormInput
                            title="duration"
                            label="Durasi Membaca (Detik)"
                            placeholder="Waktu dalam detik (contoh: 300 = 5 menit)"
                            small="Durasi diperlukan untuk estimasi waktu belajar peserta."
                            isRequired
                            onChange={handleChange}
                            value={values.duration}
                            error={errors.duration}
                        />

                        {/* FILE PDF */}
                        <div className="mb-3">
                            <FormLabel title="File PDF" />
                            <p className="text-xs text-gray-500 mb-2">
                                Unggah file materi dalam format PDF (maksimal 5MB).
                            </p>

                            <label
                                htmlFor="content"
                                className={`flex flex-col items-center justify-center w-full h-64 border-2 ${errors.content ? "border-red-500" : "border-gray-300"
                                    } border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100`}
                            >
                                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg
                                        className="w-8 h-8 mb-4 text-gray-500"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 20 16"
                                    >
                                        <path
                                            stroke="currentColor"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                                        />
                                    </svg>

                                    <p className="text-sm text-gray-700 font-semibold">
                                        Klik untuk unggah
                                    </p>
                                    <p className="text-xs text-gray-600">
                                        Format PDF • Maksimal 5MB
                                    </p>
                                </div>
                                <input
                                    id="content"
                                    type="file"
                                    accept="application/pdf"
                                    onChange={handleFileChange}
                                    className="hidden"
                                />
                            </label>

                            {values.content && (
                                <p className="mt-2 text-sm text-green-600">
                                    File terpilih: {values.content.name}
                                </p>
                            )}

                            {errors.content && (
                                <small className="text-red-500">{errors.content}</small>
                            )}
                        </div>

                    </div>
                </div>

                {/* BUTTON */}
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
