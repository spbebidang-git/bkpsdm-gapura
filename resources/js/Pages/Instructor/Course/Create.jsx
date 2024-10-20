import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormSelect from "@/Components/FormSelect";
import FormTextArea from "@/Components/FormTextArea";
import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import ModalAddSchedule from "./Partials/ModalAddSchedule";

const Create = ({ courses, categories }) => {

    const [type, setType] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const { errors } = usePage().props

    const toggleModal = () => {
        setIsModalOpen(!isModalOpen);
    };

    const typeOptions = [
        { value: 'unschedule', label: 'Unschedule' },
        { value: 'schedule', label: 'Schedule' }
    ];

    const statusOptions = [
        { value: 'publish', label: 'Published' },
        { value: 'draft', label: 'Draft' }
    ];

    const typeCategories = categories.map(item => ({
        value: item.id,
        label: item.title
    }));

    const handleTypeChange = (e) => {
        const selectedValue = e.target.value;
        if (selectedValue === "Schedule") {
            setType(1);
        } else {
            setType(0);
        }
    };

    const [values, setValues] = useState({
        type: "",
        category: "",
        title: "",
        short_description: "",
        description: "",
        thumbnail: null,
        teaser_video: "",
    })

    const handleChange = (e) => {
        const { id, type, value, checked, files } = e.target;

        setValues((values) => ({
            ...values,
            [id]: type === 'checkbox' ? checked : (type === 'file' ? files[0] : value),
        }));

        if (id === 'type') {
            if (value === 'schedule') {
                setType(1);
            } else {
                setType(0);
            }
        }
    }

    const handleSubmit = (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('type', values.type);
        formData.append('category', values.category);
        formData.append('title', values.title);
        formData.append('short_description', values.short_description);
        formData.append('description', values.description);
        formData.append('thumbnail', values.thumbnail);
        formData.append('teaser_video', values.teaser_video);

        const localData = localStorage.getItem('scheduleData');
        if (values.type == 'schedule' && localData) {
            formData.append('schedules', localData)
        }

        router.post(`/instructor/course`, values, {
            onSuccess: page => {
                toast.success('🦄 Wow so easy!', {
                    position: "top-right",
                    autoClose: 5000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    progress: undefined,
                    theme: "light",
                    transition: Bounce,
                });
            },
        });
    }

    const [data, setData] = useState([]);

    useEffect(() => {
        const localData = localStorage.getItem('scheduleData');
        if (localData) {
            setData(JSON.parse(localData));
        }
    }, []);

    const handleScheduleAdded = (newSchedule) => {
        setData(prevData => {
            const updatedData = [...prevData, newSchedule];
            localStorage.setItem('scheduleData', JSON.stringify(updatedData));
            return updatedData;
        });
    };

    const handleDeleteSchedule = (id) => {
        const updatedData = data.filter(item => item.id !== id);
        setData(updatedData);
        localStorage.setItem('scheduleData', JSON.stringify(updatedData));
    };
    return (
        <App>
            <form onSubmit={handleSubmit}>
                <div className="max-w-4xl mx-auto gap-5 bg-white rounded-xl shadow">
                    <h4 className="p-5 border-b text-center text-xl font-bold">Add New Course</h4>
                    <div className="p-6">
                        <div>
                            {/* type */}
                            <FormSelect title={'type'} value={values.type} onChange={handleChange} isRequired={true} options={typeOptions} error={errors.type} />
                            {/* category */}
                            <FormSelect title={'category'} value={values.category} onChange={handleChange} isRequired={true} options={typeCategories} error={errors.category} />
                            {/* title */}
                            <FormInput title={'title'} value={values.title} onChange={handleChange} isRequired={true} error={errors.title} />
                            {/* short description */}
                            <FormTextArea title={'short description'} value={values.short_description} onChange={handleChange} isRequired={true} error={errors.short_description} />
                            {/* description */}
                            <FormTextArea title={'description'} value={values.description} onChange={handleChange} isRequired={true} error={errors.description} />
                            {/* teaser video */}
                            <FormInput title={'teaser video'} value={values.teaser_video} onChange={handleChange} isRequired={true} error={errors.teaser_video} />
                            {/* thumbnail */}
                            <div className="mb-3">
                                <FormLabel title={'thumbnail'} isRequired={true} />
                                <div className="flex items-center justify-center w-full">
                                    <label htmlFor="thumbnail" className={`flex flex-col items-center justify-center w-full h-64 border-2 ${errors.thumbnail ? 'border-red-500' : 'border-gray-300'} border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100`}>
                                        <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg className="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                                <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                                            </svg>
                                            <p className="mb-2 text-sm text-gray-500"><span className="font-semibold">Click to upload</span> or drag and drop</p>
                                            <p className="text-xs text-gray-500">SVG, PNG, JPG or GIF (MAX. 800x400px)</p>
                                        </div>
                                        <input id="thumbnail" type="file" onChange={handleChange} className="hidden" />
                                    </label>
                                </div>
                                {errors.thumbnail && <small className="text-red-500 text-sm mt-1">{errors.thumbnail}</small>}
                            </div>
                            {/* status */}
                            <FormSelect title={'status'} value={values.status} onChange={handleChange} isRequired={true} options={statusOptions} error={errors.status} />
                        </div>
                    </div>
                </div>
                {type ? (
                    <div>
                        <div className="max-w-4xl mx-auto gap-5 bg-white rounded-xl shadow mt-5">
                            <div className="flex justify-between border-b p-5 items-center">
                                <h4 className="text-lg font-bold w-full">Set Schedules</h4>
                                <button type="button" className="button-primary max-w-56" onClick={toggleModal}>
                                    Add New Schedule
                                </button>
                            </div>
                            <div className="p-5">
                                <div>
                                    <table className="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr className="sticky top-0 z-10">
                                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 dark:bg-gray-700">
                                                    #
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 dark:bg-gray-700">
                                                    Date
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 dark:bg-gray-700">
                                                    Time Period
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 dark:bg-gray-700">
                                                    Quota
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 dark:bg-gray-700">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.length > 0 ? (
                                                data.map((item, index) => (
                                                    <tr key={index} className="border-b dark:border-gray-700">
                                                        <td className="px-6 py-4 text-center">{index + 1}</td>
                                                        <td className="px-6 py-4 text-center">{item.date}</td>
                                                        <td className="px-6 py-4 text-center">{`${item.time_from} - ${item.time_to}`}</td>
                                                        <td className="px-6 py-4 text-center">{item.quota}</td>
                                                        <td className="px-6 py-4 text-center">
                                                            {/* Tambahkan tombol atau aksi di sini */}
                                                            <button type="button" className="text-red-600 hover:text-red-900" onClick={() => handleDeleteSchedule(item.id)}>Delete</button>
                                                        </td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td colSpan="100%" className="text-center py-4">No data available</td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                    </div>
                ) : ''}
                <div className="max-w-80 mx-auto gap-3 flex justify-center mt-8">
                    <button className="button-primary">Save</button>
                    <Link href="/instructor" className="button-secondary">Back</Link>
                </div>
            </form>

            <ModalAddSchedule isOpen={isModalOpen} onClose={toggleModal} onScheduleAdded={handleScheduleAdded} />


        </App>
    )
}

export default Create