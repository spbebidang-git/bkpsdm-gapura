import StarRating from '@/Components/StarRating';
import { Link } from '@inertiajs/react';
import App from '../../Layouts/App';

export default function Home({ courses }) {
    return (
        <App>
            {/* Slide */}
            <div className="xl:h-[512px]">
                <img src="/assets/media/images/static/banner.jpg" alt="#"
                    className="w-full h-full object-cover" />
            </div>
            {/* About Course */}
            <div className="max-w-6xl mx-auto p-5">
                <div className="grid grid-cols-3 gap-0 xl:gap-5">
                    <div className="p-3 col-span-3 lg:col-span-1">
                        <div className="h-36 xl:h-52">
                            <img className="object-contain w-full h-full" src="/assets/media/images/static/pemateri.png"
                                alt="" />
                        </div>
                        <div className="px-2 py-1">
                            <div className="text-xl font-bold text-center text-gray-700 py-1">
                                Pemateri Berpengalaman
                            </div>
                            <div className="text-center text-gray-600">
                                <p>Materi dibawakan oleh pemateri yang berpengalaman</p>
                            </div>
                        </div>
                    </div>
                    <div className="p-3 col-span-3 lg:col-span-1">
                        <div className="h-36 xl:h-52">
                            <img className="object-contain w-full h-full" src="/assets/media/images/static/materi-berkualitas.png"
                                alt="" />
                        </div>
                        <div className="px-2 py-1">
                            <div className="text-xl font-bold text-center text-gray-700 py-1">
                                Materi Berkualitas
                            </div>
                            <div className="text-center text-gray-600">
                                <p>Materi yang diberikan relevan dengan kebutuhan dunia kerja</p>
                            </div>
                        </div>
                    </div>
                    <div className="p-3 col-span-3 lg:col-span-1">
                        <div className="h-36 xl:h-52">
                            <img className="object-contain w-full h-full" src="/assets/media/images/static/fleksibel.png"
                                alt="" />
                        </div>
                        <div className="px-2 py-1">
                            <div className="text-xl font-bold text-center text-gray-700 py-1">
                                Fleksibel
                            </div>
                            <div className="text-center text-gray-600">
                                <p>Pembelajaran dapat dilakukan dimana dan kapan saja</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {/* List of Courses */}
            <div className='bg-gray-100 p-8'>
                <div className="max-w-7xl mx-auto">
                    <div className='font-bold text-xl text-gray-700 mb-3'>Materi Pilihan</div>
                    <div className='grid grid-cols-4 gap-5'>
                        {courses.length > 0 ? (
                            courses.map((item, index) => {
                                return (
                                    <div class="bg-white rounded-lg overflow-hidden col-span-4 lg:col-span-1 flex flex-row lg:flex-col">
                                        <Link href={'course/' + item.slug}>
                                            <div class="w-full overflow-hidden">
                                                <img class="w-full h-full object-fill" src={item.thumbnail}
                                                    alt={item.title} />
                                            </div>
                                            <div class="p-3 w-full">
                                                <div
                                                    class="font-semibold text-black text-sm lg:text-base leading-normal hover:text-primary">
                                                    {item.title}
                                                </div>
                                                <div class="text-xs lg:text-sm text-gray-400 italic mb-2">
                                                    by
                                                    <span class="font-medium text-gray-500"> {item.instructor}</span>
                                                </div>
                                                <div className='flex flex-col gap-1 text-sm text-gray-700 font-medium'>
                                                    <div className='flex gap-2 items-center'>
                                                        <div className='w-7 h-7 border rounded-full'>
                                                            <img src="/assets/media/images/static/peserta-icon.png" alt="" />
                                                        </div>
                                                        <div><b>{item.total_participant}</b> Peserta</div>
                                                    </div>
                                                    <div className='flex gap-2 items-center'>
                                                        <div className='w-7 h-7 border rounded-full'>
                                                            <img src="/assets/media/images/static/lulus-icon.png" alt="" />
                                                        </div>
                                                        <div><b>{item.total_passed}</b> Peserta Selesai</div>
                                                    </div>
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