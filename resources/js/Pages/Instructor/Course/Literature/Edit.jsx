import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
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

        if (file.type !== "application/pdf") {
            alert("Format file harus PDF.");
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            alert("Ukuran file maksimal 5MB.");
            return;
        }

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
            <NumberActivity number={3} />
            <TitleActivity number={3} title="Literature" />

            <form onSubmit={handleSubmit} encType="multipart/form-data">
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">

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
                            <FormLabel title="content" isRequired={true} />
                            <p className="text-xs text-gray-500 mb-2">
                                Unggah file PDF baru jika diperlukan. Ukuran maksimal 5MB.
                            </p>

                            {/* Jika ada file lama */}
                            {activity.content && (
                                <p className="text-sm mb-2">
                                    <span className="font-semibold">File saat ini:</span>{" "}
                                    {activity.content}
                                </p>
                            )}

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
                        </div>

                    </div>
                </div>

                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Simpan Perubahan</button>
                    <Link
                        className="w-full"
                        href={`/instructor/course/${slug}/activity`}
                    >
                        <button className="button-secondary">Kembali</button>
                    </Link>
                </div>
            </form>
        </App>
    );
};

export default Edit;
