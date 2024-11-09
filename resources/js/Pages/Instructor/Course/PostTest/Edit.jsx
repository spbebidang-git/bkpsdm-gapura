import FormCheckbox from "@/Components/FormCheckbox";
import FormInput from "@/Components/FormInput";
import FormTextArea from "@/Components/FormTextArea";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";

const Edit = ({ slug, activity }) => { // Tambahkan activity untuk membawa data yang akan diedit
    const { errors } = usePage().props

    const [values, setValues] = useState({
        title: activity?.title || "",
        description: activity?.description || "",
        min_score: activity?.min_score || 0,
        is_loop: activity?.is_loop || false,
        value: activity?.value || ""
    });

    useEffect(() => {
        if (activity) {
            setValues({
                title: activity.title,
                description: activity.description,
                min_score: activity.min_score,
                is_loop: activity.is_loop,
                value: activity.value
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

    function handleSubmit(e) {
        e.preventDefault();
        router.put(`/instructor/course/${slug}/activity/post-test/${activity.id}`, values); // Menggunakan PUT untuk edit
    }

    return (
        <App>
            <NumberActivity number={3} />
            <TitleActivity number={3} title={'Edit Post-Test / Quiz'} />

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
                        <FormInput title={'title'} isRequired={true} onChange={handleChange} value={values.title} error={errors.title} />
                        <FormTextArea title={'description'} isRequired={true} onChange={handleChange} value={values.description} error={errors.description} />
                        <FormInput title={'min score'} isRequired={true} onChange={handleChange} value={values.min_score} error={errors.min_score} />
                        <FormInput title={'value'} isRequired={true} onChange={handleChange} value={values.value} error={errors.value} />
                        <FormCheckbox
                            id="is_loop"
                            title={'Pengulangan? (Maksimal pengulangan untuk post-test adalah 4x)'}
                            onChange={handleChange}
                            checked={values.is_loop}
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
    );
}

export default Edit;
