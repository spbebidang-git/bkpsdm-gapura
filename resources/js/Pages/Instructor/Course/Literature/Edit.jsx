import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

const Edit = ({ slug, activity }) => {
    const { errors } = usePage().props;

    const [values, setValues] = useState({
        title: activity?.title || "",
        sub_title: activity?.sub_title || "",
        value: activity?.value || "",
        instruction: activity?.instruction || "",
        duration: activity?.duration || "",
        content: null, // file baru (opsional)
    });

    function handleChange(e) {
        const { id, type, value, checked } = e.target;
        setValues((prev) => ({
            ...prev,
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
        formData.append("_method", "put");
        formData.append("title", values.title);
        formData.append("sub_title", values.sub_title);
        formData.append("value", values.value);
        formData.append("instruction", values.instruction);
        formData.append("duration", values.duration);

        // File opsional, hanya dikirim kalau ada file baru
        if (values.content) {
            formData.append("content", values.content);
        }

        router.post(
            `/instructor/course/${slug}/activity/literature/${activity.id}`,
            formData
        );
    }

    return (
        <App>
            <ActivityFormLayout
                slug={slug}
                type="literature"
                title="Edit Literatur"
                description="Perbarui file PDF atau detail literatur yang diakses peserta."
                isEdit={true}
            />

            <form onSubmit={handleSubmit} encType="multipart/form-data">
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">

                        <FormInput
                            title="title"
                            placeholder="Masukkan judul materi"
                            small="Isi dengan judul literatur yang jelas."
                            isRequired
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                        />

                        <FormInput
                            title="sub title"
                            placeholder="Masukkan sub judul"
                            small="Sub judul untuk menjelaskan detail tambahan."
                            isRequired
                            onChange={handleChange}
                            value={values.sub_title}
                            error={errors.sub_title}
                        />

                        <FormInput
                            title="value"
                            placeholder="Masukkan bobot nilai"
                            small="Wajib angka. Contoh: 10"
                            isRequired
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                        />

                        {/* Instruksi */}
                        <div className="h-auto mb-3">
                            <FormLabel title="instruction" />
                            <p className="text-xs text-gray-500 mb-1">
                                Berikan petunjuk lengkap mengenai materi ini.
                            </p>

                            <ReactQuill
                                value={values.instruction}
                                onChange={(value) =>
                                    setValues((prev) => ({
                                        ...prev,
                                        instruction: value,
                                    }))
                                }
                            />

                            {errors.instruction && (
                                <small className="text-red-500">
                                    {errors.instruction}
                                </small>
                            )}
                        </div>

                        <FormInput
                            title="duration"
                            placeholder="Durasi (detik), contoh: 60 = 1 menit"
                            small="Wajib dalam satuan detik."
                            isRequired
                            onChange={handleChange}
                            value={values.duration}
                            error={errors.duration}
                        />

                        {/* Upload File PDF */}
                        <div className="mb-3">
                            <FormLabel title="content" isRequired={false} />
                            <p className="text-xs text-gray-500 mb-2">
                                Unggah file PDF baru jika ingin mengganti. Ukuran maksimal 5MB.
                            </p>

                            <div className="flex items-center justify-center w-full">
                                <label
                                    htmlFor="content"
                                    className={`flex flex-col items-center justify-center w-full h-64 border-2 ${errors.content
                                        ? "border-red-500"
                                        : "border-gray-300"
                                        } border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100`}
                                >
                                    <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg
                                            className="w-8 h-8 mb-4 text-gray-500"
                                            aria-hidden="true"
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

                                        <p className="text-sm text-gray-700">
                                            <span className="font-semibold">Klik untuk unggah</span>{" "}
                                            atau tarik file ke sini
                                        </p>
                                        <p className="text-xs text-gray-600">
                                            Format: PDF • Maksimal 5MB
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
                            </div>

                            {values.content && (
                                <p className="mt-2 text-sm text-green-600">
                                    File baru: {values.content.name}
                                </p>
                            )}

                            {errors.content && (
                                <small className="text-red-500">
                                    {errors.content}
                                </small>
                            )}

                            {/* Tombol lihat / download PDF yang sudah ada */}
                            {activity.content_url && (
                                <div className="mt-3 flex gap-2">
                                    <a
                                        href={activity.content_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition"
                                    >
                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            <path stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                        </svg>
                                        Lihat PDF
                                    </a>
                                    <a
                                        href={activity.content_url}
                                        download
                                        className="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-primary border border-primary rounded-lg hover:bg-primary hover:text-white transition"
                                    >
                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" d="M12 3v12m0 0-4-4m4 4 4-4M3 17v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2" />
                                        </svg>
                                        Download PDF
                                    </a>
                                </div>
                            )}
                        </div>

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

export default Edit;
