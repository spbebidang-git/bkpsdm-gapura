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
                                    <div class="bg-white rounded-lg overflow-hidden col-span-4 lg:col-span-1 flex flex-row lg:flex-col">
                                        <Link href={'course/' + item.slug}>
                                            <div class="lg:h-36 w-2/5 lg:w-full overflow-hidden">

                                                <img class="w-full h-full object-cover" src={item.thumbnail}
                                                    alt={item.title} />
                                            </div>
                                            <div class="p-3 w-full">
                                                <div
                                                    class="font-semibold text-black text-sm lg:text-base leading-normal hover:text-primary">
                                                    {item.title}
                                                </div>
                                                <div class="text-xs lg:text-sm text-gray-400 italic">
                                                    by
                                                    <span class="font-medium text-gray-500"> {item.instructor}</span>
                                                </div>
                                                <div className='flex justify-center p-2.5'>
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