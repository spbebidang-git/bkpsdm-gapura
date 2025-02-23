import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";
import { useEffect, useState } from "react";
import Swal from "sweetalert2";
const Show = ({ course, activities }) => {
    const [dataActivities, setDataActivities] = useState([]);

    useEffect(() => {
        setDataActivities(activities)
    }, [])

    const handleEndMeeting = (e, activityId) => {
        e.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: "This data will be permanently updated!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete!',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                router.post(route('instructor.course.activity.meeting.end-meeting', [course.slug, activityId]));

                setDataActivities(prevData => prevData.map(activity =>
                    activity.id === activityId ? { ...activity, end_meeting: 1 } : activity
                ));
            }
        });
    }

    const handleSubmitDelete = (e, activityId) => {
        e.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: "This data will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete!',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(`/instructor/course/${course.slug}/activity/${activityId}`, {
                    onSuccess: () => {
                        router.get(`/instructor/course/${course.slug}`);
                        Swal.fire(
                            'Deleted!',
                            'Data deleted successfully.',
                            'success'
                        );
                    },
                });
            }
        });
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
                                        <td className="px-6 py-4 text-center flex justify-end w-96 gap-2">
                                            <div className=" w-full flex gap-2 justify-end h-8">
                                                {item.type === 'pre-test' || item.type == 'post-test' ? (
                                                    <div className="flex gap-2">
                                                        {!item.has_imported ? (
                                                            <Link href={route('instructor.course.activity.import-question.index', { course: course.slug, activity: item.id })}>
                                                                <button className="w-20 h-7 text-sm font-medium bg-green-500 text-white rounded-full shadow">
                                                                    Import
                                                                </button>
                                                            </Link>
                                                        ) : ('')}
                                                        <Link href={`/instructor/course/${course.slug}/activity/variant/${item.id}`}>
                                                            <button className="w-20 h-7 text-sm font-medium bg-primary text-white rounded-full shadow">
                                                                Variant
                                                            </button>
                                                        </Link>
                                                    </div>
                                                ) : ''}
                                                {item.type === 'meeting' && item.end_meeting === 0 ? (
                                                    <button onClick={(e) => handleEndMeeting(e, item.id)} className="w-20 h-7 text-sm font-medium bg-primary text-white rounded-full shadow">
                                                        End Meet
                                                    </button>
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

export default Show