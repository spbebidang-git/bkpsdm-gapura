import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormSelect from "@/Components/FormSelect";
import FormTextArea from "@/Components/FormTextArea";
import { btnPrimary, btnSecondary, card, sectionDescription, sectionTitle } from "@/Components/Admin/ui";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import ReactQuill from "react-quill-new";
import "react-quill-new/dist/quill.snow.css";

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
        router.post(`/admin/course/${slug}/activity/task`, values);
    }

    return (
        <App fullWidth>
            <ActivityFormLayout
                slug={slug}
                type="task"
                title="Pengaturan Tugas"
                description="Atur penugasan yang hasilnya akan dikirim ke email instruktur."
                fullWidth
            />

            <form onSubmit={handleSubmit} className="w-full space-y-5">
                <section className={`${card} overflow-hidden`} aria-labelledby="info-tugas">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="info-tugas" className={sectionTitle}>Informasi tugas</h2>
                        <p className={sectionDescription}>Gunakan judul yang memudahkan peserta mengenali penugasan.</p>
                    </div>
                    <div className="p-5 sm:p-7">
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

                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="penilaian-tugas">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="penilaian-tugas" className={sectionTitle}>Pengaturan penilaian</h2>
                        <p className={sectionDescription}>Tentukan bobot tugas dan cara penilaiannya.</p>
                    </div>
                    <div className="grid gap-x-5 p-5 sm:grid-cols-2 sm:p-7">
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

                        <FormSelect
                            title={"is_calculated"}
                            label={"Nilai Otomatis?"}
                            value={values.is_calculated}
                            onChange={handleChange}
                            isRequired={true}
                            options={typeOptions}
                            small={"Pilih 'Ya' jika tugas dinilai otomatis oleh sistem. Pilih 'Tidak' jika dinilai oleh instruktur."}
                        />
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="instruksi-tugas">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="instruksi-tugas" className={sectionTitle}>Instruksi tugas</h2>
                        <p className={sectionDescription}>Berikan aturan penulisan, format pengumpulan, atau panduan teknis.</p>
                    </div>
                    <div className="p-5 sm:p-7">
                        <div className="mb-5 [&_.ql-container]:min-h-36 [&_.ql-container]:rounded-b-lg [&_.ql-container]:border-hairline [&_.ql-editor]:min-h-36 [&_.ql-toolbar]:rounded-t-lg [&_.ql-toolbar]:border-hairline [&_.ql-toolbar]:bg-surface-soft">
                            <FormLabel title={"Instruksi Tugas"} />
                            <ReactQuill value={values.instruction} onChange={(value) => setValues((prevValues) => ({ ...prevValues, instruction: value }))} />
                        </div>
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="deskripsi-tugas">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="deskripsi-tugas" className={sectionTitle}>Soal atau deskripsi tugas</h2>
                        <p className={sectionDescription}>Jelaskan keluaran yang harus dikumpulkan dan standar pengerjaannya.</p>
                    </div>
                    <div className="p-5 sm:p-7">
                        <div>
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

                    </div>
                </section>

                <div className="flex flex-col-reverse gap-3 border-t border-hairline pt-5 sm:flex-row sm:justify-end">
                    <Link href={`/admin/course/${slug}/activity`} className="w-full sm:w-auto">
                        <button type="button" className={`${btnSecondary} w-full sm:w-auto`}>
                            Kembali
                        </button>
                    </Link>
                    <button type="submit" className={`${btnPrimary} w-full sm:w-auto`}>
                        <i className="bx bx-save text-lg" aria-hidden />
                        Simpan
                    </button>
                </div>
            </form>
        </App>
    );
};

export default Create;
