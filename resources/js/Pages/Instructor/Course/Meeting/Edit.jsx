import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormTextArea from "@/Components/FormTextArea";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

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
            `/instructor/course/${slug}/activity/meeting/${activity.id}`,
            values
        );
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={"Edit Pertemuan Daring"} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">

                        {/* Judul */}
                        <FormInput
                            title={"Judul Pertemuan"}
                            label={"Judul Pertemuan"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            small="Minimal 3 karakter. Masukkan judul pertemuan yang jelas dan informatif."
                        />

                        {/* Deskripsi */}
                        <FormTextArea
                            title={"Deskripsi Pertemuan"}
                            label={"Deskripsi Pertemuan"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.description}
                            error={errors.description}
                            small="Berikan penjelasan singkat mengenai tujuan dan isi pertemuan daring."
                        />

                        {/* Nilai */}
                        <FormInput
                            title={"Bobot Nilai"}
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
                            title={"Tautan Meeting"}
                            label={"Tautan Meeting (URL)"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.url}
                            error={errors.url}
                            small="Masukkan URL meeting yang valid (Zoom, Google Meet, Webex, dll.)."
                        />

                        {/* Durasi */}
                        <FormInput
                            title={"Durasi Pertemuan"}
                            label={"Durasi Pertemuan (Menit)"}
                            type="number"
                            isRequired={true}
                            onChange={handleChange}
                            value={values.duration}
                            error={errors.duration}
                            small="Gunakan satuan menit. Contoh: 60 = 60 detik (1 menit)."
                        />

                    </div>
                </div>

                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Perbarui</button>

                    <Link className="w-full" href={`/instructor/course/${slug}/activity`}>
                        <button className="button-secondary">Kembali</button>
                    </Link>
                </div>
            </form>
        </App>
    );
};

export default Edit;
