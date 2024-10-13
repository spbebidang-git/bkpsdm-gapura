import FormSelect from "@/Components/FormSelect";
import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";
import { useEffect, useState } from "react";

const Edit = ({ slug, activityId, variant }) => {

    const [values, setValues] = useState({
        loop: 0,
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
        router.put(`/instructor/course/${slug}/activity/variant/${activityId}/${variant.id}`, values)
    }

    const typeOptions = [
        { value: 0, label: 'Tidak' },
        { value: 1, label: 'Ya' }
    ];

    useEffect(() => {
        setValues({
            loop: variant.loop,
            mix: variant.mix
        });
    }, [variant])

    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">Edit Variant</div>

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
                        <FormSelect title={'loop'} value={values.type} onChange={handleChange} isRequired={true} options={typeOptions} />
                        <FormSelect title={'mix'} value={values.type} onChange={handleChange} isRequired={true} options={typeOptions} />
                    </div>
                </div>

                <div className="flex gap-3 max-w-lg mx-auto pt-8">
                    <button className="button-primary">Save</button>
                    <Link className="w-full" href={`/instructor/course/${slug}/activity/variant/${activityId}`}>
                        <button className="button-secondary">Back</button>
                    </Link>
                </div>
            </form>
        </App>
    )
}

export default Edit