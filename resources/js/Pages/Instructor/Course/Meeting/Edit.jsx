import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormTextArea from "@/Components/FormTextArea";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import ReactQuill from "react-quill-new";
import "react-quill-new/dist/quill.snow.css";

const Edit = ({ slug, activity }) => {
    const { errors } = usePage().props;

    const [values, setValues] = useState({
        title: activity?.title || "",
        description: activity?.description || "",
        value: activity?.value || "",
        instruction: activity?.instruction || "",
        url: activity?.url || "",
        duration: activity?.duration || "",
    });

    useEffect(() => {
        if (activity) {
            setValues({
                title: activity.title,
                description: activity.description,
                value: activity.value,
                instruction: activity.instruction,
                url: activity.url,
                duration: activity.duration,
            });
        }
    }, [activity]);

    function handleChange(e) {
        const { id, type, value, checked } = e.target;
        setValues((values) => ({
            ...values,
            [id]: type === "checkbox" ? checked : value,
        }));
    }

    function handleSubmit(e) {
        e.preventDefault();
        router.put(
            `/admin/course/${slug}/activity/meeting/${activity.id}`,
            values
        );
    }

    return (
        <App>
            <ActivityFormLayout
                slug={slug}
                type="meeting"
                title="Edit Pertemuan Daring"
                description="Perbarui detail sesi pertemuan online dan URL untuk peserta."
                isEdit={true}
            />

            <form onSubmit={handleSubmit}>
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">

                        {/* Judul */}
                        <FormInput
                            title={"title"}
                            label={"Judul Pertemuan"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            small="Minimal 3 karakter. Masukkan judul pertemuan yang jelas dan informatif."
                        />

                        {/* Deskripsi */}
                        <FormTextArea
                            title={"description"}
                            label={"Deskripsi Pertemuan"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.description}
                            error={errors.description}
                            small="Berikan penjelasan singkat mengenai tujuan dan isi pertemuan daring."
                        />

                        {/* Nilai */}
                        <FormInput
                            title={"value"}
                            label={"Bobot Nilai"}
                            type="number"
                            isRequired={true}
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                            small="Isikan angka antara 1–100. Bobot nilai akan digunakan saat penilaian aktivitas."
                        />

                        {/* Instruksi */}
                        <div className="h-auto mb-3">
                            <FormLabel title={"Instruksi"} />

                            <ReactQuill
                                value={values.instruction}
                                onChange={(value) =>
                                    setValues((prevValues) => ({
                                        ...prevValues,
                                        instruction: value,
                                    }))
                                }
                            />

                            {errors.instruction && (
                                <small className="text-red-500 text-sm mt-1 block">
                                    {errors.instruction}
                                </small>
                            )}

                            <small className="text-gray-500 text-xs mt-1 block">
                                Tuliskan instruksi atau panduan lengkap yang harus diikuti oleh peserta.
                            </small>
                        </div>

                        {/* URL Meeting */}
                        <FormInput
                            title={"url"}
                            label={"Tautan Meeting (URL)"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.url}
                            error={errors.url}
                            small="Masukkan URL meeting yang valid (Zoom, Google Meet, Webex, dll.)."
                        />

                        {/* Durasi */}
                        <FormInput
                            title={"duration"}
                            label={"Durasi Pertemuan (Detik)"}
                            type="number"
                            isRequired={true}
                            onChange={handleChange}
                            value={values.duration}
                            error={errors.duration}
                            small="Gunakan satuan detik. Contoh: 60 = 60 detik (1 menit)."
                        />

                    </div>
                </div>

                <div className="max-w-3xl mx-auto flex flex-col sm:flex-row justify-end gap-3 mt-8">
                    <Link href={`/admin/course/${slug}/activity`} className="w-full sm:w-auto">
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
