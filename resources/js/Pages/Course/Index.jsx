import StarRating from "@/Components/StarRating"
import App from "@/Layouts/App"
import { Link } from "@inertiajs/react"

const Index = ({ courses }) => {
    return (
        <App>
            <div className='bg-gray-100 min-h-screen p-8'>
                <div className="max-w-7xl mx-auto">
                    <div className='font-bold text-2xl text-gray-700 mb-6 text-center'>Materi Pilihan</div>
                    <div className='grid grid-cols-3 gap-5'>
                        {courses.length > 0 ? (
                            courses.map((item, index) => {
                                return (
                                    <div className="bg-white rounded-lg overflow-hidden col-span-4 lg:col-span-1 flex flex-row lg:flex-col relative">
                                        <Link href={'course/' + item.slug}>
                                            <div className="lg:h-36 w-2/5 lg:w-full overflow-hidden relative">
                                                {/* Label di pojok kanan atas */}
                                                <div className="absolute top-2.5 right-2.5 bg-secondary text-white text-[10px] font-bold text-sm px-2 rounded">
                                                    {item.employee_status}
                                                </div>

                                                <img className="w-full h-full object-cover" src={item.thumbnail} alt={item.title} />
                                            </div>
                                            <div className="p-3 w-full">
                                                <div className="font-semibold text-black text-sm lg:text-base leading-normal hover:text-primary">
                                                    {item.title}
                                                </div>
                                                <div className="text-xs lg:text-sm text-gray-400 italic">
                                                    by
                                                    <span className="font-medium text-gray-500"> {item.instructor}</span>
                                                </div>
                                                <div className="flex justify-center p-2.5">
                                                    <StarRating value={item.star} />
                                                </div>
                                            </div>
                                        </Link>
                                    </div>


                                )
                            })
                        ) : ('kosong')}
                    </div>
                </div>
            </div>
        </App>
    )
}

export default Index