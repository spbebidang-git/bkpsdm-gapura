import FormCheckbox from "@/Components/FormCheckbox";
import FormInput from "@/Components/FormInput";
import FormTextArea from "@/Components/FormTextArea";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const Create = ({ slug }) => {
    const { errors } = usePage().props

    const [values, setValues] = useState({
        title: "",
        description: "",
        min_score: 0,
        show_result: false,
        value: ''
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
        router.post(`/instructor/course/${slug}/activity/post-test`, values)
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={'Setting Pre-Test / Quiz'} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
                        <FormInput title={'title'} isRequired={true} onChange={handleChange} value={values.title} error={errors.title} />
                        <FormTextArea title={'description'} isRequired={true} onChange={handleChange} value={values.description} error={errors.description} />
                        <FormInput title={'value'} isRequired={true} onChange={handleChange} value={values.value} error={errors.value} />
                        <FormInput title={'min score'} isRequired={true} onChange={handleChange} value={values.min_score} error={errors.min_score} />
                        <FormCheckbox
                            id="show_result"
                            title={'Show result in certificate?'}
                            onChange={handleChange}
                            checked={values.show_result}
                        />
                    </div>
                </div>

                <div className="flex gap-3 max-w-lg mx-auto pt-8">
                    <button className="button-primary">Save</button>
                    <Link className="w-full" href={`/instructor/course/${slug}/activity`}>
                        <button className="button-secondary">Back</button>
                    </Link>
                </div>
            </form>
        </App>
    )
}

export default Create