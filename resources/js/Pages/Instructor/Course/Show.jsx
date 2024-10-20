import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";
import { useEffect, useState } from "react";

const Create = ({ course, activities }) => {
    const [dataActivities, setDataActivities] = useState([]);

    useEffect(() => {
        setDataActivities(activities)
    }, [])

    const handleSubmitDelete = (e, activityId) => {
        e.preventDefault()
        router.delete(`/instructor/course/${course.slug}/activity/${activityId}`)
    }

    return (
        <App>
            <div className="max-w-6xl mx-auto">
                <div className="grid grid-cols-3 gap-5 mb-5">
                    <div className='col-span-1'>
                        <div className='h-48 rounded-xl overflow-hidden shadow'>
                            <img src={course.thumbnail} alt={course.title} />
                        </div>
                    </div>
                    <div className='col-span-2'>
                        <div className='text-xl font-bold text-gray-800 mb-0.5'>{course.title}</div>
                        <div className='text-sm italic mb-1 text-gray-700'>oleh <span className='not-italic font-bold'>{course.instructor}</span></div>

                        <div className='mb-2.5 text-gray-700'>{course.short_description}</div>
                    </div>
                </div>
                <div className="col-span-1 border-2 rounded-xl shadow">
                    <div className="flex items-center p-5 border-b">
                        <h4 className="w-full title-card">Activities</h4>
                        <Link href={`/instructor/course/${course.slug}/activity`} className="max-w-44 text-center button-primary">Add New Activity</Link>
                    </div>

                    <table className="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                            <tr className="sticky top-0 z-10">
                                <th scope="col" className="px-6 py-3 text-center  dark:bg-gray-700">
                                    #
                                </th>
                                <th scope="col" className="px-6 py-3 text-center  dark:bg-gray-700">
                                    Type
                                </th>
                                <th scope="col" className="px-6 py-3 text-center  dark:bg-gray-700">
                                    Title
                                </th>
                                <th scope="col" className="px-6 py-3 text-center  dark:bg-gray-700">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {dataActivities.length > 0 ? (
                                dataActivities.map((item, index) => (
                                    <tr key={index} className="border-b dark:border-gray-700">
                                        <td className="px-6 py-4 text-center">{index + 1}</td>
                                        <td className="px-6 py-4 text-center capitalize whitespace-nowrap">{item.type}</td>
                                        <td className="px-6 py-4 text-center font-medium text-gray-800 w-full">{item.title}</td>
                                        <td className="px-6 py-4 text-center flex justify-end w-80 gap-2">
                                            <div className=" w-full flex gap-2 justify-end h-8">
                                                {item.type === 'pre-test' || item.type == 'post-test' ? (
                                                    <Link href={`/instructor/course/${course.slug}/activity/variant/${item.id}`}>
                                                        <button className="w-20 h-7 text-sm font-medium bg-primary text-white rounded-full shadow">
                                                            Variant
                                                        </button>
                                                    </Link>
                                                ) : ''}
                                                <Link href={`/instructor/course/${course.slug}/activity/${item.type}/${item.id}/edit`}>
                                                    <button className="button-warning">
                                                        Edit
                                                    </button>
                                                </Link>
                                                <button onClick={(e) => handleSubmitDelete(e, item.id)} className="button-danger">Delete</button>
                                            </div>
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
        </App>
    )
}

export default Create