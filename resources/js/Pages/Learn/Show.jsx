import Activity from "@/Components/Activity"
import ListActivity from "@/Components/ListActivity"
import { Link, usePage } from "@inertiajs/react"

const Home = ({ course, progress, activities, activity, activity_id, question_result }) => {
    const { site } = usePage().props

    return (
        <>
            <div className="grid grid-cols-12 max-h-screen min-h-screen">
                <div className="col-span-9 bg-teal-50 flex flex-col">
                    <div className="h-14 p-5 bg-white shadow flex items-center justify-between">
                        <div className="flex gap-8 items-center">
                            <div className="flex items-center">
                                <Link href={`/my-course`}>
                                    <box-icon name='left-arrow-circle' size="md"></box-icon>
                                </Link>
                            </div>
                            {/* <div className="text-2xl font-bold uppercase border-r border-gray-300 pr-8">Logo</div> */}
                            <div className='h-9'>
                                <img src={site.logo} alt="" className='w-full h-full object-contain' />
                            </div>
                            <div className="text-xl font-bold">{course.title}</div>
                        </div>
                        <div className="text-xl font-bold text-gray-800 flex items-center gap-1">
                            <box-icon name='hourglass-top' type='solid' color='#018083' ></box-icon>
                            {progress}
                        </div>
                    </div>
                    <div className="flex-1 m-8 rounded-xl shadow bg-white overflow-hidden">
                        <div className="h-16 bg-orange-50  flex items-center px-6 justify-between">
                            <div className="text-xl font-bold text-gray-800 capitalize">{activity.title}</div>
                            <div className="rounded-full border-2 bg-teal-50 w-fit px-6 py-1.5 font-semibold text-primary border-primary flex items-center gap-1">
                                <box-icon name='book-bookmark' color='#018083' ></box-icon>
                                Materi
                            </div>
                        </div>

                        {/* Detail Activity */}
                        <div className='m-8'>
                            <div className="overflow-scroll h-[700px] px-3">
                                <Activity question_result={question_result} slug={course.slug} id={activity_id} type={activity.type} data={activity.data} status={activity.status} />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-span-3 bg-white">
                    <div className="text-gray-800 flex justify-center items-center font-bold text-xl border-b-2 h-14 cursor-pointer">
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