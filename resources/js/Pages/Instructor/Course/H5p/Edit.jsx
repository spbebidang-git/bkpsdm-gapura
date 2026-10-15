import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

const Edit = ({ slug, activity }) => {
    const { errors } = usePage().props;

    const [values, setValues] = useState({
        title: activity?.title || "",
        sub_title: activity?.sub_title || "",
        value: activity?.value || "",
        instruction: activity?.instruction || "",
        duration: activity?.duration || "",
        package: null, // paket baru (opsional)
    });
    const [progress, setProgress] = useState(null);

    function handleChange(e) {
        const { id, value } = e.target;
        setValues((prev) => ({ ...prev, [id]: value }));
    }

    function handleFileChange(e) {
        const file = e.target.files[0];
        if (!file) return;
        setValues((prev) => ({ ...prev, package: file }));
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
        if (values.package) formData.append("package", values.package);

        router.post(
            `/instructor/course/${slug}/activity/h5p/${activity.id}`,
            formData,
            {
                forceFormData: true,
                onProgress: (e) => setProgress(e?.percentage ?? null),
                onFinish: () => setProgress(null),
            }
        );
    }

    return (
        <App>
            <ActivityFormLayout
                slug={slug}
                type="h5p"
                title="Edit H5P Interaktif"
                description="Perbarui paket H5P atau detail konten interaktif yang diakses peserta."
                isEdit={true}
            />

            <form onSubmit={handleSubmit} encType="multipart/form-data">
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">
                        <FormInput
                            title="title"
                            label="Judul"
                            isRequired
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                        />

                        <FormInput
                            title="sub_title"
                            label="Sub Judul"
                            onChange={handleChange}
                            value={values.sub_title}
                            error={errors.sub_title}
                        />

                        <FormInput
                            title="value"
                            label="Bobot Nilai"
                            isRequired
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                        />

                        <div className="h-auto mb-3">
                            <FormLabel title="Instruksi Tambahan" />
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

                        <FormInput
                            title="duration"
                            label="Durasi (Detik)"
                            isRequired
                            onChange={handleChange}
                            value={values.duration}
                            error={errors.duration}
                        />

                        <div className="mb-3">
                            <FormLabel title="Ganti Paket H5P (.h5p)" />
                            <p className="text-xs text-gray-500 mb-2">
                                Kosongkan jika tidak ingin mengganti paket. Paket saat ini:{" "}
                                <span className="font-medium text-gray-700">
                                    {activity?.main_library || activity?.folder || "-"}
                                </span>
                            </p>

                            <label
                                htmlFor="package"
                                className={`flex flex-col items-center justify-center w-full h-56 border-2 ${
                                    errors.package ? "border-red-500" : "border-gray-300"
                                } border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100`}
                            >
                                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg className="w-8 h-8 mb-4 text-gray-500" fill="none" viewBox="0 0 20 16">
                                        <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                                    </svg>
                                    <p className="text-sm text-gray-700 font-semibold">Klik untuk unggah paket baru</p>
                                    <p className="text-xs text-gray-600">Format .h5p • Maksimal 200MB</p>
                                </div>
                                <input id="package" type="file" accept=".h5p" onChange={handleFileChange} className="hidden" />
                            </label>

                            {values.package && (
                                <p className="mt-2 text-sm text-green-600">
                                    File baru: {values.package.name} (
                                    {(values.package.size / 1048576).toFixed(1)} MB)
                                </p>
                            )}
                            {errors.package && (
                                <small className="text-red-500">{errors.package}</small>
                            )}
                        </div>

                        {progress !== null && (
                            <div className="mt-4">
                                <div className="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Mengunggah paket...</span>
                                    <span>{progress}%</span>
                                </div>
                                <div className="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        className="h-full bg-primary transition-all duration-200"
                                        style={{ width: `${Math.max(progress, 3)}%` }}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="max-w-3xl mx-auto flex flex-col sm:flex-row justify-end gap-3 mt-8">
                    <Link href={`/instructor/course/${slug}`} className="w-full sm:w-auto">
                        <button type="button" className="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-semibold transition-colors">
                            Kembali
                        </button>
                    </Link>
                    <button
                        type="submit"
                        disabled={progress !== null}
                        className="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-primary text-white hover:bg-primary/90 font-semibold shadow-sm transition-colors disabled:opacity-50"
                    >
                        {progress !== null ? "Mengunggah..." : "Simpan Perubahan"}
                    </button>
                </div>
            </form>
        </App>
    );
};

export default Edit;
