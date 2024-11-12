import Activity from "@/Components/Activity"
import ListActivity from "@/Components/ListActivity"
import { Link, usePage } from "@inertiajs/react"
import { toast } from "react-toastify"

const Home = ({ course, progress, activities, activity, activity_id, question_result }) => {
    const { site } = usePage().props
    const { errors } = usePage().props

    if (errors.error) {
        // console.log(errors);
        toast.error(errors.error, {
            position: "top-right",
            autoClose: 3000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
            theme: "colored",
            // transition: Bounce,
        });
    }
    return (
        <>
            <div className="grid grid-cols-12 max-h-screen min-h-screen">
                <div className="col-span-12 xl:col-span-9 bg-teal-50 flex flex-col">
                    <div className="h-14 p-5 bg-white shadow flex items-center justify-between">
                        <div className="flex gap-8 items-center">
                            <div className="flex items-center mt-1 xl:mt-0">
                                <Link href={`/my-course`}>
                                    <box-icon name='left-arrow-circle' size="md"></box-icon>
                                </Link>
                            </div>
                            <div className='h-9 flex-1'>
                                <img src={site.logo} alt="" className='w-full h-full object-contain' />
                            </div>
                            <div className="text-xl font-bold hidden xl:block">{course.title}</div>
                        </div>
                        <div className="text-xl font-bold text-gray-800 flex items-center gap-1">
                            <box-icon name='hourglass-top' type='solid' color='#018083' ></box-icon>
                            {progress}
                        </div>
                    </div>
                    <div className="text-base font-bold xl:hidden max-w-sm text-center text-gray-800 w-full mt-5 p-3 rounded-xl shadow bg-white mx-auto">{course.title}</div>
                    <div className="flex-1 m-5 xl:m-8 rounded-xl shadow bg-white overflow-hidden">
                        <div className="h-16 bg-orange-50  flex items-center px-6 justify-between">
                            <div className="xl:text-xl font-bold text-gray-800 capitalize">{activity.title}</div>
                            <div className="rounded-full border-2 bg-teal-50 w-fit px-6 py-1.5 font-semibold text-primary border-primary flex items-center gap-1">
                                <box-icon name='book-bookmark' color='#018083' ></box-icon>
                                Materi
                            </div>
                        </div>
                        {/* Detail Activity */}
                        <div className='mx-2 my-4 xl:m-8'>
                            <div className="overflow-scroll h-[700px] xl:px-3">
                                <Activity question_result={question_result} slug={course.slug} id={activity_id} type={activity.type} data={activity.data} status={activity.status} />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-span-12 xl:col-span-3 p-5 bg-teal-50">
                    <div className="text-gray-800 bg-white flex justify-center items-center font-bold text-xl border-b-2 h-14 cursor-pointer">
                        Agenda Kegiatan
                    </div>
                    <div className="bg-white p-5">
                        <div className="text-lg font-medium">
                            <ul className="list-outside list-decimal">
                                {activities && activities.map((item, index) => {
                                    return (
                                        <ListActivity type={item.type} slug={course.slug} activityId={item.id} activity={item.title} status={item.status} isActive={item.id == activity.activity_id ? 1 : 0} />
                                    )
                                })}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </>
    )
}

export default Home