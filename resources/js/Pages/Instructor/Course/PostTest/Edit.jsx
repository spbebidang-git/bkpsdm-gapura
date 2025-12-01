import FormCheckbox from "@/Components/FormCheckbox";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormTextArea from "@/Components/FormTextArea";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
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
        is_loop: activity?.is_loop ? true : false,   // ← benar
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
                is_loop: activity.is_loop ? true : false,  // ← benar
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
        router.put(`/instructor/course/${slug}/activity/post-test/${activity.id}`, values);
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={"Edit Post-Test / Kuis"} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">

                        {/* JUDUL */}
                        <FormInput
                            title={"title"}
                            label={"Judul Post-Test / Kuis"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.title}
                            error={errors.title}
                            small={"Judul harus jelas dan sesuai dengan materi yang diuji. Contoh: 'Post-Test Bab 2'."}
                        />

                        {/* DESKRIPSI */}
                        <FormTextArea
                            title={"description"}
                            label={"Deskripsi"}
                            isRequired={true}
                            onChange={handleChange}
                            value={values.description}
                            error={errors.description}
                            small={"Deskripsikan secara singkat tujuan post-test atau materi yang diuji."}
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
                            small={"Masukkan bobot nilai untuk post-test (contoh: 20, 50, 100)."}
                        />

                        {/* TIMER */}
                        <FormInput
                            title={"timer"}
                            label={"Durasi Waktu (Menit)"}
                            isRequired={true}
                            type="number"
                            onChange={handleChange}
                            value={values.timer}
                            error={errors.timer}
                            placeholder={"Contoh: 30"}
                            small={"Durasi pengerjaan post-test dalam menit. Contoh: 15 = 15 menit."}
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
                                Tulis instruksi pengerjaan dengan jelas, seperti aturan menjawab,
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
                            id="is_loop"
                            title={'Pengulangan? (Maksimal pengulangan untuk post-test adalah 3x)'}
                            onChange={handleChange}
                            checked={values.is_loop}
                        />

                    </div>
                </div>

                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Simpan Perubahan</button>
                    <Link className="w-full" href={`/instructor/course/${slug}/activity`}>
                        <button className="button-secondary" type="button">Kembali</button>
                    </Link>
                </div>
            </form>
        </App>
    );
};

export default Edit;
