import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormSelect from "@/Components/FormSelect";
import FormTextArea from "@/Components/FormTextArea";
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
        instruction: "",
        is_calculated: "",
        value: ""
    });

    function handleChange(e) {
        const { id, type, value, checked } = e.target;
        setValues((values) => ({
            ...values,
            [id]: type === "checkbox" ? checked : value,
        }));
    }

    const typeOptions = [
        { value: 0, label: "Tidak" },
        { value: 1, label: "Ya" },
    ];

    function handleSubmit(e) {
        e.preventDefault();
        router.post(`/instructor/course/${slug}/activity/task`, values);
    }

    return (
        <App>
            <ActivityFormLayout
                slug={slug}
                type="task"
                title="Pengaturan Tugas"
                description="Atur penugasan yang hasilnya akan dikirim ke email instruktur."
            />

            <form onSubmit={handleSubmit}>
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">

                        {/* JUDUL */}
                        <FormInput
                            title={"title"}
                            label={"Judul Tugas"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            placeholder={"Contoh: Tugas Bab 1"}
                            small={"Judul harus singkat dan jelas. Contoh: 'Tugas Bab 1' atau 'Analisis Studi Kasus'."}
                        />

                        {/* NILAI */}
                        <FormInput
                            title={"value"}
                            label={"Bobot Nilai"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                            type="number"
                            placeholder={"Contoh: 10, 20, 100"}
                            small={"Masukkan bobot nilai untuk tugas ini. Hanya angka diperbolehkan."}
                        />

                        {/* INSTRUKSI */}
                        <div className="h-auto mb-3">
                            <FormLabel title={"Instruksi Tugas"} />

                            <ReactQuill
                                value={values.instruction}
                                onChange={(value) =>
                                    setValues((prevValues) => ({
                                        ...prevValues,
                                        instruction: value,
                                    }))
                                }
                            />

                            <small className="text-gray-500 text-xs mt-1 block">
                                Jelaskan instruksi pengerjaan secara jelas, seperti aturan penulisan,
                                format pengumpulan, atau panduan teknis lainnya.
                            </small>
                        </div>

                        {/* PENILAIAN AUTOMATIS */}
                        <FormSelect
                            title={"is_calculated"}
                            label={"Nilai Otomatis?"}
                            value={values.is_calculated}
                            onChange={handleChange}
                            isRequired={true}
                            options={typeOptions}
                            small={"Pilih 'Ya' jika tugas dinilai otomatis oleh sistem. Pilih 'Tidak' jika dinilai oleh instruktur."}
                        />

                        {/* DESKRIPSI */}
                        <div className="-mb-3">
                            <FormTextArea
                                title={"description"}
                                label={"Deskripsi Tugas"}
                                isRequired={true}
                                onChange={handleChange}
                                value={values.description}
                                error={errors.description}
                                placeholder={"Masukkan soal, penjelasan tugas, atau panduan yang harus dikerjakan peserta."}
                            />
                        </div>

                        <small className="italic text-gray-600">
                            Isi dengan soal atau deskripsi tugas beserta standar pengumpulannya.
                        </small>

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
