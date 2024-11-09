import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const Edit = ({ slug, activity }) => {
    const { errors } = usePage().props

    const [values, setValues] = useState({
        title: activity?.title || "",
        sub_title: activity?.sub_title || "",
        value: activity?.value || "",
        duration: activity?.duration || "",
        content: null,
    });

    function handleChange(e) {
        const { id, type, value, checked, files } = e.target;
        setValues((values) => ({
            ...values,
            [id]: type === 'checkbox' ? checked : (type === 'file' ? files[0] : value),
        }));
    }


    function handleSubmit(e) {
        e.preventDefault();

        router.post(`/instructor/course/${slug}/activity/literature/${activity.id}`, {
            _method: 'put', title: values.title,
            sub_title: values.sub_title,
            value: values.value,
            content: values.content,
            duration: values.duration,
        });
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={'Literature'} />

            <form onSubmit={handleSubmit} encType="multipart/form-data">
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
                        <FormInput title={'title'} isRequired={true} onChange={handleChange} value={values.title} error={errors.title} />
                        <FormInput title={'sub title'} isRequired={true} onChange={handleChange} value={values.sub_title} error={errors.sub_title} />
                        <FormInput title={'value'} isRequired={true} onChange={handleChange} value={values.value} error={errors.value} />
                        <FormInput title={'duration'} isRequired={true} onChange={handleChange} value={values.duration} error={errors.duration} />
                        <div className="mb-3">
                            <FormLabel isRequired={true} title={'content'} />
                            <div className="flex items-center justify-center w-full">
                                <label htmlFor="content" className={`flex flex-col items-center justify-center w-full h-64 border-2 ${errors.content ? 'border-red-500' : 'border-gray-300'} border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100`}>
                                    <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg className="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                            <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                                        </svg>
                                        <p className="mb-2 text-sm text-gray-500 dark:text-gray-400"><span className="font-semibold">Click to upload</span> or drag and drop</p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">PDF (MAX. 10MB)</p>
                                    </div>
                                    <input id="content" type="file" onChange={handleChange} className="hidden" />
                                </label>
                            </div>
                            {errors.content && <small className="text-red-500 text-sm mt-1">{errors.content}</small>}
                        </div>
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
    );
}

export default Edit;
