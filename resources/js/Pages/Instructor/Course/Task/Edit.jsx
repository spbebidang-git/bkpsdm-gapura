import ActivityFormLayout from "@/Components/ActivityFormLayout";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormSelect from "@/Components/FormSelect";
import FormTextArea from "@/Components/FormTextArea";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

const Edit = ({ slug, activity }) => {
    const { errors } = usePage().props

    const [values, setValues] = useState({
        title: activity?.title || "",
        description: activity?.description || "",
        instruction: "123",
        value: activity?.value || "",
        is_calculated: activity?.is_calculated || ""
    });

    useEffect(() => {
        if (activity) {
            setValues({
                title: activity.title,
                description: activity.description,
                is_calculated: activity.is_calculated,
                value: activity.value,
                instruction: activity.instruction,
            });
        }
    }, [activity]);

    function handleChange(e) {
        const { id, type, value, checked } = e.target;
        setValues((values) => ({
            ...values,
            [id]: type === 'checkbox' ? checked : value,
        }));
    }

    const typeOptions = [
        { value: 0, label: "Tidak" },
        { value: 1, label: "Ya" },
    ];

    function handleSubmit(e) {
        e.preventDefault();
        router.put(`/instructor/course/${slug}/activity/task/${activity.id}`, values);
    }

    return (
        <App>
            <ActivityFormLayout
                slug={slug}
                type="task"
                title="Edit Tugas"
                description="Perbarui deskripsi dan pengaturan tugas untuk peserta."
                isEdit={true}
            />

            <form onSubmit={handleSubmit}>
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">
                        <FormInput title={'title'} isRequired={true} onChange={handleChange} value={values.title} error={errors.title} />
                        <FormInput title={'value'} isRequired={true} onChange={handleChange} value={values.value} error={errors.value} />
                        <div className="h-auto mb-3">
                            <FormLabel title={'instruction'} />
                            <ReactQuill
                                value={values.instruction}
                                onChange={(value) => setValues((prevValues) => ({
                                    ...prevValues,
                                    instruction: value
                                }))}
                            />
                        </div>
                        <FormSelect
                            title={"is_calculated"}
                            value={values.is_calculated}
                            selectedValue={values.is_calculated}
                            onChange={handleChange}
                            isRequired={true}
                            options={typeOptions}
                            error={errors.is_calculated}
                        />
                        <div className="-mb-3">
                            <FormTextArea title={'description'} isRequired={true} onChange={handleChange} value={values.description} error={errors.description} />
                        </div>
                        <small className="italic text-gray-600">Bisa diisi soal atau deskripsi tugas yang harus dikerjakan oleh peserta berikut standart pengumpulannya</small>
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
}

export default Edit;
