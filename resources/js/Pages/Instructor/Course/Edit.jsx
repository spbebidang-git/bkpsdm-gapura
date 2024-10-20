import FormInput from "@/Components/FormInput";
import FormLabel from "@/Components/FormLabel";
import FormSelect from "@/Components/FormSelect";
import FormTextArea from "@/Components/FormTextArea";
import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";
import { useEffect, useState } from "react";
import ModalAddSchedule from "./Partials/ModalAddSchedule";

const Edit = ({ course, categories }) => {
    const [type, setType] = useState(course.type === 'schedule' ? 1 : 0); // Set type based on existing data
    const [isModalOpen, setIsModalOpen] = useState(false);

    const toggleModal = () => {
        setIsModalOpen(!isModalOpen);
    };

    const typeOptions = [
        { value: 'unschedule', label: 'Unschedule' },
        { value: 'schedule', label: 'Schedule' }
    ];

    const typeCategories = categories.map(item => ({
        value: item.id,
        label: item.title
    }));

    const handleTypeChange = (e) => {
        const selectedValue = e.target.value;
        if (selectedValue === "schedule") {
            setType(1);
        } else {
            setType(0);
        }
    };

    // Prepopulate form with course
    const [values, setValues] = useState({
        type: course.type || "unschedule",
        category: course.category_id || "",
        title: course.title || "",
        short_description: course.short_description || "",
        description: course.description || "",
        thumbnail: null, // Handle file input separately
        teaser_video: course.teaser_video || "",
    });

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

        // Send PUT request to update the course
        router.post(`/instructor/course/${course.slug}`, { _method: 'put', values });
    };

    const [data, setData] = useState(course.schedules || []); // Prepopulate schedules

    useEffect(() => {
        const localData = localStorage.getItem('scheduleData');
        if (localData) {
            setData(JSON.parse(localData));
        }

        setValues({
            type: course.type || 'unschedule',
            category: course.category || "",
            title: course.title || "",
            short_description: course.short_description || "",
            description: course.description || "",
            thumbnail: null,  // Untuk file upload, ini harus tetap null sampai user mengubahnya
            teaser_video: course.teaser_video || ""
        });
    }, [course]);

    const handleScheduleAdded = (newSchedule) => {
        setData((prevData) => {
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
                    <h4 className="p-5 border-b text-center text-xl font-bold">Edit Course</h4>
                    <div className="p-6">
                        <div>
                            {/* type */}
                            <FormSelect title={'type'} value={values.type} onChange={handleChange} isRequired={true} options={typeOptions} />
                            {/* category */}
                            <FormSelect title={'category'} value={values.category} onChange={handleChange} isRequired={true} options={typeCategories} />
                            {/* title */}
                            <FormInput title={'title'} value={values.title} onChange={handleChange} isRequired={true} />
                            {/* short description */}
                            <FormTextArea title={'short description'} value={values.short_description} onChange={handleChange} isRequired={true} />
                            {/* description */}
                            <FormTextArea title={'description'} value={values.description} onChange={handleChange} isRequired={true} />
                            <div className="mb-3">
                                <FormLabel title={'thumbnail'} />
                                <div className="flex items-center justify-center w-full">
                                    <label htmlFor="thumbnail" className="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                                        <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg className="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                                <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                                            </svg>
                                            <p className="mb-2 text-sm text-gray-500 dark:text-gray-400"><span className="font-semibold">Click to upload</span> or drag and drop</p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">SVG, PNG, JPG or GIF (MAX. 800x400px)</p>
                                        </div>
                                        <input id="thumbnail" type="file" onChange={handleChange} className="hidden" />
                                    </label>
                                </div>
                            </div>
                            {/* teaser video */}
                            <FormInput title={'teaser video'} value={values.teaser_video} onChange={handleChange} isRequired={true} />
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
                                                    Time
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 dark:bg-gray-700">
                                                    Delete
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.map((item, index) => (
                                                <tr key={item.id}>
                                                    <td className="px-6 py-4 text-center">{index + 1}</td>
                                                    <td className="px-6 py-4 text-center">{item.date}</td>
                                                    <td className="px-6 py-4 text-center">{item.time}</td>
                                                    <td className="px-6 py-4 text-center">
                                                        <button type="button" onClick={() => handleDeleteSchedule(item.id)} className="font-medium text-red-600 hover:underline dark:text-red-500">
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <ModalAddSchedule isOpen={isModalOpen} onClose={toggleModal} onScheduleAdded={handleScheduleAdded} />
                    </div>
                ) : null}

                <div className="flex justify-end max-w-4xl mx-auto mt-10">
                    <Link href="/instructor/course" className="button-secondary px-10">Back</Link>
                    <button type="submit" className="button-primary px-10 ml-2">Update</button>
                </div>
            </form>
        </App>
    );
};

export default Edit;
