import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
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
        router.post(`/admin/course/${slug}/activity/meeting`, values);
    }

    return (
        <App fullWidth>
            <ActivityFormLayout
                slug={slug}
                type="meeting"
                title="Pertemuan Daring"
                description="Buat sesi pertemuan online beserta URL yang digunakan peserta untuk bergabung."
                fullWidth
            />

            <form onSubmit={handleSubmit} className="w-full space-y-5">
                <section className={`${card} overflow-hidden`} aria-labelledby="info-meeting">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="info-meeting" className={sectionTitle}>Informasi pertemuan</h2>
                        <p className={sectionDescription}>Jelaskan tujuan dan isi sesi yang akan diikuti peserta.</p>
                    </div>
                    <div className="p-5 sm:p-7">
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
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="akses-meeting">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="akses-meeting" className={sectionTitle}>Akses dan penilaian</h2>
                        <p className={sectionDescription}>Atur tautan bergabung, durasi, dan bobot aktivitas.</p>
                    </div>
                    <div className="grid gap-x-5 p-5 sm:grid-cols-2 sm:p-7">
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
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="instruksi-meeting">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="instruksi-meeting" className={sectionTitle}>Instruksi untuk peserta</h2>
                        <p className={sectionDescription}>Tambahkan panduan lengkap yang harus diikuti sebelum pertemuan berlangsung.</p>
                    </div>
                    <div className="p-5 sm:p-7">
                        <div className="[&_.ql-container]:min-h-36 [&_.ql-container]:rounded-b-lg [&_.ql-container]:border-hairline [&_.ql-editor]:min-h-36 [&_.ql-toolbar]:rounded-t-lg [&_.ql-toolbar]:border-hairline [&_.ql-toolbar]:bg-surface-soft">
                            <FormLabel title={"Instruksi"} />
                            <ReactQuill value={values.instruction} onChange={(value) => setValues((prevValues) => ({ ...prevValues, instruction: value }))} />
                        </div>
                        {errors.instruction && <small className="mt-1 block text-sm text-red-500">{errors.instruction}</small>}
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
