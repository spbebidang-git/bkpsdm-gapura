import StartRating from '@/Components/StartRating';
import { useState } from 'react';
import { ToastContainer } from 'react-toastify';
import App from '../../Layouts/App';

import { Link, usePage } from '@inertiajs/react';
import 'react-toastify/dist/ReactToastify.css';

const Show = ({ course, schedules, reviews }) => {
    const [activeSchedules, setActiveSchedules] = useState([]);

    const { auth } = usePage().props


    const handleActivation = async (e, scheduleId) => {
        e.preventDefault()

        try {
            const response = await axios.post(`/course/${scheduleId}`, []);

            if (response.data.status === 200) {
                setActiveSchedules(prevState => [...prevState, scheduleId]);
            }
        } catch (error) {
            console.log(error)
        }
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
                                Beranda
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
                    <div className='col-span-1'>
                        <div className='h-48 bg-red-200 rounded-lg overflow-hidden shadow'>
                            <img src={course.thumbnail} alt={course.title} className='w-full h-full object-contain' />
                        </div>
                    </div>
                    <div className='col-span-2'>
                        <div className='text-xl font-bold text-gray-800 mb-0.5 capitalize'>{course.title}</div>
                        <div className='text-sm italic mb-1 text-gray-700'>oleh <span className='not-italic font-bold'>{course.instructor.name}</span></div>
                        <div className='text-base flex gap-1 items-center mb-1 '>
                            <div className='flex'>
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
                            <div className='font-extrabold'>
                                5.0
                            </div>
                            <div className='font-medium'>
                                (1000 Rating)
                            </div>
                        </div>
                        <div className='mb-2.5 text-gray-700 text-sm'>{course.short_description}</div>
                        <div className='flex gap-3'>
                            <button className='bg-purple-500 text-sm shadow text-white font-medium  px-5 py-2.5 rounded-full'>Teaser Video</button>
                            <button className='bg-purple-500 text-sm shadow text-white font-medium  px-5 py-2.5 rounded-full'>Description</button>
                            <button className='bg-purple-500 text-sm shadow text-white font-medium  px-5 py-2.5 rounded-full'>Schedule</button>
                            <button className='bg-purple-500 text-sm shadow text-white font-medium  px-5 py-2.5 rounded-full'>Review</button>
                        </div>
                    </div>
                </div>
                {/* Jadwal Kegiatan */}
                <div className="bg-white p-6 rounded-xl shadow mb-5">
                    <div className='text-lg font-bold mb-5'>Jadwal Kegiatan</div>
                    {schedules.map((item, index) => {
                        return (
                            <div key={item.id} className='ring-8 ring-purple-300 rounded-xl overflow-hidden shadow'>
                                <div className=' shadow rounded-lg overflow-hidden'>
                                    <div className='px-5 border-b-2 pb-4 font-medium text-lg flex justify-between items-center my-4'>{course.title}
                                        <div>
                                            {activeSchedules.includes(item.id) || item.is_active ? (
                                                <Link href={`/my-course`}>
                                                    <button className='bg-purple-500 text-sm shadow text-white font-bold px-5 py-2 rounded-full'>Lihat Kelas</button>
                                                </Link>
                                            ) : <form onSubmit={(e) => handleActivation(e, item.id)}
                                            >
                                                <button className='bg-purple-500 text-sm shadow text-white font-bold px-5 py-2 rounded-full'>Aktivasi Kelas</button>
                                            </form>}
                                        </div>
                                    </div>
                                    <div className='mb-4 px-5 grid grid-cols-4 gap-5'>
                                        <div className='flex items-center gap-1.5  text-sm text-gray-600 font-medium'>
                                            <box-icon name='calendar' color='#d8b4fe' size="sm"></box-icon>
                                            <div className=''>{item.date}</div>
                                        </div>
                                        <div className='flex items-center gap-1.5  text-sm text-gray-600 font-medium'>
                                            <box-icon name='time' color='#d8b4fe' size="sm"></box-icon>
                                            <div className=''>{item.time}</div>
                                        </div>
                                        <div className='flex items-center gap-1.5  text-sm text-gray-600 font-medium'>
                                            <box-icon name='user-circle' color='#d8b4fe' size="sm"></box-icon>
                                            <div className=''>{course.instructor}</div>
                                        </div>
                                        <div className='flex items-center gap-1.5  text-sm text-gray-600 font-medium'>
                                            <box-icon name='check-circle' color='#d8b4fe' size="sm"></box-icon>
                                            <div className='capitalize'>{course.quota}</div>
                                        </div>
                                    </div>
                                    <div className='bg-gray-200 p-2'>
                                    </div>
                                </div>
                            </div>
                        )
                    })}
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
                                <div className='flex gap-1.5 items-center mb-1'>
                                    <div className=''>
                                        <StartRating />
                                    </div>
                                    <div className='text-gray-700'>
                                        <span className='font-bold'>{item.name}</span> | <span>{item.created_at}</span>
                                    </div>
                                </div>
                                <div className=''>
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
