import Learn from "@/Layouts/Participant/Learn";
import { router } from "@inertiajs/react";
import { useEffect } from "react";
import { toast } from "react-toastify";
import type { LearnActivity, LearnAgendaItem, LearnId } from "@/types/learn";

interface ResultProps {
    course: { slug: string; title: string };
    progress: string | number;
    activities: LearnAgendaItem[];
    activity: LearnActivity;
    activity_id: LearnId;
    question_result?: { score?: number | string; correct?: number; wrong?: number } | null;
    instruction?: string | null;
    schedule: { id: LearnId };
}

const completionCopy: Record<Exclude<LearnActivity["type"], "pre-test" | "post-test">, { title: string; back: string }> = {
    literature: { title: "Materi selesai dipelajari", back: "Baca ulang" },
    video: { title: "Video selesai ditonton", back: "Tonton ulang" },
    task: { title: "Tugas berhasil dikirim", back: "Lihat tugas" },
    meeting: { title: "Pertemuan selesai diikuti", back: "Lihat kegiatan" },
};

const ResultPersonIcon = ({ passed = true }: { passed?: boolean }) => (
    <span className={`learn-result-person${passed ? '' : ' is-unpassed'}`} aria-hidden="true">
        <svg viewBox="0 0 96 96" fill="none">
            <path d="M23 82v-9c0-13 11-22 25-22s25 9 25 22v9" fill="currentColor" opacity=".18" />
            <path d="M23 82v-9c0-13 11-22 25-22s25 9 25 22" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
            <circle cx="48" cy="34" r="21" fill="var(--result-person-face)" stroke="currentColor" strokeWidth="3" />
            <path d="M28 29c2-14 11-20 22-18 10 1 17 8 18 18-8-1-15-5-19-10-5 6-12 9-21 10Z" fill="currentColor" />
            <circle cx="40" cy="35" r="2" fill="currentColor" />
            <circle cx="56" cy="35" r="2" fill="currentColor" />
            <path d={passed ? 'M40 43q8 9 16 0' : 'M41 47q7-8 14 0'} stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
            <circle cx="74" cy="72" r="13" fill="currentColor" />
            <path d={passed ? 'm68 72 4 4 8-8' : 'M74 66v7m0 5v.1'} stroke="white" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    </span>
);

const handleNext = (e: React.FormEvent, slug: string, scheduleId: LearnId, activityId: LearnId) => {
    e.preventDefault();
    router.post(route("participant.learn.result.store", { course: slug, schedule: scheduleId, activity: activityId }), [] as any, {
        onError: (errors) => {
            toast.error(errors.error, {
                position: "top-right", autoClose: 5000, hideProgressBar: false,
                closeOnClick: true, pauseOnHover: true, draggable: true, theme: "colored",
            });
        },
    });
};

const Index = ({ course, progress, activities, activity, activity_id, question_result, schedule }: ResultProps) => {
    const currentIndex = activities.findIndex(item => String(item.id) === String(activity_id));
    const isLastActivity = currentIndex >= 0 && currentIndex === activities.length - 1;
    const isTest = activity.type === "pre-test" || activity.type === "post-test";
    const completion = !isTest ? completionCopy[activity.type as keyof typeof completionCopy] : null;
    const score = Number(question_result?.score ?? 0);
    const minScore = Number(activity.data?.min_score ?? 70);
    const passed = isTest && score >= minScore;
    const testName = activity.type === "pre-test" ? "pre-test" : "post-test";
    const canRetry = isTest && Number(activity.data?.is_loop) === 1;
    const activityWeight = activities.find(item => String(item.id) === String(activity_id))?.value;

    useEffect(() => {
        if (isTest && typeof window !== "undefined") {
            window.localStorage.removeItem(`countdownStartTime_${activity_id}`);
        }
    }, [activity_id, isTest]);

    const goToMaterial = () => router.get(route("participant.learn.index", {
        course: course.slug,
        schedule: schedule.id,
        activity: activity_id,
    }));

    const retryTest = () => router.get(route("participant.learn.index", {
        course: course.slug,
        schedule: schedule.id,
        activity: activity_id,
        loop: 1,
    }));

    return (
        <Learn type="result" course={course} progress={progress} activities={activities} activity={activity} schedule={schedule}>
            <div className="learn-section learn-result learn-completion-result">
                {!isTest ? <section className="learn-result__panel learn-completion-result__panel" aria-labelledby="learn-result-title">
                    <ResultPersonIcon />
                    <h3 id="learn-result-title">{activity.type === 'literature' ? <><span className="learn-completion-result__title-line">Materi {activity.title}</span><span className="learn-completion-result__title-line">Telah selesai Anda pelajari</span></> : completion.title}</h3>
                    {activityWeight !== null && activityWeight !== undefined && <span className="learn-completion-result__value">Bobot nilai: <strong>{activityWeight}</strong></span>}
                    <p>{activity.type === 'literature' && 'Terima kasih telah meluangkan waktu untuk mempelajari materi ini. Anda dapat melanjutkan ke '}{activity.type !== 'literature' && 'Anda dapat melanjutkan ke '}{isLastActivity ? 'penilaian kelas' : 'kegiatan berikutnya'}.</p>
                </section> : <section className={`learn-result__panel learn-completion-result__panel learn-test-result${passed ? '' : ' is-unpassed'}`} aria-labelledby="learn-result-title">
                    <ResultPersonIcon passed={passed} />
                    <h3 id="learn-result-title">{passed ? `Anda lulus ${testName}` : `Anda belum lulus ${testName}`}</h3>
                    <div className="learn-test-result__score" aria-label={`Nilai Anda ${score} dari 100`}>
                        <span className="learn-test-result__score-label">Nilai Anda</span>
                        <span className="learn-test-result__score-number">{score}<span> / 100</span></span>
                    </div>
                    <div className="learn-test-result__details">
                        <span>Nilai minimum <strong>{minScore}</strong></span>
                        {activityWeight !== null && activityWeight !== undefined && <span>Bobot nilai <strong>{activityWeight}</strong></span>}
                    </div>
                    <p id="learn-result-description">{passed
                        ? `Terima kasih telah menyelesaikan tes. Anda dapat melanjutkan ke ${isLastActivity ? 'penilaian kelas' : 'kegiatan berikutnya'}.`
                        : canRetry
                            ? 'Nilai Anda belum mencapai batas kelulusan. Silakan ulangi tes saat Anda siap.'
                            : 'Nilai Anda belum mencapai batas kelulusan. Hubungi penyelenggara untuk mengetahui langkah berikutnya.'}</p>
                </section>}


                <div className="learn-result__actions">
                    <button type="button" onClick={goToMaterial} className="learn-result__back" aria-label={completion?.back || "Kembali ke materi kegiatan"}>
                        <i className="bx bx-left-arrow-alt" aria-hidden="true" />
                    </button>
                    <div className="learn-result__actions-right">
                        {canRetry && (
                            <button type="button" onClick={retryTest} className={`learn-action${passed ? ' learn-result__retry' : ''}`}><i className="bx bx-refresh" aria-hidden="true" />Ulangi tes</button>
                        )}
                        <button
                            type="button"
                            onClick={(e) => handleNext(e, course.slug, schedule.id, activity_id)}
                            className="learn-action learn-result__next"
                            disabled={isTest && !passed}
                            aria-describedby={isTest && !passed ? "learn-result-description" : undefined}
                        >
                            Selanjutnya
                            <i className="bx bx-right-arrow-alt" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>
        </Learn>
    );
};

export default Index;
