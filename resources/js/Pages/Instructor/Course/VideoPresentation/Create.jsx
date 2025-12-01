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
        router.post(`/instructor/course/${slug}/activity/video`, values);
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={"Video Pembelajaran"} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">

                        {/* JUDUL */}
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

                        {/* BOBOT NILAI */}
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

                        {/* INSTRUKSI */}
                        <div className="h-auto mb-3">
                            <FormLabel title={"Instruksi Tambahan"} />

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
                                Berikan penjelasan tambahan seperti catatan penting, hal yang harus diperhatikan,
                                atau instruksi untuk menonton video.
                            </small>
                        </div>

                        {/* URL VIDEO */}
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
                            placeholder={"Contoh: 300 = 5 menit"}
                            small={"Durasi diperlukan untuk pengaturan waktu dan statistik pembelajaran."}
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
