import Activity from '@/Components/Activity';
import Learn from '@/Layouts/Participant/Learn';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from "react-toastify";


const Index = ({ course, progress, activities, activity, activity_id, question_result }) => {
    const [answers, setAnswers] = useState({});
    const [file, setFile] = useState(null);
    const [activeButton, setActiveButton] = useState(false)
    const [duration, setDuration] = useState(activity.data.duration)

    useEffect(() => {

        if (activity.type == 'video' || activity.type == 'literature') {
            // setDuration(data.duration)
            if (duration === 0) {
                setActiveButton(true)
                return;
            }
            // Atur interval untuk menghitung mundur
            const interval = setInterval(() => {
                setDuration(prevDuration => {
                    if (prevDuration > 1) {
                        return prevDuration - 1;
                    } else {
                        clearInterval(interval); // Hentikan interval saat durasi mencapai 0
                        setActiveButton(true); // Aktifkan tombol
                        return 0;
                    }
                });
            }, 1000);

            // Bersihkan interval ketika komponen unmount
            return () => clearInterval(interval);
        } else {
            setActiveButton(true)
        }

    }, [duration]);

    const handleSubmit = (e, slug) => {
        e.preventDefault();

        // store pre test or post test
        if (activity.type == 'pre-test' || activity.type == 'post-test') {
            const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));
            router.post(route('participant.learn.store', { course: slug, schedule: 1, activity: activity_id }), formattedAnswers, {
                onError: (errors) => {
                    toast.error(errors.error, {
                        position: "top-center",
                        autoClose: 5000,
                        hideProgressBar: false,
                        closeOnClick: true,
                        pauseOnHover: true,
                        draggable: true,
                        theme: "colored",
                    });
                },
            });
        }

        // store task
        if (activity.type == 'task') {
            if (!file) {
                alert("Please select a file first");
                return;
            }

            // Membuat FormData
            const formData = new FormData();
            formData.append('file', file);  // Tambahkan file ke formData
            formData.append('id', 123);  // Tambahkan data lain jika diperlukan

            router.post(route('participant.learn.store', { course: slug, schedule: 1, activity: activity_id }), formData, {
                forceFormData: true, // Pastikan formData dikirim sebagai multipart/form-data
                onSuccess: () => {
                    alert("File uploaded successfully");
                },
                onError: () => {
                    alert("File upload failed");
                }
            });
        }

        // store meeting
        if (activity.type == 'meeting') {
            router.post(route('participant.learn.store', { course: slug, schedule: 1, activity: activity_id }), []);
        }

        // store video
        if (activity.type == 'video') {
            const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));
            router.post(route('participant.learn.store', { course: slug, schedule: 1, activity: activity_id }), formattedAnswers);
        }

        // store literature
        if (activity.type == 'literature') {
            const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));
            router.post(route('participant.learn.store', { course: slug, schedule: 1, activity: activity_id }), formattedAnswers);
        }

        // toast.error('Kamu belum memenuhi nilai yang ditetapkan. Silakan ulangi post-test untuk mencoba lagi', {
        //     position: "top-center",
        //     autoClose: 5000,
        //     hideProgressBar: false,
        //     closeOnClick: true,
        //     pauseOnHover: true,
        //     draggable: true,
        //     theme: "colored",
        // });
    };

    return <Learn type="material" course={course} progress={progress} activities={activities} activity={activity}>
        <div className="flex flex-col justify-between gap-3 h-auto ">
            <div className='h-[562px] bg-gray-50 py-2.5 overflow-scroll'>
                <div>
                    <Activity question_result={question_result} slug={course.slug} id={activity_id} type={activity.type} data={activity.data} status={activity.status} answers={answers} file={file} setFile={setFile}
                        setAnswers={setAnswers} />
                </div>
            </div>
            <div className='h-14 flex items-center w-56 mx-auto'>

                {activeButton ? <button onClick={(e) => handleSubmit(e, course.slug)} className='button-primary'>Kirim Jawaban</button>
                    : <button className="button-disable max-w-56">
                        Selanjutnya
                    </button>}
            </div>
        </div>
    </Learn>
}

export default Index