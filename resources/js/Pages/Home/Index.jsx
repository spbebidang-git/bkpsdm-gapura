import { Link } from '@inertiajs/react';
import App from '../../Layouts/App';

export default function Home({ courses }) {
    return (
        <App>
            {/* Slide */}
            <div className="xl:h-[512px]">
                <img src="https://dummyimage.com/1000x512/000/ffffff" alt="#"
                    className="w-full h-full object-cover" />
            </div>
            {/* About Course */}
            <div className="max-w-6xl mx-auto p-5">
                <div className="grid grid-cols-3 gap-0 xl:gap-5">
                    <div className="p-3 col-span-3 lg:col-span-1">
                        <div className="h-36 xl:h-52">
                            <img className="object-contain w-full h-full" src="https://dummyimage.com/600x300/000/fff"
                                alt="" />
                        </div>
                        <div className="px-2 py-1">
                            <div className="text-xl font-bold text-center text-gray-700 py-1">
                                Pemateri Berpengalaman
                            </div>
                            <div className="text-center text-gray-500">
                                <p>Materi dibawakan
                                    oleh pemateri yang
                                    berpengalaman</p>
                            </div>
                        </div>
                    </div>
                    <div className="p-3 col-span-3 lg:col-span-1">
                        <div className="h-36 xl:h-52">
                            <img className="object-contain w-full h-full" src="https://dummyimage.com/600x300/000/fff"
                                alt="" />
                        </div>
                        <div className="px-2 py-1">
                            <div className="text-xl font-bold text-center text-gray-700 py-1">
                                Pemateri Berpengalaman
                            </div>
                            <div className="text-center text-gray-500">
                                <p>Materi dibawakan
                                    oleh pemateri yang
                                    berpengalaman</p>
                            </div>
                        </div>
                    </div>
                    <div className="p-3 col-span-3 lg:col-span-1">
                        <div className="h-36 xl:h-52">
                            <img className="object-contain w-full h-full" src="https://dummyimage.com/600x300/000/fff"
                                alt="" />
                        </div>
                        <div className="px-2 py-1">
                            <div className="text-xl font-bold text-center text-gray-700 py-1">
                                Pemateri Berpengalaman
                            </div>
                            <div className="text-center text-gray-500">
                                <p>Materi dibawakan
                                    oleh pemateri yang
                                    berpengalaman</p>
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
                                                <div class="flex justify-center pt-3">
                                                    <div class="flex">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5 lg:h-6 lg:w-6 font-bold text-gray-300" viewBox="0 0 20 20"
                                                            fill="currentColor">
                                                            <path
                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5 lg:h-6 lg:w-6 font-bold text-gray-300" viewBox="0 0 20 20"
                                                            fill="currentColor">
                                                            <path
                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5 lg:h-6 lg:w-6 font-bold text-gray-300" viewBox="0 0 20 20"
                                                            fill="currentColor">
                                                            <path
                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5 lg:h-6 lg:w-6 font-bold text-gray-300" viewBox="0 0 20 20"
                                                            fill="currentColor">
                                                            <path
                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-5 w-5 lg:h-6 lg:w-6 font-bold text-gray-300" viewBox="0 0 20 20"
                                                            fill="currentColor">
                                                            <path
                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                    </div>
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