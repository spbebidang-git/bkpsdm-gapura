import FormInput from "@/Components/FormInput";
import FormSelect from "@/Components/FormSelect";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";

const Edit = ({ slug, activityId, variant }) => {
    const { errors } = usePage().props;

    const [values, setValues] = useState({
        code: "",
        variant: "",
        mix: 0,
    });

    function handleChange(e) {
        const { id, value } = e.target;
        setValues((values) => ({
            ...values,
            [id]: value,
        }));
    }

    function handleSubmit(e) {
        e.preventDefault();
        router.put(
            `/instructor/course/${slug}/activity/variant/${activityId}/${variant.id}`,
            values
        );
    }

    const typeOptions = [
        { value: 0, label: "Tidak" },
        { value: 1, label: "Ya" },
    ];

    useEffect(() => {
        setValues({
            code: variant.code,
            variant: variant.variant,
            mix: variant.mix,
        });
    }, [variant]);

    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">
                Edit Variant
            </div>

            <form onSubmit={handleSubmit}>
                <div className="max-w-2xl mx-auto shadow bg-white rounded-xl">
                    <div className="p-6">
                        <FormInput
                            title="code"
                            label="Kode"
                            value={values.code}
                            onChange={handleChange}
                            isRequired={true}
                            error={errors.code}
                        />

                        <FormInput
                            title="variant"
                            label="Variant"
                            value={values.variant}
                            onChange={handleChange}
                            isRequired={false}
                            error={errors.variant}
                            readOnly={true}
                        />

                        <FormSelect
                            title="mix"
                            label="Acak Soal"
                            value={values.mix}
                            selectedValue={values.mix}
                            onChange={handleChange}
                            isRequired={true}
                            options={typeOptions}
                            error={errors.mix}
                        />
                    </div>
                </div>

                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Simpan</button>

                    <Link
                        className="w-full"
                        href={`/instructor/course/${slug}/activity/variant/${activityId}`}
                    >
                        <button className="button-secondary">Kembali</button>
                    </Link>
                </div>
            </form>
        </App>
    );
};

export default Edit;
