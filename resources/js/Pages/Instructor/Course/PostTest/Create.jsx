import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormCheckbox from "@/Components/FormCheckbox";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
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
        min_score: 0,
        is_loop: false,
        value: "",
        timer: 0,
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
        router.post(`/instructor/course/${slug}/activity/post-test`, values);
    }

    return (
        <App>
            <ActivityFormLayout
                slug={slug}
                type="post-test"
                title="Pengaturan Post-Test"
                description="Test akhir yang menjadi syarat penerbitan Sertifikat Digital peserta."
            />

            <form onSubmit={handleSubmit}>
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">

                        {/* JUDUL */}
                        <FormInput
                            title={"title"}
                            label={"Judul Post-Test / Kuis"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            small={"Judul harus singkat dan jelas. Contoh: 'Tes Akhir Bab' atau 'Kuis Evaluasi'."}
                        />

                        {/* DESKRIPSI */}
                        <FormTextArea
                            title={"description"}
                            label={"Deskripsi"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.description}
                            error={errors.description}
                            small={"Berikan gambaran singkat mengenai isi atau tujuan post-test."}
                        />

                        {/* BOBOT NILAI */}
                        <FormInput
                            title={"value"}
                            label={"Bobot Nilai"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.value}
                            type="number"
                            error={errors.value}
                            small={"Isi bobot nilai untuk aktivitas ini (misal: 10, 20, 100). Hanya angka diperbolehkan."}
                        />

                        {/* DURASI */}
                        <FormInput
                            title={"timer"}
                            label={"Durasi Waktu (Detik)"}
                            isRequired={true}
                            type="number"
                            onChange={handleChange}
                            value={values.timer}
                            error={errors.timer}
                            placeholder={"Waktu dalam detik (contoh: 60 = 1 menit)"}
                            small={"Masukkan lama pengerjaan dalam detik. Contoh: 1800 = 30 menit."}
                        />

                        {/* INSTRUKSI */}
                        <div className="h-auto mb-3">
                            <FormLabel title={"Instruksi Soal"} />

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
                                Tuliskan instruksi pengerjaan secara jelas, seperti aturan menjawab atau petunjuk teknis.
                            </small>
                        </div>

                        {/* NILAI MINIMAL */}
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

                        {/* PENGULANGAN */}
                        <FormCheckbox
                            id="is_loop"
                            value={values.is_loop}
                            title={"Pengulangan? (Maksimal pengulangan untuk post-test adalah 3x)"}
                            small={
                                "Post-test selalu diperbolehkan untuk diulang. Maksimal pengulangan adalah 3x."
                            }
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

export default Create;
