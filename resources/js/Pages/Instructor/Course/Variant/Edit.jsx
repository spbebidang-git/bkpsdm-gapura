import ActivityFormLayout from "@/Components/ActivityFormLayout";
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
            <ActivityFormLayout
                slug={slug}
                type="variant"
                title="Edit Variasi"
                description="Ubah konfigurasi pada variasi soal ini."
                isEdit={true}
            />

            <form onSubmit={handleSubmit}>
                <div className="max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-8">
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
                            label="Judul Variasi"
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

                <div className="max-w-3xl mx-auto flex flex-col sm:flex-row justify-end gap-3 mt-8">
                    <Link href={`/instructor/course/${slug}/activity`} className="w-full sm:w-auto">
                        <button type="button" className="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-semibold transition-colors">
                            Kembali
                        </button>
                    </Link>
                    <button type="submit" className="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-primary text-white hover:bg-primary/90 font-semibold shadow-sm transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </App>
    );
};

export default Edit;
