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
        url: "",
        duration: "",
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
        router.post(`/instructor/course/${slug}/activity/presentation-video`, values)
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={'Task'} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
                        <FormInput title={'title'} isRequired={true} onChange={handleChange} value={values.title} />
                        <FormTextArea title={'description'} isRequired={true} onChange={handleChange} value={values.description} />
                        <FormInput title={'url'} isRequired={true} onChange={handleChange} value={values.url} />
                        <FormInput title={'duration'} isRequired={true} onChange={handleChange} value={values.duration} />
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