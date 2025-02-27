import BreadcrumbLearn from "@/Components/BreadcrumbLearn";
import CountdownTimer from "@/Components/CountDownTimer";
import ListActivity from "@/Components/ListActivity";
import { Link, router, usePage } from "@inertiajs/react";
import React from "react";

const Learn = ({ type, course, progress, activity, children, activities, timer, activity_id, formattedAnswers }) => {
    const { site } = usePage().props
    const { errors } = usePage().props

    const handleTimeout = () => {
        router.post(route('participant.learn.store', { course: course.slug, schedule: 1, activity: activity_id }), formattedAnswers, {
            onError: (errors) => {
                toast.error(errors.error, {
                    position: "top-center",
                    autoClose: 5000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    theme: "colored",
                });
            },
        });
    };

    return (
        <div class="flex h-screen min-h-screen flex-col">
            <div class="flex flex-grow flex-col lg:flex-row">
                <div class="flex h-screen flex-grow flex-col bg-teal-50 lg:h-auto">
                    <div className="h-14 p-5 bg-white shadow flex items-center justify-between">
                        <div className="flex gap-5 items-center">
                            <div className="flex items-center mt-1 xl:mt-0">
                                <div className="w-8 h-8 flex items-center justify-center">
                                    <Link href={`/my-course`} className="flex">
                                        <box-icon name='left-arrow-circle' size="md"></box-icon>
                                    </Link>
                                </div>
                            </div>
                            <div className='h-9 flex-1'>
                                <img src={site.logo} alt="" className='w-full h-full object-contain' />
                            </div>
                            <div className="text-xl font-bold text-gray-700 hidden xl:block">{course.title}</div>
                        </div>
                        <div className="text-xl font-bold text-gray-800 flex items-center gap-1">
                            <box-icon name='hourglass-top' type='solid' color='#018083' ></box-icon>
                            {progress}
                        </div>
                    </div>
                    <div className="flex-1 m-5 xl:m-5 rounded-xl shadow bg-white overflow-hidden">
                        <div className="h-[74px] bg-orange-50  flex items-center px-6 justify-between">
                            <div className="xl:text-xl font-bold text-gray-800 capitalize">{activity.title}</div>
                            {/* timer */}
                            {(activity.type == 'pre-test' || activity.type == 'post-test') && type == 'material' ? (<CountdownTimer initialSeconds={timer} onTimeout={handleTimeout} />) : ''}
                        </div>
                        {/* Detail Activity */}
                        <div className='mx-2 my-4 xl:m-6 h-full flex flex-col border-b'>
                            <div className="flex gap-2.5 mb-4">
                                <BreadcrumbLearn title='Instruksi' activeTab={type == 'instruction' ? true : false} icon="book-bookmark" ></BreadcrumbLearn>
                                <BreadcrumbLearn title='Materi' activeTab={type == 'material' ? true : false} icon="book-bookmark" ></BreadcrumbLearn>
                                <BreadcrumbLearn title='Hasil' activeTab={type == 'result' ? true : false} icon="book-bookmark" ></BreadcrumbLearn>
                            </div>

                            <div className="flex-1">
                                {React.Children.map(children, (child) =>
                                    child ? React.cloneElement(child, { onTimeout: handleTimeout }) : child
                                )}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-grow flex-col bg-red-200 lg:max-w-sm w-full">
                    <div className="text-gray-800 bg-white flex justify-center items-center font-bold text-xl h-14 cursor-pointer">
                        Agenda Kegiatan
                    </div>
                    {/* Kontainer dengan Scroll, Menyesuaikan Max Tinggi */}
                    <div className="flex-1 overflow-y-auto border-t" style={{ maxHeight: "calc(100vh - 56px)" }}>
                        <div className="bg-white p-5">
                            <div className="text-lg font-medium">
                                <ul className="list-outside list-decimal">
                                    {activities && activities.map((item, index) => {
                                        return (
                                            <ListActivity
                                                type={item.type}
                                                slug={course.slug}
                                                activityId={item.id}
                                                activity={item.title}
                                                status={item.status}
                                                isActive={item.id == activity.activity_id ? 1 : 0}
                                            />
                                        )
                                    })}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    )
}

export default Learn