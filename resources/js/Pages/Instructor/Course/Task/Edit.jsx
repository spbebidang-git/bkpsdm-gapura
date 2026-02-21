import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormSelect from "@/Components/FormSelect";
import FormTextArea from "@/Components/FormTextArea";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
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
            <NumberActivity number={3} />
            <TitleActivity number={3} title={'Task'} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
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

                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Save</button>
                    <Link className="w-full" href={`/instructor/course/${slug}`}>
                        <button className="button-secondary">Back</button>
                    </Link>
                </div>
            </form>
        </App>
    );
}

export default Edit;
