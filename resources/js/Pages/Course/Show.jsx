import { useState } from 'react';
import { ToastContainer } from 'react-toastify';
import App from '../../Layouts/App';

import StarRating from '@/Components/StarRating';
import { Link, router, usePage } from '@inertiajs/react';
import 'react-toastify/dist/ReactToastify.css';

const Show = ({ course, schedules, reviews, rating }) => {
    const [activeSchedules, setActiveSchedules] = useState([]);

    const { auth } = usePage().props

    const handleActivation = (e, scheduleId) => {
        e.preventDefault()

        router.post(`/course/${course.slug}/${scheduleId}`, [], {
            onSuccess: page => {
                setActiveSchedules(prevState => [...prevState, scheduleId]);
            },
            onError: errors => {
                console.log(123);
            },
        });
    }

    return (
        <App>
            <div className='max-w-7xl mx-auto p-6'>
                <ToastContainer />
                {/* Breadcrumb */}
                <nav class="flex mb-4" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                        <li class="inline-flex items-center">
                            <a href="#" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z" />
                                </svg>
                                <span>Beranda</span>
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Materi Pilihan</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400 capitalize">{course.title}</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                {/* Course */}
                <div className="grid grid-cols-3 gap-5 mb-5">
                    <div className='col-span-3 xl:col-span-1'>
                        <div className='h-48 w-full rounded-lg overflow-hidden shadow'>
                            <img src={course.thumbnail} alt={course.title} className='w-full h-full object-fill' />
                        </div>
                    </div>
                    <div className='col-span-3 xl:col-span-2'>
                        <div className='text-xl font-bold text-gray-800 mb-0.5 capitalize'>{course.title}</div>
                        <div className='text-sm italic mb-1 text-gray-700'>oleh <span className='not-italic font-bold'>{course.instructor}</span></div>
                        <div className='text-base flex gap-1 items-center mb-1 '>
                            <StarRating value={rating.star} />
                            <div className='font-extrabold'>
                                {rating.value}
                            </div>
                            <div className='font-medium'>
                                ({rating.total} Rating)
                            </div>
                        </div>
                        <div className='mb-2.5 text-gray-700 text-sm'>{course.short_description}</div>
                        <div className='flex flex-col xl:flex-row max-w-full w-full xl:max-w-xl gap-3'>
                            <button className='button-primary'>Schedule</button>
                            <button className='button-primary'>Teaser Video</button>
                            <button className='button-primary'>Description</button>
                            <button className='button-primary'>Review</button>
                        </div>
                    </div>
                </div>
                {/* Jadwal Kegiatan */}
                <div className="bg-white p-6 rounded-xl shadow mb-5">
                    <div className='text-lg font-bold mb-5'>Jadwal Kegiatan</div>
                    <div className='ring-8 ring-teal-200 rounded-xl overflow-hidden shadow'>
                        {schedules.map((item, index) => {
                            return (
                                <div className='shadow overflow-hidden'>
                                    <div className='px-5 border-b-2 pb-4 font-medium xl:text-lg flex flex-col xl:flex-row gap-3 xl:justify-between items-center my-4'>{course.title}
                                        <div>
                                            {activeSchedules.includes(item.id) || item.is_active ? (
                                                <Link href={`/my-course`}>
                                                    <button className='button-primary'>Lihat Kelas</button>
                                                </Link>
                                            ) : <form onSubmit={(e) => handleActivation(e, item.id)}
                                            >
                                                <button className='button-primary'>Aktivasi Kelas</button>
                                            </form>}
                                        </div>
                                    </div>
                                    <div className='mb-4 px-5 grid grid-cols-4 gap-5'>
                                        <div className='col-span-4 xl:col-span-1 flex items-center gap-1.5  text-sm text-gray-600 font-medium'>
                                            <box-icon name='calendar' color='#5eead4' size="sm"></box-icon>
                                            <div className=''>{item.date}</div>
                                        </div>
                                        <div className='col-span-4 xl:col-span-1 flex items-center gap-1.5  text-sm text-gray-600 font-medium'>
                                            <box-icon name='time' color='#5eead4' size="sm"></box-icon>
                                            <div className=''>{item.time}</div>
                                        </div>
                                        <div className='col-span-4 xl:col-span-1 flex items-center gap-1.5  text-sm text-gray-600 font-medium'>
                                            <box-icon name='user-circle' color='#5eead4' size="sm"></box-icon>
                                            <div className=''>{course.instructor}</div>
                                        </div>
                                        <div className='col-span-4 xl:col-span-1 flex items-center gap-1.5  text-sm text-gray-600 font-medium'>
                                            <box-icon name='check-circle' color='#5eead4' size="sm"></box-icon>
                                            <div className='capitalize'>{course.quota}</div>
                                        </div>
                                    </div>
                                    <div className='bg-gray-200 p-2'>
                                    </div>
                                </div>
                            )
                        })}
                    </div>
                </div>

                {/* Teaser Video */}
                <div className="bg-white p-6 rounded-xl shadow mb-5">
                    <div className='text-lg font-bold mb-3'>Teaser Video</div>
                    <div className=" flex justify-center">
                        <div className="w-full aspect-video xl:h-[512px] rounded-xl shadow overflow-hidden">
                            <iframe
                                src={course.teaser_video}
                                width="100%"
                                height="100%"
                                title="PDF Viewer"
                            ></iframe>
                        </div>
                    </div>
                </div>

                {/* Description */}
                <div className="bg-white p-6 rounded-xl shadow mb-5">
                    <div className='text-lg font-bold'>Tentang Kelas Ini</div>
                    <div className='text-gray-700 py-2.5'>
                        {course.description}
                    </div>
                </div>

                {/* Description */}
                <div className="bg-white p-6 rounded-xl shadow">
                    <div className='text-lg font-bold mb-5'>Ulasan Peserta</div>
                    {reviews ? reviews.map((item, index) => {
                        return (
                            <div className='text-gray-700 pb-4 mb-4'>
                                <div className='flex flex-col xl:flex-row gap-1.5 items-start xl:items-center mb-1'>
                                    <div className=''>
                                        <StarRating value={item.star} />
                                    </div>
                                    <div className='text-gray-700'>
                                        <span className='font-bold'>{item.name}</span> | <span className='text-gray-500 text-sm italic'>{item.created_at}</span>
                                    </div>
                                </div>
                                <div className='text-gray-600'>
                                    {item.description}
                                </div>
                            </div>
                        )
                    }) : (<div className='text-center border-2 p-5 text-gray-600 rounded-xl'>Ulasan peserta belum tersedia.</div>)}

                </div>
            </div>
        </App>
    );
};

export default Show;
