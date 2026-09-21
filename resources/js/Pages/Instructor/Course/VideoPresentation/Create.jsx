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
        instruction: "",
        value: "",
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
        router.post(`/admin/course/${slug}/activity/video`, values);
    }

    return (
        <App fullWidth>
            <ActivityFormLayout
                slug={slug}
                type="video"
                title="Presentasi Video"
                description="Tambahkan video pembelajaran beserta URL yang dapat diakses peserta."
                fullWidth
            />

            <form onSubmit={handleSubmit} className="w-full space-y-5">
                <section className={`${card} overflow-hidden`} aria-labelledby="info-video">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="info-video" className={sectionTitle}>Informasi video</h2>
                        <p className={sectionDescription}>Berikan judul dan gambaran materi yang akan ditonton peserta.</p>
                    </div>
                    <div className="p-5 sm:p-7">
                        <FormInput
                            title={"title"}
                            label={"Judul Video"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            placeholder={"Contoh: Pengenalan Materi Bab 1"}
                            small={"Judul harus singkat dan jelas, misalnya 'Pengenalan Materi Bab 1'."}
                        />

                        {/* DESKRIPSI */}
                        <FormTextArea
                            title={"description"}
                            label={"Deskripsi Video"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.description}
                            error={errors.description}
                            placeholder={"Tuliskan gambaran singkat isi video atau poin pembelajaran penting."}
                            small={"Deskripsi membantu peserta memahami tujuan pembelajaran dari video tersebut."}
                        />
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="akses-video">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="akses-video" className={sectionTitle}>Akses dan penilaian</h2>
                        <p className={sectionDescription}>Masukkan tautan video, durasi, dan bobot aktivitas.</p>
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
                            placeholder={"Contoh: 10, 20, 100"}
                            small={"Bobot nilai akan digunakan jika video ini memiliki penilaian tertentu."}
                        />
                        <FormInput
                            title={"url"}
                            label={"URL Video"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.url}
                            error={errors.url}
                            placeholder={"Masukkan URL video (YouTube, Vimeo, atau link lainnya)"}
                            small={"Pastikan URL valid dan dapat diakses oleh peserta."}
                        />

                        {/* DURASI */}
                        <FormInput
                            title={"duration"}
                            label={"Durasi Video (Detik)"}
                            type="number"
                            isRequired={true}
                            onChange={handleChange}
                            value={values.duration}
                            error={errors.duration}
                            placeholder={"Waktu dalam detik (contoh: 300 = 5 menit)"}
                            small={"Durasi diperlukan untuk pengaturan waktu dan statistik pembelajaran."}
                        />
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="instruksi-video">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="instruksi-video" className={sectionTitle}>Instruksi tambahan</h2>
                        <p className={sectionDescription}>Tambahkan catatan penting atau arahan saat peserta menonton video.</p>
                    </div>
                    <div className="p-5 sm:p-7">
                        <div className="[&_.ql-container]:min-h-36 [&_.ql-container]:rounded-b-lg [&_.ql-container]:border-hairline [&_.ql-editor]:min-h-36 [&_.ql-toolbar]:rounded-t-lg [&_.ql-toolbar]:border-hairline [&_.ql-toolbar]:bg-surface-soft">
                            <FormLabel title={"Instruksi Tambahan"} />
                            <ReactQuill value={values.instruction} onChange={(value) => setValues((prevValues) => ({ ...prevValues, instruction: value }))} />
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
