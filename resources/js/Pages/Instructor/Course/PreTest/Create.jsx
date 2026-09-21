import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormCheckbox from "@/Components/FormCheckbox";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormTextArea from "@/Components/FormTextArea";
import {
    btnPrimary,
    btnSecondary,
    card,
    sectionDescription,
    sectionTitle,
} from "@/Components/Admin/ui";
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
        timer: 0,
        min_score: 0,
        repeatable: false
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
        router.post(`/admin/course/${slug}/activity/pre-test`, values);
    }

    return (
        <App
            title="Buat Pre-Test"
            breadcrumbs={[
                { label: "Kelas", href: `/admin/course/${slug}` },
                { label: "Kegiatan", href: `/admin/course/${slug}/activity` },
                { label: "Pre Test" },
                { label: "Create" },
            ]}
            fullWidth
        >
            <ActivityFormLayout
                slug={slug}
                type="pre-test"
                title="Pengaturan Pre-Test / Kuis"
                description="Atur soal pilihan ganda untuk menilai pemahaman awal peserta."
                fullWidth
                showIcon={false}
            />

            <form onSubmit={handleSubmit} className="w-full space-y-5">
                <section className={`${card} overflow-hidden`} aria-labelledby="detail-pretest">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="detail-pretest" className={sectionTitle}>Informasi pre-test</h2>
                        <p className={sectionDescription}>Berikan nama dan ringkasan agar peserta memahami tujuan evaluasi ini.</p>
                    </div>
                    <div className="space-y-1 px-5 py-5 sm:px-7 sm:py-6">
                        <FormInput
                            title={"title"}
                            label={"Judul Pre-Test / Kuis"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            small={"Gunakan judul singkat dan spesifik, misalnya “Tes Bab 1” atau “Kuis Pengantar”."}
                        />
                        <FormTextArea
                            title={"description"}
                            label={"Deskripsi"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.description}
                            error={errors.description}
                        />
                        <p className="-mt-1 text-xs leading-5 text-steel">Berikan gambaran singkat mengenai tujuan atau isi pre-test.</p>
                    </div>

                    <div className="border-y border-hairline px-5 py-4 sm:px-7">
                        <h2 id="pengaturan-penilaian" className={sectionTitle}>Pengaturan penilaian</h2>
                        <p className={sectionDescription}>Tentukan bobot, batas kelulusan, dan waktu pengerjaan peserta.</p>
                    </div>
                    <div className="grid gap-x-5 sm:grid-cols-2 px-5 py-5 sm:px-7 sm:py-6">
                        <FormInput
                            title={"value"}
                            label={"Bobot Nilai"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                            type="number"
                            small={"Contoh: 10, 20, atau 100."}
                        />
                        <FormInput
                            title={"min_score"}
                            label={"Nilai Minimum Kelulusan"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.min_score}
                            error={errors.min_score}
                            type="number"
                            small={"Nilai minimal agar peserta dinyatakan lulus."}
                        />
                        <div className="sm:col-span-2">
                            <FormInput
                                title={"timer"}
                                label={"Durasi Waktu (Detik)"}
                                isRequired={true}
                                onChange={handleChange}
                                value={values.timer}
                                error={errors.timer}
                                type="number"
                                placeholder={"Contoh: 1800"}
                                small={"Gunakan 60 untuk 1 menit atau 1800 untuk 30 menit."}
                            />
                        </div>
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="instruksi-peserta">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="instruksi-peserta" className={sectionTitle}>Instruksi untuk peserta</h2>
                        <p className={sectionDescription}>Tambahkan aturan menjawab atau petunjuk teknis sebelum peserta memulai.</p>
                    </div>
                    <div className="px-5 py-5 sm:px-7 sm:py-6">
                        <div className="[&_.ql-container]:min-h-36 [&_.ql-container]:rounded-b-lg [&_.ql-container]:border-hairline [&_.ql-editor]:min-h-36 [&_.ql-toolbar]:rounded-t-lg [&_.ql-toolbar]:border-hairline [&_.ql-toolbar]:bg-surface-soft">
                            <FormLabel title={"Instruksi Soal"} />
                            <ReactQuill
                                value={values.instruction}
                                onChange={(value) => setValues((prevValues) => ({ ...prevValues, instruction: value }))}
                            />
                        </div>
                        <p className="mt-2 text-xs leading-5 text-steel">Cantumkan instruksi secara jelas, seperti aturan menjawab, larangan, atau petunjuk teknis lainnya.</p>
                    </div>
                </section>

                <section className={`${card} p-5 sm:p-6`} aria-labelledby="opsi-pengerjaan">
                    <h2 id="opsi-pengerjaan" className={sectionTitle}>Opsi pengerjaan</h2>
                    <div className="mt-4 rounded-lg border border-hairline bg-surface-soft px-4 py-3.5">
                        <FormCheckbox
                            id="repeatable"
                            title={"Izinkan peserta mengulang pre-test"}
                            onChange={handleChange}
                            checked={values.repeatable}
                        />
                        <p className="ml-6 text-xs leading-5 text-steel">Aktifkan bila peserta dapat mencoba kembali setelah menyelesaikan pre-test.</p>
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
