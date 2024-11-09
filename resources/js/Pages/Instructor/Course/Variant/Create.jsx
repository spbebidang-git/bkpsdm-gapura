import FormInput from "@/Components/FormInput";
import FormSelect from "@/Components/FormSelect";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const Create = ({ slug, activityId, variant }) => {
    const { errors } = usePage().props

    const [values, setValues] = useState({
        variant: variant,
        mix: 0,
    })

    function handleChange(e) {
        const { id, type, value, checked } = e.target;
        setValues((values) => ({
            ...values,
            [id]: value,
        }));
    }

    function handleSubmit(e) {
        e.preventDefault()
        router.post(`/instructor/course/${slug}/activity/variant/${activityId}`, values)
    }

    const typeOptions = [
        { value: 0, label: 'Tidak' },
        { value: 1, label: 'Ya' }
    ];

    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">Add New Variant</div>

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
                        <FormInput title={'variant'} value={values.variant} onChange={handleChange} isRequired={true} error={errors.variant} readOnly={true} />
                        <FormSelect title={'mix'} value={values.type} onChange={handleChange} isRequired={true} options={typeOptions} />
                    </div>
                </div>

                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Save</button>
                    <Link className="w-full" href={`/instructor/course/${slug}/activity/variant/${activityId}`}>
                        <button className="button-secondary">Back</button>
                    </Link>
                </div>
            </form>
        </App>
    )
}

export default Create