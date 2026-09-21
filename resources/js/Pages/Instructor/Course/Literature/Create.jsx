import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import { btnPrimary, btnSecondary, card, sectionDescription, sectionTitle } from "@/Components/Admin/ui";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import ReactQuill from "react-quill-new";
import "react-quill-new/dist/quill.snow.css";

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

const Create = ({ slug }) => {
    const { errors } = usePage().props;

    const [values, setValues] = useState({
        title: "",
        sub_title: "",
        value: "",
        instruction: "",
        duration: "",
        content: null,
    });

    function handleChange(e) {
        const { id, type, value, checked } = e.target;

        setValues((values) => ({
            ...values,
            [id]: type === "checkbox" ? checked : value,
        }));
    }

    function handleFileChange(e) {
        const file = e.target.files[0];
        if (!file) return;
        setValues((prev) => ({ ...prev, content: file }));
    }

    function handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append("title", values.title);
        formData.append("sub_title", values.subtitle);
        formData.append("value", values.value);
        formData.append("duration", values.duration);
        formData.append("instruction", values.instruction);
        formData.append("content", values.content);

        router.post(`/admin/course/${slug}/activity/literature`, formData);
    }

    return (
        <App fullWidth>
            <ActivityFormLayout
                slug={slug}
                type="literature"
                title="Literatur"
                description="Upload file PDF literatur yang dapat diakses dan dibaca oleh peserta."
                fullWidth
            />

            <form onSubmit={handleSubmit} encType="multipart/form-data" className="w-full space-y-5">
                <section className={`${card} overflow-hidden`} aria-labelledby="info-literatur">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="info-literatur" className={sectionTitle}>Informasi literatur</h2>
                        <p className={sectionDescription}>Tambahkan judul dan subjudul agar materi mudah dikenali.</p>
                    </div>
                    <div className="grid gap-x-5 p-5 sm:grid-cols-2 sm:p-7">
                        <FormInput
                            title="title"
                            label="Judul"
                            placeholder="Masukkan judul materi"
                            small="Judul harus singkat dan jelas, misalnya: 'Modul Dasar Manajemen Risiko'."
                            isRequired
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                        />

                        {/* SUBTITLE */}
                        <FormInput
                            title="sub_title"
                            label="Sub Judul"
                            placeholder="Masukkan sub judul materi"
                            small="Sub judul digunakan untuk menjelaskan detail tambahan dari materi."
                            isRequired
                            onChange={handleChange}
                            value={values.sub_title}
                            error={errors.sub_title}
                        />
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="pengaturan-literatur">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="pengaturan-literatur" className={sectionTitle}>Pengaturan belajar</h2>
                        <p className={sectionDescription}>Tentukan bobot nilai dan estimasi durasi membaca.</p>
                    </div>
                    <div className="grid gap-x-5 p-5 sm:grid-cols-2 sm:p-7">
                        <FormInput
                            title="value"
                            label="Bobot Nilai"
                            placeholder="Contoh: 10, 20, 100"
                            small="Bobot nilai digunakan jika literatur ini menjadi bagian penilaian."
                            isRequired
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                        />
                        <FormInput
                            title="duration"
                            label="Durasi Membaca (Detik)"
                            placeholder="Waktu dalam detik (contoh: 300 = 5 menit)"
                            small="Durasi diperlukan untuk estimasi waktu belajar peserta."
                            isRequired
                            onChange={handleChange}
                            value={values.duration}
                            error={errors.duration}
                        />
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="instruksi-literatur">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="instruksi-literatur" className={sectionTitle}>Instruksi tambahan</h2>
                        <p className={sectionDescription}>Berikan catatan penting atau arahan sebelum peserta membaca materi.</p>
                    </div>
                    <div className="p-5 sm:p-7">
                        <div className="[&_.ql-container]:min-h-36 [&_.ql-container]:rounded-b-lg [&_.ql-container]:border-hairline [&_.ql-editor]:min-h-36 [&_.ql-toolbar]:rounded-t-lg [&_.ql-toolbar]:border-hairline [&_.ql-toolbar]:bg-surface-soft">
                            <FormLabel title="Instruksi Tambahan" />
                            <ReactQuill value={values.instruction} onChange={(value) => setValues((prev) => ({ ...prev, instruction: value }))} />
                        </div>
                        {errors.instruction && <small className="mt-1 block text-red-500">{errors.instruction}</small>}
                    </div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="berkas-literatur">
                    <div className="border-b border-hairline px-5 py-4 sm:px-7">
                        <h2 id="berkas-literatur" className={sectionTitle}>Berkas literatur</h2>
                        <p className={sectionDescription}>Unggah materi PDF yang akan dapat diakses oleh peserta.</p>
                    </div>
                    <div className="p-5 sm:p-7">
                        <div>
                            <FormLabel title="File PDF" />
                            <p className="text-xs text-gray-500 mb-2">
                                Unggah file materi dalam format PDF (maksimal 5MB).
                            </p>

                            <label
                                htmlFor="content"
                                className={`flex h-64 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed ${errors.content ? "border-red-500" : "border-hairline-strong"
                                    } bg-surface-soft transition-colors hover:border-primary/50 hover:bg-primary-mist/40`}
                            >
                                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i className="bx bx-cloud-upload mb-3 text-4xl text-primary" aria-hidden />

                                    <p className="text-sm font-semibold text-charcoal">
                                        Klik untuk unggah
                                    </p>
                                    <p className="text-xs text-steel">
                                        Format PDF • Maksimal 5MB
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

                            {values.content && (
                                <p className="mt-2 text-sm font-medium text-[#246A3C]">
                                    File terpilih: {values.content.name}
                                </p>
                            )}

                            {errors.content && (
                                <small className="text-red-500">{errors.content}</small>
                            )}
                        </div>
                    </div>
                </section>

                {/* BUTTON */}
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
