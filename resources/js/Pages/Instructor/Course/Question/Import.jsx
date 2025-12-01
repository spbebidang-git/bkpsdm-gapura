import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";

export default function Import({ course, activity }) {

    const { errors } = usePage().props;

    const [values, setValues] = useState({
        content: null,
    });

    function handleChange(e) {
        const { id, type, value, checked, files } = e.target;

        setValues((values) => ({
            ...values,
            [id]: type === "checkbox" ? checked : type === "file" ? files[0] : value,
        }));
    }

    function handleSubmit(e) {
        e.preventDefault();
        const formData = new FormData();

        formData.append("content", values.content);

        router.post(
            route("instructor.course.activity.import-question.store", {
                course: course.slug,
                activity: activity.id,
            }),
            formData
        );
    }

    const handleDownload = () => {
        window.location.href = route(
            "instructor.course.activity.import-question.download",
            { course: course.slug, activity: activity.id }
        );
    };

    return (
        <App>

            {/* ALERT — DITAMBAHKAN SESUAI PERMINTAAN */}
            <div className="max-w-2xl mx-auto mb-6">
                <div className="bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg text-sm">
                    <strong>Perhatian:</strong> Proses <span className="font-semibold">import soal hanya dapat dilakukan satu kali</span>,
                    yaitu sebelum Anda membuat variant atau menambahkan pertanyaan secara manual.
                    Pastikan data yang diunggah sudah benar dan lengkap sebelum melanjutkan.
                </div>
            </div>

            <div className="max-w-2xl border p-8 bg-white mx-auto">
                <div className="mb-5">
                    <h4 className="text-center text-2xl font-bold mb-2 text-primary">
                        Import Question
                    </h4>
                    <p className="text-center mb-3 text-gray-700 text-sm max-w-lg mx-auto">
                        Silakan unduh file template di bawah ini, kemudian lengkapi data sesuai
                        kebutuhan Anda. Setelah selesai, unggah kembali file tersebut dan klik
                        tombol "Import" untuk memulai proses.
                    </p>

                    <div className="max-w-56 mx-auto">
                        <button className="button-primary" onClick={handleDownload}>
                            Unduh Template
                        </button>
                    </div>
                </div>

                <form onSubmit={handleSubmit} encType="multipart/form-data">
                    <div className="flex items-center justify-center w-full mb-5">
                        <label
                            htmlFor="content"
                            className={`flex flex-col items-center justify-center w-full h-64 border-2 ${errors.content ? "border-red-500" : "border-gray-300"
                                } border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100`}
                        >
                            <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg
                                    className="w-8 h-8 mb-4 text-gray-500"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 20 16"
                                >
                                    <path
                                        stroke="currentColor"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                                    />
                                </svg>
                                <p className="mb-2 text-sm text-gray-500">
                                    <span className="font-semibold">Click to upload</span> or drag and drop
                                </p>
                                <p className="text-xs text-gray-500">XLSX (MAX. 2048MB)</p>
                            </div>
                            <input id="content" type="file" onChange={handleChange} className="hidden" />
                        </label>
                    </div>

                    {values.content && (
                        <p className="mt-2 text-sm text-green-500">
                            File selected: {values.content.name}
                        </p>
                    )}

                    {errors.content && (
                        <small className="text-red-500 text-sm mt-1">
                            {errors.content}
                        </small>
                    )}

                    <div className="max-w-xs mx-auto flex gap-5">
                        <button className="button-primary">Import</button>
                        <Link
                            href={route("instructor.course.show", {
                                course: course.slug,
                            })}
                            className="w-full"
                        >
                            <button className="button-secondary">Back</button>
                        </Link>
                    </div>
                </form>
            </div>
        </App>
    );
}
