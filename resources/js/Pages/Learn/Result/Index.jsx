import Learn from "@/Layouts/Participant/Learn";
import { router } from "@inertiajs/react";
import { toast } from "react-toastify";

// const handleSubmit = (e, slug, activityId) => {
//     e.preventDefault();

//     router.post(route('participant.learn.result.store', { course: slug, schedule: 1, activity: activityId }), []);
// };

const handleSubmit = (e, slug, scheduleId, activityId) => {
    e.preventDefault();

    router.post(
        route("participant.learn.result.store", {
            course: slug,
            schedule: scheduleId,
            activity: activityId,
        }),
        [],
        {
            onError: (errors) => {
                toast.error(errors.error, {
                    position: "top-right",
                    autoClose: 5000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    theme: "colored",
                });
            },
        }
    );
};

const handleLoopTest = (e, slug, scheduleId, activityId) => {
    e.preventDefault();



    router.get(
        route("participant.learn.index", {
            course: slug,
            schedule: scheduleId,
            activity: activityId,
            loop: 1,
        }),
        []
    );
};

const Index = ({
    course,
    progress,
    activities,
    activity,
    activity_id,
    question_result,
    instruction,
    schedule
}) => {
    if (activity.type == "pre-test" || activity.type == "post-test") {
        // Menghapus record timer di local storage agar saat mengulang, timer balik ke awal
        const storageKey = `countdownStartTime_${activity_id}`;
        localStorage.removeItem(storageKey);

        return (
            <Learn
                type="result"
                course={course}
                progress={progress}
                activities={activities}
                activity={activity}
                schedule={schedule}
            >
                <div className="flex flex-col h-full bg-white">
                    <div className="flex-1 rounded-xl bg-gray-50 p-4 mb-4 shadow-inner border border-gray-100 flex flex-col min-h-0">
                        <div className="flex-1 overflow-y-auto flex flex-col justify-center">
                            {question_result.score >=
                                activity.data.min_score ? (
                                <div className="border border-green-100 shadow-sm rounded-2xl bg-white max-w-lg w-full mx-auto flex flex-col items-center p-4 sm:p-8 my-auto">
                                    <div className="border-4 w-20 h-20 sm:w-24 sm:h-24 shrink-0 flex items-center justify-center rounded-full border-green-400 mb-4 bg-green-50 text-green-500">
                                        <box-icon
                                            name="like"
                                            size="md"
                                            color="#22c55e"
                                        ></box-icon>
                                    </div>
                                    <div className="text-center text-lg sm:text-xl text-gray-700 font-semibold mb-5 leading-snug">
                                        <b>Selamat</b>, kamu telah menyelesaikan{" "}
                                        <br className="hidden sm:block" /> {activity.type} ini.
                                    </div>
                                    <div className="w-full">
                                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6 w-full">
                                            <div className="bg-emerald-50/50 border border-emerald-100/50 rounded-xl p-3 sm:p-4 text-center shadow-[0_2px_10px_-3px_rgba(16,185,129,0.1)] transition-transform hover:-translate-y-0.5">
                                                <div className="text-emerald-700 text-[11px] sm:text-xs uppercase font-bold tracking-wider mb-1">Benar</div>
                                                <div className="text-2xl sm:text-3xl font-extrabold text-emerald-600">{question_result.correct}</div>
                                            </div>
                                            <div className="bg-rose-50/50 border border-rose-100/50 rounded-xl p-3 sm:p-4 text-center shadow-[0_2px_10px_-3px_rgba(244,63,94,0.1)] transition-transform hover:-translate-y-0.5">
                                                <div className="text-rose-700 text-[11px] sm:text-xs uppercase font-bold tracking-wider mb-1">Salah</div>
                                                <div className="text-2xl sm:text-3xl font-extrabold text-rose-600">{question_result.wrong}</div>
                                            </div>
                                            <div className="bg-indigo-50/50 border border-indigo-100/50 rounded-xl p-3 sm:p-4 text-center shadow-[0_2px_10px_-3px_rgba(99,102,241,0.1)] transition-transform hover:-translate-y-0.5">
                                                <div className="text-indigo-700 text-[11px] sm:text-xs uppercase font-bold tracking-wider mb-1">Nilai</div>
                                                <div className="text-2xl sm:text-3xl font-extrabold text-indigo-600">{question_result.score}</div>
                                            </div>
                                            <div className="bg-gray-50/80 border border-gray-200/60 rounded-xl p-3 sm:p-4 text-center shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] transition-transform hover:-translate-y-0.5">
                                                <div className="text-gray-500 text-[10px] sm:text-[11px] uppercase font-bold tracking-wider mb-1 whitespace-nowrap pt-0.5">Min. Skor</div>
                                                <div className="text-2xl sm:text-3xl font-extrabold text-gray-700">{activity.data.min_score}</div>
                                            </div>
                                        </div>
                                        <div className="w-full justify-center flex mx-auto">
                                            {activity.data.is_loop == 1 ? (
                                                <button
                                                    onClick={(e) =>
                                                        handleLoopTest(
                                                            e,
                                                            course.slug,
                                                            schedule.id,
                                                            activity_id
                                                        )
                                                    }
                                                    className="button-primary max-w-56"
                                                >
                                                    Ulangi
                                                </button>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="border border-red-100 shadow-sm rounded-2xl bg-white max-w-lg w-full mx-auto flex flex-col items-center p-4 sm:p-8 my-auto">
                                    <div className="border-4 w-20 h-20 sm:w-24 sm:h-24 shrink-0 flex items-center justify-center rounded-full border-red-400 mb-4 bg-red-50 text-red-500">
                                        <box-icon
                                            name="dislike"
                                            size="md"
                                            color="#f87171"
                                        ></box-icon>
                                    </div>
                                    <div className="text-center text-lg sm:text-xl text-gray-700 font-semibold mb-5 leading-snug">
                                        Nilai kamu belum cukup untuk lulus{" "}
                                        <br className="hidden sm:block" /> dari {activity.type} ini
                                    </div>
                                    <div className="w-full">
                                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6 w-full">
                                            <div className="bg-emerald-50/50 border border-emerald-100/50 rounded-xl p-3 sm:p-4 text-center shadow-[0_2px_10px_-3px_rgba(16,185,129,0.1)] transition-transform hover:-translate-y-0.5">
                                                <div className="text-emerald-700 text-[11px] sm:text-xs uppercase font-bold tracking-wider mb-1">Benar</div>
                                                <div className="text-2xl sm:text-3xl font-extrabold text-emerald-600">{question_result.correct}</div>
                                            </div>
                                            <div className="bg-rose-50/50 border border-rose-100/50 rounded-xl p-3 sm:p-4 text-center shadow-[0_2px_10px_-3px_rgba(244,63,94,0.1)] transition-transform hover:-translate-y-0.5">
                                                <div className="text-rose-700 text-[11px] sm:text-xs uppercase font-bold tracking-wider mb-1">Salah</div>
                                                <div className="text-2xl sm:text-3xl font-extrabold text-rose-600">{question_result.wrong}</div>
                                            </div>
                                            <div className="bg-indigo-50/50 border border-indigo-100/50 rounded-xl p-3 sm:p-4 text-center shadow-[0_2px_10px_-3px_rgba(99,102,241,0.1)] transition-transform hover:-translate-y-0.5">
                                                <div className="text-indigo-700 text-[11px] sm:text-xs uppercase font-bold tracking-wider mb-1">Nilai</div>
                                                <div className="text-2xl sm:text-3xl font-extrabold text-indigo-600">{question_result.score}</div>
                                            </div>
                                            <div className="bg-gray-50/80 border border-gray-200/60 rounded-xl p-3 sm:p-4 text-center shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] transition-transform hover:-translate-y-0.5">
                                                <div className="text-gray-500 text-[10px] sm:text-[11px] uppercase font-bold tracking-wider mb-1 whitespace-nowrap pt-0.5">Min. Skor</div>
                                                <div className="text-2xl sm:text-3xl font-extrabold text-gray-700">{activity.data.min_score}</div>
                                            </div>
                                        </div>
                                        <div className="w-full justify-center flex mx-auto">
                                            {activity.data.is_loop == 1 ? (
                                                <button
                                                    onClick={(e) =>
                                                        handleLoopTest(
                                                            e,
                                                            course.slug,
                                                            schedule.id,
                                                            activity_id
                                                        )
                                                    }
                                                    className="button-primary max-w-56"
                                                >
                                                    Ulangi
                                                </button>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="w-full sm:w-64 mx-auto shrink-0 pb-1">
                        <button
                            onClick={(e) =>
                                handleSubmit(e, course.slug, schedule.id, activity_id)
                            }
                            className="w-full px-6 py-3.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:-translate-y-0.5 flex justify-center items-center gap-2"
                        >
                            Kegiatan Selanjutnya
                            <box-icon name="right-arrow-alt" color="white" size="20px"></box-icon>
                        </button>
                    </div>
                </div>
            </Learn>
        );
    }

    return (
        <Learn
            type="result"
            course={course}
            progress={progress}
            activities={activities}
            activity={activity}
            schedule={schedule}
        >
            <div className="flex flex-col h-full bg-white">
                <div className="flex-1 rounded-xl bg-gray-50 p-4 mb-4 shadow-inner border border-gray-100 flex flex-col min-h-0">
                    <div className="flex-1 overflow-y-auto flex flex-col justify-center">
                        <div className="border border-green-100 shadow-sm rounded-2xl bg-white max-w-lg w-full mx-auto flex flex-col justify-center items-center p-6 sm:p-8 my-auto min-h-[250px] sm:h-80">
                            <div className="border-4 w-20 h-20 sm:w-24 sm:h-24 shrink-0 flex items-center justify-center rounded-full border-green-400 mb-4 bg-green-50 text-green-500">
                                <box-icon
                                    name="like"
                                    size="md"
                                    color="#22c55e"
                                ></box-icon>
                            </div>
                            <div className="text-center text-lg sm:text-xl text-gray-700 font-semibold mb-5 leading-snug">
                                <b>Selamat</b>, kamu telah menyelesaikan <br className="hidden sm:block" />{" "}
                                {activity.type} ini.
                            </div>
                        </div>
                    </div>
                </div>
                <div className="w-full sm:w-64 mx-auto shrink-0 pb-1">
                    <button
                        onClick={(e) =>
                            handleSubmit(e, course.slug, schedule.id, activity_id)
                        }
                        className="w-full px-6 py-3.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:-translate-y-0.5 flex justify-center items-center gap-2"
                    >
                        Kegiatan Selanjutnya
                        <box-icon name="right-arrow-alt" color="white" size="20px"></box-icon>
                    </button>
                </div>
            </div>
        </Learn>
    );

    // return <Learn type="result" course={course} progress={progress} activities={activities} activity={activity}>
    //     <div className='flex flex-col justify-between'>
    //         <div className='flex-1 bg-gray-50 py-2.5 mb-8 '>
    //             <div className='p-3 flex-1 h-[550px] overflow-scroll'>
    //                 <div dangerouslySetInnerHTML={{ __html: instruction }} />
    //             </div>
    //         </div>
    //         <div className='h-14 w-56 mx-auto'>
    //             <button onClick={(e) => handleSubmit(e, course.slug, activity_id)} className='button-primary'>Lanjut ke Materi</button>
    //         </div>
    //     </div>
    // </Learn>
};

export default Index;
