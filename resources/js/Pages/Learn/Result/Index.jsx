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
        return (
            <Learn
                type="result"
                course={course}
                progress={progress}
                activities={activities}
                activity={activity}
                schedule={schedule}
            >
                <div className="flex flex-col justify-between">
                    <div className="flex-1 rounded-xl bg-gray-50 py-2.5 mb-8 ">
                        <div className="p-3 flex-1 h-[550px] flex flex-col justify-center overflow-scroll">
                            {question_result.score >=
                                activity.data.min_score ? (
                                <div className="border-2 bg-white max-w-lg w-full mx-auto flex flex-col items-center p-8">
                                    <div className="border-2 w-24 h-24 flex items-center justify-center rounded-full border-green-400 mb-3">
                                        <box-icon
                                            name="like"
                                            size="lg"
                                            color="#22c55e"
                                        ></box-icon>
                                    </div>
                                    <div className="text-center text-xl text-gray-700 font-semibold mb-5">
                                        <b>Selamat</b>, kamu telah menyelesaikan{" "}
                                        <br /> {activity.type} ini.
                                    </div>
                                    <div className="w-full">
                                        <table className="w-full">
                                            <tr>
                                                <th className="p-3 border w-24">
                                                    Benar
                                                </th>
                                                <th className="p-3 border w-24">
                                                    Salah
                                                </th>
                                                <th className="p-3 border w-24">
                                                    Nilai
                                                </th>
                                                <th className="p-3 border w-24 whitespace-nowrap">
                                                    Min Score
                                                </th>
                                            </tr>
                                            <tr className="font-bold">
                                                <td className="p-3 border w-24 text-center">
                                                    {question_result.correct}
                                                </td>
                                                <td className="p-3 border w-24 text-center">
                                                    {question_result.wrong}
                                                </td>
                                                <td className="p-3 border w-24 text-center">
                                                    {question_result.score}
                                                </td>
                                                <td className="p-3 border w-24 text-center whitespace-nowrap">
                                                    {activity.data.min_score}
                                                </td>
                                            </tr>
                                        </table>
                                        <div className="w-full justify-center flex mx-auto mt-6">
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
                                <div className="border-2 bg-white max-w-lg w-full mx-auto flex flex-col items-center p-8">
                                    <div className="border-2 w-24 h-24 flex items-center justify-center rounded-full border-red-400 mb-3">
                                        <box-icon
                                            name="dislike"
                                            size="lg"
                                            color="#f87171"
                                        ></box-icon>
                                    </div>
                                    <div className="text-center text-xl text-gray-700 font-semibold mb-5">
                                        Nilai kamu belum cukup untuk lulus{" "}
                                        <br /> dari {activity.type} ini
                                    </div>
                                    <div className="w-full">
                                        <table className="w-full">
                                            <tr>
                                                <th className="p-3 border w-24">
                                                    Benar
                                                </th>
                                                <th className="p-3 border w-24">
                                                    Salah
                                                </th>
                                                <th className="p-3 border w-24">
                                                    Nilai
                                                </th>
                                                <th className="p-3 border w-24 whitespace-nowrap">
                                                    Min Score
                                                </th>
                                            </tr>
                                            <tr className="font-bold">
                                                <td className="p-3 border w-24 text-center">
                                                    {question_result.correct}
                                                </td>
                                                <td className="p-3 border w-24 text-center">
                                                    {question_result.wrong}
                                                </td>
                                                <td className="p-3 border w-24 text-center">
                                                    {question_result.score}
                                                </td>
                                                <td className="p-3 border w-24 text-center whitespace-nowrap">
                                                    {activity.data.min_score}
                                                </td>
                                            </tr>
                                        </table>
                                        <div className="w-full justify-center flex mx-auto mt-6">
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
                    <div className="h-14 w-56 mx-auto">
                        <button
                            onClick={(e) =>
                                handleSubmit(e, course.slug, schedule.id, activity_id)
                            }
                            className="button-primary"
                        >
                            Kegiatan Selanjutnya
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
            <div className="flex flex-col justify-between">
                <div className="flex-1 rounded-xl bg-gray-50 py-2.5 mb-8 ">
                    <div className="p-3 flex-1 h-[550px] flex flex-col justify-center overflow-scroll">
                        <div className="border-2 h-80 bg-white max-w-lg w-full mx-auto flex flex-col justify-center items-center p-8">
                            <div className="border-2 w-24 h-24 flex items-center justify-center rounded-full border-green-400 mb-3">
                                <box-icon
                                    name="like"
                                    size="lg"
                                    color="#22c55e"
                                ></box-icon>
                            </div>
                            <div className="text-center text-xl text-gray-700 font-semibold mb-5">
                                <b>Selamat</b>, kamu telah menyelesaikan <br />{" "}
                                {activity.type} ini.
                            </div>
                        </div>
                    </div>
                </div>
                <div className="h-14 w-56 mx-auto">
                    <button
                        onClick={(e) =>
                            handleSubmit(e, course.slug, schedule.id, activity_id)
                        }
                        className="button-primary"
                    >
                        Kegiatan Selanjutnya
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
