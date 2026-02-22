import Activity from '@/Components/Activity';
import Learn from '@/Layouts/Participant/Learn';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from "react-toastify";


const Index = ({ course, progress, activities, activity, activity_id, question_result, schedule }) => {
    const [answers, setAnswers] = useState({});
    const [file, setFile] = useState(null);
    const [activeButton, setActiveButton] = useState(false)
    const [duration, setDuration] = useState(activity.data.duration)
    const [timer, setTimer] = useState(activity.timer)

    const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));


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
            localStorage.removeItem('countdownStartTime');

            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formattedAnswers, {
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
                toast.error("Please select a file first", {
                    position: "top-right",
                    autoClose: 3000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    theme: "colored",
                });
                return;
            }

            // Validasi: Hanya PDF yang diperbolehkan
            if (file.type !== 'application/pdf') {
                toast.error("Only PDF files are allowed", {
                    position: "top-right",
                    autoClose: 3000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    theme: "colored",
                });
                return;
            }

            // Validasi: Maksimal 10MB
            const maxSizeInBytes = 2 * 1024 * 1024; // 10MB
            if (file.size > maxSizeInBytes) {
                toast.error("File size must not exceed 2MB", {
                    position: "top-right",
                    autoClose: 3000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    theme: "colored",
                });
                return;
            }

            // Membuat FormData
            const formData = new FormData();
            formData.append('file', file);

            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formData, {
                forceFormData: true,
                onSuccess: () => {
                    toast.success("File uploaded successfully", {
                        position: "top-right",
                        autoClose: 3000,
                        hideProgressBar: false,
                        closeOnClick: true,
                        pauseOnHover: true,
                        draggable: true,
                        theme: "colored",
                    });
                },
                onError: () => {
                    toast.error("File upload failed", {
                        position: "top-right",
                        autoClose: 3000,
                        hideProgressBar: false,
                        closeOnClick: true,
                        pauseOnHover: true,
                        draggable: true,
                        theme: "colored",
                    });
                }
            });

        }

        // store meeting
        if (activity.type == 'meeting') {
            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), []);
        }

        // store video
        if (activity.type == 'video') {
            const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));
            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formattedAnswers);
        }

        // store literature
        if (activity.type == 'literature') {
            const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));
            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formattedAnswers);
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

    return <Learn type="material" course={course} progress={progress} activities={activities} activity={activity} timer={timer} formattedAnswers={formattedAnswers} activity_id={activity_id} schedule={schedule}>
        <div className="flex flex-col h-full bg-white">
            <div className="flex-1 rounded-xl bg-gray-50 p-3 lg:p-4 mb-4 shadow-inner border border-gray-100 flex flex-col min-h-0">
                <div className="flex-1 overflow-y-auto pr-2 flex flex-col">
                    <Activity question_result={question_result} slug={course.slug} id={activity_id} type={activity.type} data={activity.data} status={activity.status} answers={answers} file={file} setFile={setFile}
                        setAnswers={setAnswers} schedule={schedule} />
                </div>
            </div>
            <div className="w-full sm:w-64 mx-auto shrink-0 pb-1">
                {activeButton ? <button onClick={(e) => handleSubmit(e, course.slug)} className="w-full px-6 py-3.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:-translate-y-0.5 flex justify-center items-center gap-2">
                    Kirim Jawaban
                    <box-icon name="send" color="white" size="20px"></box-icon>
                </button>
                    : <button className="w-full px-6 py-3.5 bg-gray-300 text-gray-500 font-bold rounded-xl shadow-sm cursor-not-allowed flex justify-center items-center gap-2">
                        Selanjutnya
                        <box-icon name="time" color="#6b7280" size="20px"></box-icon>
                    </button>}
            </div>
        </div>
    </Learn>
}

export default Index