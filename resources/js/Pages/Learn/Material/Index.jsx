import Activity from '@/Components/Activity';
import InteractiveContentLauncher from '@/Components/InteractiveContentLauncher';
import QuestionnaireModal from '@/Components/QuestionnaireModal';
import TestStepperModal from '@/Components/TestStepperModal';
import Learn from '@/Layouts/Participant/Learn';
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from "react-toastify";

const buildTimedActivityStorageKey = (scheduleId, activityId) => `materialCountdown_${scheduleId}_${activityId}`;

/**
 * Baca sisa hitung mundur yang tersimpan.
 *
 * Format lama menyimpan `startedAt` dan menghitung memakai jam dinding, jadi
 * waktu tetap berjalan walau materinya tidak dibuka. Nilai lama dikonversi
 * sekali ke sisa waktu supaya peserta yang sedang berjalan tidak kehilangan
 * waktu yang sudah mereka lalui.
 */
const readRemainingCountdown = (storageKey, initialDuration) => {
    if (initialDuration <= 0) return 0;

    let stored = null;

    try {
        stored = localStorage.getItem(storageKey);
    } catch {
        return initialDuration;
    }

    if (!stored) return initialDuration;

    const clamp = (value) => Math.min(Math.max(value, 0), initialDuration);

    try {
        const parsed = JSON.parse(stored);

        if (parsed?.initialDuration !== initialDuration) return initialDuration;
        if (typeof parsed.remaining === 'number') return clamp(parsed.remaining);
        if (typeof parsed.startedAt === 'number') {
            return clamp(initialDuration - (Math.floor(Date.now() / 1000) - parsed.startedAt));
        }

        return initialDuration;
    } catch {
        return initialDuration;
    }
};

const shuffle = (array) => [...array].sort(() => Math.random() - 0.5);

/**
 * Siapkan urutan soal satu kali di halaman, bukan di dalam Activity.
 *
 * `answers` dikunci oleh indeks soal, jadi hanya boleh ada satu urutan:
 * mengacak ulang saat komponennya dipasang kembali akan menautkan jawaban
 * yang sudah ada ke soal yang berbeda tanpa terlihat.
 */
const prepareTestQuestions = (items = [], mix) => {
    if (mix !== 1) return items;

    return shuffle(items).map((item) => ({
        ...item,
        option: Object.fromEntries(shuffle(Object.entries(item.option))),
    }));
};

const writeRemainingCountdown = (storageKey, remaining, initialDuration) => {
    try {
        localStorage.setItem(storageKey, JSON.stringify({ remaining, initialDuration }));
    } catch {
        // Penyimpanan penuh atau diblokir: hitung mundur tetap jalan di memori.
    }
};


const Index = ({ course, progress, activities, activity, activity_id, question_result, schedule }) => {
    const [answers, setAnswers] = useState({});
    const [file, setFile] = useState(null);
    const [duration, setDuration] = useState(activity.data?.duration ?? 0);
    const isCompletionGated = activity.type == 'h5p'
        || activity.type == 'scorm'
        || activity.type == 'video'
        || activity.type == 'literature';
    const isTimedActivity = activity.type == 'literature';
    const isQuestionnaire = activity.type == 'questionnaire';
    const isTest = activity.type == 'pre-test' || activity.type == 'post-test';
    const isExpandable = isQuestionnaire || isTest;
    const [isExpanded, setIsExpanded] = useState(false);
    const [testQuestions, setTestQuestions] = useState([]);
    const [isCompletingContent, setIsCompletingContent] = useState(false);
    const [isPlayerOpen, setIsPlayerOpen] = useState(false);
    const isSubmitEnabled = !isTimedActivity || duration === 0;
    const timedActivityStorageKey = buildTimedActivityStorageKey(schedule.id, activity_id);

    const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));

    useEffect(() => {
        const initialDuration = activity.data?.duration ?? 0;

        setDuration(isTimedActivity
            ? readRemainingCountdown(timedActivityStorageKey, initialDuration)
            : initialDuration);
    }, [activity.data?.duration, activity.type, activity_id, isTimedActivity, timedActivityStorageKey]);

    // Hitung mundur hanya berjalan selama modal terbuka, dan sisa waktunya
    // disimpan tiap detik. Menutup modal atau meninggalkan tab benar-benar
    // menjeda, bukan sekadar menunda pembacaan jam dinding seperti sebelumnya.
    // Penulisan dibatasi pada keadaan terbuka supaya berpindah aktivitas tidak
    // sempat menimpa sisa waktu milik aktivitas lain.
    useEffect(() => {
        if (!isTimedActivity || !isPlayerOpen) return;

        const initialDuration = activity.data?.duration ?? 0;
        if (initialDuration <= 0) return;

        writeRemainingCountdown(timedActivityStorageKey, duration, initialDuration);

        if (duration === 0) return;

        const timer = setTimeout(() => {
            setDuration(prevDuration => (prevDuration > 1 ? prevDuration - 1 : 0));
        }, 1000);

        return () => clearTimeout(timer);
    }, [activity.data?.duration, duration, isPlayerOpen, isTimedActivity, timedActivityStorageKey]);

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
            localStorage.removeItem(timedActivityStorageKey);
            const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));
            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formattedAnswers);
        }

        // store literature
        if (activity.type == 'literature') {
            localStorage.removeItem(timedActivityStorageKey);
            const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));
            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formattedAnswers);
        }

        // store kuesioner
        if (activity.type == 'questionnaire') {
            // Dikirim utuh sebagai objek berkunci id pertanyaan - bentuknya
            // bersarang (pilihan jamak & skala), bukan daftar rata.
            router.post(
                route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }),
                { answers },
                {
                    onError: () => {
                        toast.error("Masih ada pertanyaan yang belum terisi dengan benar.", {
                            position: "top-center",
                            autoClose: 5000,
                            theme: "colored",
                        });
                    },
                }
            );
        }

        // store h5p / scorm
        if (activity.type == 'h5p' || activity.type == 'scorm') {
            localStorage.removeItem(timedActivityStorageKey);
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

    useEffect(() => {
        setIsExpanded(false);
    }, [activity_id]);

    // Identitas question_items berubah tiap kunjungan Inertia, jadi mengulang
    // tes tetap mengacak ulang, sementara render biasa tidak.
    useEffect(() => {
        setTestQuestions(isTest
            ? prepareTestQuestions(activity.data?.question_items ?? [], activity.data?.mix)
            : []);
    }, [activity.data?.question_items, activity.data?.mix, isTest]);

    const handlePlayerOpenChange = useCallback((open) => setIsPlayerOpen(open), []);

    const handleInteractiveFinish = () => {
        if (isCompletingContent) return;

        setIsCompletingContent(true);

        router.post(
            route('participant.learn.store', {
                course: course.slug,
                schedule: schedule.id,
                activity: activity_id,
            }),
            {},
            {
                onSuccess: () => localStorage.removeItem(timedActivityStorageKey),
                onError: () => {
                    toast.error("Gagal menyelesaikan konten. Silakan coba lagi.", {
                        position: "top-center",
                    });
                },
                onFinish: () => setIsCompletingContent(false),
            }
        );
    };

    // Hanya satu salinan yang boleh ter-mount: pilihan ganda dikelompokkan
    // lewat atribut `name` per pertanyaan, jadi dua salinan yang hidup
    // bersamaan akan saling menghapus pilihan.
    const activityNode = (
        <Activity question_result={question_result} slug={course.slug} id={activity_id} type={activity.type} data={activity.data} status={activity.status} answers={answers} file={file} setFile={setFile}
            setAnswers={setAnswers} schedule={schedule} questionItems={testQuestions} />
    );

    return <Learn type="material" course={course} progress={progress} activities={activities} activity={activity} timer={activity.timer} displayTimerSeconds={duration} formattedAnswers={formattedAnswers} activity_id={activity_id} schedule={schedule}>
        <div className="flex flex-col h-full bg-white">
            <div className="flex-1 rounded-xl bg-gray-50 p-3 lg:p-4 mb-4 shadow-inner border border-gray-100 flex flex-col min-h-0">
                {isCompletionGated ? (
                    <InteractiveContentLauncher
                        activity={activity}
                        activityId={activity_id}
                        courseSlug={course.slug}
                        schedule={schedule}
                        questionResult={question_result}
                        answers={answers}
                        setAnswers={setAnswers}
                        file={file}
                        setFile={setFile}
                        onFinish={handleInteractiveFinish}
                        isFinishing={isCompletingContent}
                        isCompletedExternally={isTimedActivity && duration === 0}
                        onOpenChange={handlePlayerOpenChange}
                    />
                ) : (
                    <div className="flex min-h-0 flex-1 flex-col overflow-y-auto pr-2">
                        {isExpanded ? (
                            <div className="flex flex-1 items-center justify-center p-8 text-center text-sm text-gray-500">
                                Sedang dibuka di jendela besar.
                            </div>
                        ) : activityNode}
                    </div>
                )}
            </div>
            <div className={`mx-auto shrink-0 pb-1 w-full ${isExpandable ? 'sm:w-[34rem]' : 'sm:w-64'}`}>
                {isCompletionGated ? (
                    <p className="text-center text-xs text-gray-400">
                        Tombol Selesai muncul setelah konten dituntaskan
                    </p>
                ) : (
                    <div className="flex flex-col gap-2 sm:flex-row">
                        {isExpandable && (
                            <button type="button" onClick={() => setIsExpanded(true)} className="w-full px-6 py-3.5 border-2 border-primary text-primary hover:bg-primary/5 font-bold rounded-xl transition-all flex justify-center items-center gap-2">
                                Buka di Jendela Besar
                                <box-icon name="expand" color="#018083" size="20px"></box-icon>
                            </button>
                        )}
                        {isSubmitEnabled ? (
                            <button onClick={(e) => handleSubmit(e, course.slug)} className="w-full px-6 py-3.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:-translate-y-0.5 flex justify-center items-center gap-2">
                                Kirim Jawaban
                                <box-icon name="send" color="white" size="20px"></box-icon>
                            </button>
                        ) : (
                            <button className="w-full px-6 py-3.5 bg-gray-300 text-gray-500 font-bold rounded-xl shadow-sm cursor-not-allowed flex justify-center items-center gap-2">
                                Selanjutnya
                                <box-icon name="time" color="#6b7280" size="20px"></box-icon>
                            </button>
                        )}
                    </div>
                )}
            </div>

            {isQuestionnaire && (
                <QuestionnaireModal
                    isOpen={isExpanded}
                    onClose={() => setIsExpanded(false)}
                    title={activity.title}
                    onSubmit={(e) => handleSubmit(e, course.slug)}
                >
                    {activityNode}
                </QuestionnaireModal>
            )}

            {isTest && (
                <TestStepperModal
                    isOpen={isExpanded}
                    onClose={() => setIsExpanded(false)}
                    eyebrow={activity.type == 'pre-test' ? 'Pre-Test' : 'Post-Test'}
                    title={activity.title}
                    questions={testQuestions}
                    answers={answers}
                    setAnswers={setAnswers}
                    onSubmit={(e) => handleSubmit(e, course.slug)}
                />
            )}
        </div>
    </Learn>
}

export default Index
