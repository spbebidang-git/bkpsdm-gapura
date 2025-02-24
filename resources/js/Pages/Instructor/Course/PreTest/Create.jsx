import FormCheckbox from "@/Components/FormCheckbox";
import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormTextArea from "@/Components/FormTextArea";
import NumberActivity from "@/Components/NumberActivity";
// import QuillEditor from "@/Components/QuillEditor";
import TitleActivity from "@/Components/TitleActivity";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";



const Create = ({ slug }) => {
    const { errors } = usePage().props
    // const [content, setContent] = useState('');


    const [values, setValues] = useState({
        title: "",
        description: "",
        instruction: "",
        value: "",
        timer: 0,
        min_score: 0,
        repeatable: false
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
        router.post(`/instructor/course/${slug}/activity/pre-test`, values)
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
                        <FormInput title={'timer'} isRequired={true} onChange={handleChange} value={values.timer} error={errors.timer} placeholder={'Time in minutes'} />
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
                        <FormInput title={'min score'} isRequired={true} onChange={handleChange} value={values.min_score} error={errors.min_score} />
                        <FormCheckbox
                            id="repeatable"
                            title={'Pengulangan?'}
                            onChange={handleChange}
                            checked={values.repeatable}
                        />
                    </div>
                </div>

                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
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