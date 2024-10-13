import FormInput from "@/Components/FormInput";
import FormTextArea from "@/Components/FormTextArea";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";
import { useState } from "react";

const Create = ({ slug }) => {

    const [values, setValues] = useState({
        title: "",
        description: "",
    })

    function handleChange(e) {
        const { id, type, value, checked } = e.target;
        setValues((values) => ({
            ...values,
            [id]: type === 'checkbox' ? checked : value,
        }));
    }

    function handleSubmit(e) {
        e.preventDefault()
        router.post(`/instructor/course/${slug}/activity/task`, values)
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={'Task'} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
                        <FormInput title={'title'} isRequired={true} onChange={handleChange} value={values.title} />
                        <div className="-mb-3">
                            <FormTextArea title={'description'} isRequired={true} onChange={handleChange} value={values.description} />
                        </div>
                        <small className="italic text-gray-600">Bisa diisi soal atau deskripsi tugas yang harus dikerjakan oleh peserta berikut standart pengumpulannya</small>
                    </div>
                </div>

                <div className="flex gap-3 max-w-lg mx-auto pt-8">
                    <button className="button-primary">Save</button>
                    <Link className="w-full" href={`/instructor/course/${slug}/activity`}>
                        <button className="button-secondary">Back to Activity</button>
                    </Link>
                </div>
            </form>
        </App>
    )
}

export default Create