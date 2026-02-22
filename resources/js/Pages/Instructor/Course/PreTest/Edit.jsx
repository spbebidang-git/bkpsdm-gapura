import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormCheckbox from "@/Components/FormCheckbox";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormTextArea from "@/Components/FormTextArea";
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
        instruction: activity?.instruction || "",
        value: activity?.value || "",
        timer: activity?.timer || "",
        min_score: activity?.min_score || 0,
        repeatable: activity?.is_loop || false,
    });

    useEffect(() => {
        if (activity) {
            setValues({
                title: activity.title,
                description: activity.description,
                instruction: activity.instruction,
                value: activity.value,
                timer: activity.timer,
                min_score: activity.min_score,
                repeatable: activity.is_loop,
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
        router.put(`/instructor/course/${slug}/activity/pre-test/${activity.id}`, values);
    }

    return (
        <App>
            <ActivityFormLayout
                slug={slug}
                type="pre-test"
                title="Edit Pre-Test / Kuis"
                description="Perbarui pengaturan soal pilihan ganda untuk Pre-Test ini."
                isEdit={true}
            />

            <form onSubmit={handleSubmit}>
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">

                        {/* JUDUL */}
                        <FormInput
                            title={"title"}
                            label={"Judul Pre-Test / Kuis"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            small={"Judul harus singkat dan jelas. Contoh: 'Tes Bab 1' atau 'Kuis Pengantar'."}
                        />

                        {/* DESKRIPSI */}
                        <FormTextArea
                            title={"description"}
                            label={"Deskripsi"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.description}
                            error={errors.description}
                            small={"Berikan gambaran singkat mengenai tujuan atau isi pre-test."}
                        />

                        {/* NILAI */}
                        <FormInput
                            title={"value"}
                            label={"Bobot Nilai"}
                            isRequired={true}
                            type="number"
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                            small={"Isi bobot nilai untuk aktivitas ini (contoh: 10, 20, 100). Hanya angka diperbolehkan."}
                        />

                        {/* TIMER */}
                        <FormInput
                            title={"timer"}
                            label={"Durasi Waktu (Detik)"}
                            isRequired={true}
                            type="number"
                            onChange={handleChange}
                            value={values.timer}
                            error={errors.timer}
                            placeholder={"Waktu dalam detik"}
                            small={"Masukkan durasi pengerjaan dalam detik. Contoh: 60 = 1 menit."}
                        />

                        {/* INSTRUKSI */}
                        <div className="h-auto mb-3">
                            <FormLabel title={"Instruksi Soal"} />

                            <ReactQuill
                                value={values.instruction}
                                onChange={(value) =>
                                    setValues((prev) => ({
                                        ...prev,
                                        instruction: value,
                                    }))
                                }
                            />

                            <small className="text-gray-500 text-xs mt-1 block">
                                Cantumkan instruksi pengerjaan secara jelas, seperti aturan menjawab,
                                larangan, atau petunjuk teknis lainnya.
                            </small>
                        </div>

                        {/* NILAI MINIMUM */}
                        <FormInput
                            title={"min_score"}
                            label={"Nilai Minimum Kelulusan"}
                            isRequired={true}
                            type="number"
                            onChange={handleChange}
                            value={values.min_score}
                            error={errors.min_score}
                            small={"Nilai minimal agar peserta dinyatakan lulus. Contoh: 70."}
                        />

                        {/* REPEATABLE */}
                        <FormCheckbox
                            id="repeatable"
                            title={"Boleh Diulang?"}
                            onChange={handleChange}
                            checked={values.repeatable}
                            small={"Aktifkan jika peserta diperbolehkan mengulang pengerjaan pre-test."}
                        />
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

export default Edit;
