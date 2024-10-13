import Activity from "@/Components/Activity"
import ListActivity from "@/Components/ListActivity"
import { Link, usePage } from "@inertiajs/react"

const Home = ({ course, activities, activity, activity_id }) => {
    const { site } = usePage().props

    console.log(activity)
    return (
        <>
            <div className="grid grid-cols-12 max-h-screen min-h-screen">
                <div className="col-span-9 bg-purple-50 flex flex-col">
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
                        <div className="text-xl font-bold">58,88 %</div>
                    </div>
                    <div className="flex-1 m-8 rounded-xl shadow-sm bg-white overflow-hidden">
                        <div className="h-14 bg-purple-100 flex items-center px-6">
                            <div className="text-xl font-bold">{course.title}</div>
                        </div>

                        {/* Detail Activity */}
                        <div className='m-8'>
                            <div className="flex gap-3">
                                {/* <div className="rounded-full border-2 bg-purple-100 w-fit px-10 py-2.5 font-semibold border-purple-500">
                                    Detail Aktivitas
                                </div> */}
                                <div className="rounded-full border-2 bg-purple-100 w-fit px-10 py-2.5 font-semibold border-purple-500 mb-5">
                                    Materi
                                </div>
                            </div>
                            <div className="overflow-scroll h-[550px] border-2 mb-5">
                                <Activity id={activity_id} type={activity.type} data={activity.data} />
                            </div>
                            <div>
                                <button className="button-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-span-3 bg-white p-4">
                    <div className="h-14 border-b-2"></div>
                    <div className="bg-white shadow rounded-xl border-2">
                        <div className="text-lg font-medium">
                            <ul className="list-outside list-decimal">
                                {activities && activities.map((item, index) => {
                                    return (
                                        <ListActivity type={item.type} activity={item.title} isActive={0} />
                                    )
                                })}
                                {/* <div className="pb-3 my-3 border-gray-200">
                                    <div className="w-fit mb-1 px-4 flex items-center justify-center text-[10px] font-extrabold bg-purple-500 text-white rounded-full shadow">
                                        MATERI
                                    </div>
                                    <li className="">
                                        <div className="mb-0.5">Selamat Datang Di SEKOLAHMU!</div>
                                        <div className="flex items-center gap-1 text-xs text-gray-500 italic">
                                            <box-icon name='play-circle' size="sm" color="gray" ></box-icon>
                                            Menonton Video Pemaparan
                                        </div>
                                    </li>
                                </div> */}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </>
    )
}

export default Home