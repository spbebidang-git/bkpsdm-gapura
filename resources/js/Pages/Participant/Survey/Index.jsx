import { Link, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import { toast } from 'react-toastify';

const Index = ({ course, progress, initial_activity }) => {
    const { site } = usePage().props
    const [rating, setRating] = useState(0);
    const [description, setDescription] = useState('');

    const handleRating = (index) => {
        setRating(index); // Mengubah nilai rating saat bintang diklik
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        router.post(route('participant.rating.store', [course.slug, 1]), {
            rating,
            description
        }, {
            onSuccess: page => {
                toast.success('Data Saved Successfully.', {
                    position: "top-center",
                    autoClose: 5000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    progress: undefined,
                    theme: "light",
                });
            },
        });
    }

    return (
        <div className="grid grid-cols-12 min-h-screen">
            <div className="col-span-12 bg-purple-50 flex flex-col">
                <div className="p-3 sm:p-5 h-auto min-h-[56px] bg-white shadow flex items-center justify-between gap-4">
                    <div className="flex gap-3 sm:gap-8 items-center overflow-hidden flex-1">
                        <div className="flex items-center shrink-0">
                            <Link href={`/my-course`}>
                                <box-icon name='left-arrow-circle' size="md"></box-icon>
                            </Link>
                        </div>
                        {/* <div className="text-2xl font-bold uppercase border-r border-gray-300 pr-8">Logo</div> */}
                        <div className='h-7 sm:h-9 shrink-0'>
                            <img src={site.logo} alt="" className='w-full h-full object-contain' />
                        </div>
                        <div className="hidden sm:block text-sm sm:text-xl font-bold truncate">{course.title}</div>
                    </div>
                    <div className="text-sm sm:text-xl font-bold text-gray-800 flex items-center gap-1 shrink-0">
                        <box-icon name='hourglass-top' type='solid' color='#018083' ></box-icon>
                        {progress}
                    </div>
                </div>
                <div className="flex-1 m-4 sm:m-8 rounded-xl shadow-sm bg-white overflow-hidden">
                    {/* Judul Test di Mobile */}
                    <div className="sm:hidden h-auto min-h-[56px] shrink-0 bg-transparent flex flex-col items-center px-4 py-4 justify-center text-center text-lg font-bold text-gray-800 uppercase w-full border-b border-gray-100">
                        {course.title}
                    </div>

                    <div className="min-h-[56px] py-3 bg-purple-100 flex items-center px-4 sm:px-6">
                        <div className="text-xl font-bold">Survey Kepuasan Pengguna</div>
                    </div>

                    {/* Detail Activity */}
                    <div className='m-6 sm:m-8 text-gray-700 flex flex-col items-center'>
                        <div className="mb-5 px-2">
                            <h4 className="text-xl sm:text-3xl font-bold text-center mb-2">Survey Kepuasan Pengguna GAPURA</h4>
                            <p className="max-w-2xl text-center text-sm sm:text-base">
                                Terima kasih telah mengikuti kelas online ini. Kami berharap pengalaman belajar Anda menyenangkan dan bermanfaat. Sebagai bagian dari upaya kami untuk terus meningkatkan kualitas layanan, kami memohon Anda untuk meluangkan beberapa menit untuk mengisi Survey Kepuasan Pengguna GAPURA.
                            </p>
                        </div>
                        <div className="flex flex-col items-center text-gray-500 text-xs sm:text-sm italic mb-5 text-center">
                            <div className="mb-2 break-all max-w-[90vw] px-4">
                                <a className="text-blue-500 text-base sm:text-xl font-semibold hover:underline" href="http://bit.ly/SurveiKepuasanPenggunaGAPURA" target="_blank">
                                    http://bit.ly/SurveiKepuasanPenggunaGAPURA
                                </a>
                            </div>
                            (Klik link diatas untuk mengisi survey kepuasan pengguna gapura.)
                        </div>
                        {/* <p className="font-medium mb-5">
                            Terima kasih atas masukan Anda!
                        </p> */}
                        <div className="max-w-sm w-full px-4 sm:px-0">
                            <Link href={route('participant.course.index')} className="w-full inline-block">
                                <button className="button-primary w-full shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 text-sm md:text-base py-2.5 md:py-3 cursor-pointer">
                                    Saya Sudah Mengisi Survey
                                </button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default Index