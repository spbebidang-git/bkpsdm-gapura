import BreadcrumbLearn from "@/Components/BreadcrumbLearn"
import CountdownTimer from "@/Components/CountDownTimer"
import ListActivity from "@/Components/ListActivity"
import { Link, usePage } from "@inertiajs/react"

const Learn = ({ type, course, progress, activity, children, activities }) => {
    const { site } = usePage().props
    const { errors } = usePage().props

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
                        <div className="h-[74px] bg-orange-50  flex items-center px-6 justify-between">
                            <div className="xl:text-xl font-bold text-gray-800 capitalize">{activity.title}</div>
                            {/* timer */}
                            {(activity.type == 'pre-test' || activity.type == 'post-test') && type == 'material' ? (<CountdownTimer />) : ''}
                        </div>
                        {/* Detail Activity */}
                        <div className='mx-2 my-4 xl:m-6 h-full flex flex-col border-b'>
                            <div className="flex gap-2.5 mb-4">
                                <BreadcrumbLearn title='Instruksi' activeTab={type == 'instruction' ? true : false} icon="book-bookmark" ></BreadcrumbLearn>
                                <BreadcrumbLearn title='Materi' activeTab={type == 'material' ? true : false} icon="book-bookmark" ></BreadcrumbLearn>
                                <BreadcrumbLearn title='Hasil' activeTab={type == 'result' ? true : false} icon="book-bookmark" ></BreadcrumbLearn>
                            </div>

                            <div className="flex-1">
                                {children}
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-span-12 xl:col-span-3 p-5 xl:p-0 bg-teal-50 xl:bg-white">
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

export default Learn