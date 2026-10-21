import Activity from '@/Components/Activity';
import Learn from '@/Layouts/Participant/Learn';
import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from "react-toastify";

const buildTimedActivityStorageKey = (scheduleId, activityId) => `materialCountdown_${scheduleId}_${activityId}`;


const Index = ({ course, progress, activities, activity, activity_id, question_result, schedule }) => {
    const [answers, setAnswers] = useState({});
    const [file, setFile] = useState(null);
    const [answerValidationAttempted, setAnswerValidationAttempted] = useState(false);
    const [fileValidationAttempted, setFileValidationAttempted] = useState(false);
    const [submittingTask, setSubmittingTask] = useState(false);
    const [taskSubmitError, setTaskSubmitError] = useState('');
    const [duration, setDuration] = useState(activity.data?.duration ?? 0);
    const isTimedActivity = activity.type == 'video' || activity.type == 'literature';
    const isSubmitEnabled = !isTimedActivity || duration === 0;
    const timedActivityStorageKey = buildTimedActivityStorageKey(schedule.id, activity_id);

    const formattedAnswers = Object.values(answers as any).map((answer: any) => ({ ...answer }));
    const isTest = activity.type === 'pre-test' || activity.type === 'post-test';
    const questionCount = activity.data?.question_items?.length ?? 0;
    const answeredCount = Object.values(answers as any).filter((answer: any) => answer.option_select !== undefined && answer.option_select !== null).length;
    const unansweredCount = Math.max(questionCount - answeredCount, 0);
    const hasIncompleteAnswers = isTest && unansweredCount > 0;

    useEffect(() => {
        const initialDuration = activity.data?.duration ?? 0;

        if (!isTimedActivity) {
            setDuration(initialDuration);
            return;
        }

        const now = Math.floor(Date.now() / 1000);
        const storedCountdown = localStorage.getItem(timedActivityStorageKey);

        if (!storedCountdown) {
            if (initialDuration > 0) {
                localStorage.setItem(timedActivityStorageKey, JSON.stringify({
                    startedAt: now,
                    initialDuration,
                }));
            }

            setDuration(initialDuration);
            return;
        }

        try {
            const parsedCountdown = JSON.parse(storedCountdown);
            const hasChangedDuration = parsedCountdown.initialDuration !== initialDuration;

            if (hasChangedDuration) {
                if (initialDuration > 0) {
                    localStorage.setItem(timedActivityStorageKey, JSON.stringify({
                        startedAt: now,
                        initialDuration,
                    }));
                } else {
                    localStorage.removeItem(timedActivityStorageKey);
                }

                setDuration(initialDuration);
                return;
            }

            const elapsed = now - parsedCountdown.startedAt;
            const remainingDuration = Math.max(initialDuration - elapsed, 0);

            setDuration(remainingDuration);
        } catch {
            localStorage.removeItem(timedActivityStorageKey);

            if (initialDuration > 0) {
                localStorage.setItem(timedActivityStorageKey, JSON.stringify({
                    startedAt: now,
                    initialDuration,
                }));
            }

            setDuration(initialDuration);
        }
    }, [activity.data?.duration, activity.type, activity_id, isTimedActivity, timedActivityStorageKey]);

    useEffect(() => {
        if (!isTimedActivity) {
            return;
        }

        if (duration === 0) {
            return;
        }

        const interval = setInterval(() => {
            setDuration(prevDuration => {
                if (prevDuration > 1) {
                    return prevDuration - 1;
                }

                clearInterval(interval);
                return 0;
            });
        }, 1000);

        return () => clearInterval(interval);
    }, [duration, isTimedActivity, timedActivityStorageKey]);

    const handleSubmit = (e, slug) => {
        e.preventDefault();

        // store pre test or post test
        if (activity.type == 'pre-test' || activity.type == 'post-test') {
            if (hasIncompleteAnswers) {
                setAnswerValidationAttempted(true);
                const firstUnanswered = Array.from({ length: questionCount }, (_, index) => index)
                    .find(index => answers[index]?.option_select === undefined || answers[index]?.option_select === null) ?? 0;
                requestAnimationFrame(() => {
                    const firstQuestion = document.querySelector<HTMLElement>(`[data-question-index="${firstUnanswered}"] h3`);
                    firstQuestion?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstQuestion?.focus({ preventScroll: true });
                });
                return;
            }

            setAnswerValidationAttempted(false);
            localStorage.removeItem('countdownStartTime');

            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formattedAnswers as any, {
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
                setFileValidationAttempted(true);
                return;
            }

            if (file.type !== 'application/pdf') {
                setFileValidationAttempted(true);
                return;
            }

            const maxSizeInBytes = 2 * 1024 * 1024;
            if (file.size > maxSizeInBytes) {
                setFileValidationAttempted(true);
                return;
            }

            setFileValidationAttempted(false);
            setTaskSubmitError('');
            setSubmittingTask(true);

            // Membuat FormData
            const formData = new FormData();
            formData.append('file', file);

            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formData, {
                forceFormData: true,
                onError: (errors) => setTaskSubmitError(errors.file || errors.error || 'Tugas gagal dikirim. Silakan coba lagi.'),
                onFinish: () => setSubmittingTask(false),
            });

        }

        // store meeting
        if (activity.type == 'meeting') {
            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), [] as any);
        }

        // store video
        if (activity.type == 'video') {
            localStorage.removeItem(timedActivityStorageKey);
            const formattedAnswers = Object.values(answers as any).map((answer: any) => ({ ...answer }));
            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formattedAnswers as any);
        }

        // store literature
        if (activity.type == 'literature') {
            localStorage.removeItem(timedActivityStorageKey);
            const formattedAnswers = Object.values(answers as any).map((answer: any) => ({ ...answer }));
            router.post(route('participant.learn.store', { course: slug, schedule: schedule.id, activity: activity_id }), formattedAnswers as any);
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

    return <Learn type="material" course={course} progress={progress} activities={activities} activity={activity} timer={activity.timer} displayTimerSeconds={duration} formattedAnswers={formattedAnswers} activity_id={activity_id} schedule={schedule}>
        <div className={`learn-section${isTimedActivity ? ' learn-media-section' : ''}${activity.type === 'meeting' ? ' learn-meeting-section' : ''}`}>
            <div className="learn-section__body">
                <div className="flex min-w-0 flex-col">
                    {answerValidationAttempted && hasIncompleteAnswers && <div className="learn-answer-validation" role="alert" aria-live="assertive">
                        <span className="learn-answer-validation__icon" aria-hidden="true"><i className="bx bx-error-circle" /></span>
                        <div className="learn-answer-validation__copy">
                            <strong>Jawaban belum lengkap</strong>
                            <span>Masih ada {unansweredCount} soal yang perlu dijawab sebelum tes dikirim.</span>
                        </div>
                        <span className="learn-answer-validation__count" aria-label={`${unansweredCount} soal belum dijawab`}>{unansweredCount}</span>
                    </div>}
                    {activity.type === 'task' && taskSubmitError && <div className="learn-task__alert" role="alert"><span className="learn-task__alert-icon" aria-hidden="true"><i className="bx bx-error-circle" /></span><div className="learn-task__alert-copy"><strong>Tugas belum terkirim</strong><span>{taskSubmitError}</span></div></div>}
                    <Activity title={activity.title} question_result={question_result} slug={course.slug} id={activity_id} type={activity.type} data={activity.data} status={activity.status} answers={answers} showAnswerErrors={answerValidationAttempted} showFileErrors={fileValidationAttempted} file={file} setFile={setFile}
                        setAnswers={setAnswers} schedule={schedule} />
                </div>
            </div>
            {activity.type !== 'meeting' && <div className="learn-section__actions">
                {isTimedActivity && <Link href={route('participant.learn.instruction.index', { course: course.slug, schedule: schedule.id, activity: activity_id })} className="learn-media__back" aria-label="Kembali ke instruksi"><i className="bx bx-chevron-left" aria-hidden="true" /></Link>}
                {isSubmitEnabled ? <button onClick={(e) => handleSubmit(e, course.slug)} className="learn-action" disabled={submittingTask}>
                    {submittingTask ? 'Mengirim PDF...' : isTimedActivity ? 'Selanjutnya' : activity.type === 'task' ? 'Kirim Tugas PDF' : 'Kirim Jawaban'}
                    <i className={`bx ${isTimedActivity ? 'bx-chevron-right' : 'bx-send'}`} aria-hidden="true" />
                </button>
                    : <button disabled className="learn-action">
                        Selanjutnya
                        <box-icon name="time" color="#6b7280" size="20px"></box-icon>
                    </button>}
            </div>}
        </div>
    </Learn>
}

export default Index
