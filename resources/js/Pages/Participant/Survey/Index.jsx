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
        <div className="grid grid-cols-12 max-h-screen min-h-screen">
            <div className="col-span-12 bg-purple-50 flex flex-col">
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
                <div className="flex-1 m-8 rounded-xl shadow-sm bg-white overflow-hidden">
                    <div className="h-14 bg-purple-100 flex items-center px-6">
                        <div className="text-xl font-bold">Survey Kepuasan Pengguna</div>
                    </div>

                    {/* Detail Activity */}
                    <div className='m-8 text-gray-700 flex flex-col items-center'>
                        <div className="mb-5">
                            <h4 className="text-3xl font-bold text-center mb-2">Survey Kepuasan Pengguna GAPURA</h4>
                            <p className="max-w-2xl text-center">
                                Terima kasih telah mengikuti kelas online ini. Kami berharap pengalaman belajar Anda menyenangkan dan bermanfaat. Sebagai bagian dari upaya kami untuk terus meningkatkan kualitas layanan, kami memohon Anda untuk meluangkan beberapa menit untuk mengisi Survey Kepuasan Pengguna GAPURA.
                            </p>
                        </div>
                        <div className="flex flex-col items-center text-gray-500 text-sm italic mb-5">
                            <div className="mb-2">
                                <a className="text-blue-500 text-xl" href="http://bit.ly/SurveiKepuasanPenggunaGAPURA" target="_blank">
                                    http://bit.ly/SurveiKepuasanPenggunaGAPURA
                                </a>
                            </div>
                            (Klik link diatas untuk mengisi survey kepuasan pengguna gapura.)
                        </div>
                        {/* <p className="font-medium mb-5">
                            Terima kasih atas masukan Anda!
                        </p> */}
                        <div className="max-w-sm w-full">
                            <Link href={route('participant.course.index')}>
                                <button className="button-primary">Saya Sudah Mengisi Survey</button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default Index