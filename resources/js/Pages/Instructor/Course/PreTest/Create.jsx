import FormCheckbox from "@/Components/FormCheckbox";
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
        router.post(`/instructor/course/${slug}/activity/pre-test`, values);
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={"Pengaturan Pre-Test / Kuis"} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">

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

                        {/* NILAI / SCORE */}
                        <FormInput
                            title={"value"}
                            label={"Bobot Nilai"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.value}
                            error={errors.value}
                            type="number"
                            small={"Isi bobot nilai untuk aktivitas ini (contoh: 10, 20, 100). Hanya angka diperbolehkan."}
                        />

                        {/* TIMER */}
                        <FormInput
                            title={"timer"}
                            label={"Durasi Waktu (Menit)"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.timer}
                            error={errors.timer}
                            type="number"
                            placeholder={"Waktu dalam menit"}
                            small={"Masukkan durasi pengerjaan dalam menit. Contoh: 30 = 30 menit."}
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
                                Cantumkan instruksi pengerjaan secara jelas, seperti aturan menjawab, larangan, atau petunjuk teknis lainnya.
                            </small>
                        </div>

                        {/* MINIMAL SCORE */}
                        <FormInput
                            title={"min score"}
                            label={"Nilai Minimum Kelulusan"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.min_score}
                            error={errors.min_score}
                            type="number"
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
