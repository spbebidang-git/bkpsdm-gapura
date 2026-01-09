import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormTextArea from "@/Components/FormTextArea";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

const Create = ({ slug }) => {
    const { errors } = usePage().props;

    const [values, setValues] = useState({
        title: "",
        description: "",
        value: "",
        instruction: "",
        url: "",
        duration: "",
    });

    function handleChange(e) {
        const { id, type, value, checked } = e.target;
        setValues((values) => ({
            ...values,
            [id]: type === "checkbox" ? checked : value,
        }));
    }

    function handleSubmit(e) {
        e.preventDefault();
        router.post(`/instructor/course/${slug}/activity/meeting`, values);
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={"Pertemuan Daring"} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">

                        {/* Judul */}
                        <FormInput
                            title={"title"}
                            label={"Judul Pertemuan"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            small="Minimal 3 karakter. Isikan judul pertemuan yang jelas dan mudah dipahami."
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

                        {/* Bobot Nilai */}
                        <FormInput
                            title={"value"}
                            label={"Bobot Nilai"}
                            isRequired={true}
                            type="number"
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                            small="Isikan angka antara 1–100. Bobot nilai digunakan untuk penilaian aktivitas."
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
                                Tuliskan instruksi atau panduan lengkap yang harus diikuti peserta.
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
                            small="Masukkan URL meeting yang valid (Zoom, Google Meet, atau platform lainnya)."
                        />

                        {/* Durasi */}
                        <FormInput
                            title={"duration"}
                            label={"Durasi Pertemuan (Detik)"}
                            isRequired={true}
                            type="number"
                            onChange={handleChange}
                            value={values.duration}
                            error={errors.duration}
                            small="Gunakan satuan detik. Contoh: 60 = 60 detik (1 menit)."
                        />
                    </div>
                </div>

                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Simpan</button>
                    <Link className="w-full" href={`/instructor/course/${slug}/activity`}>
                        <button className="button-secondary">Kembali</button>
                    </Link>
                </div>
            </form>
        </App>
    );
};

export default Create;
