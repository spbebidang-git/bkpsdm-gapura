import Learn from '@/Layouts/Participant/Learn';
import { router } from '@inertiajs/react';

const handleSubmit = (e, slug, activityId) => {
    e.preventDefault();

    router.post(route('participant.learn.result.store', { course: slug, schedule: 1, activity: activityId }), []);
};

const handleLoopTest = (e, slug, activityId) => {
    e.preventDefault();

    router.get(route('participant.learn.index', { course: slug, schedule: 1, activity: activityId }), []);
};

const Index = ({ course, progress, activities, activity, activity_id, question_result, instruction }) => {
    console.log(activity)
    if (activity.type == 'pre-test' || activity.type == 'post-test') {
        return <Learn type="result" course={course} progress={progress} activities={activities} activity={activity}>
            <div className='h-[562px] bg-gray-50 py-8 mb-5 '>
                {question_result.score >= activity.data.min_score ?
                    (
                        <div className='border-2 max-w-lg mx-auto flex flex-col items-center p-5'>
                            <div className='border-2 w-24 h-24 flex items-center justify-center rounded-full border-green-400 mb-3'>
                                <box-icon name='like' size='lg' color="#22c55e"></box-icon>
                            </div>
                            <div className="text-center text-xl text-gray-700 font-semibold mb-5">
                                <b>Selamat</b>, kamu telah menyelesaikan tes ini.
                            </div>
                            <div className="flex text-center justify-center gap-8 text-xl mb-4">
                                <div>
                                    <div>Benar</div>
                                    <div className='font-bold'>{question_result.correct}</div>
                                </div>
                                <div>
                                    <div>Salah</div>
                                    <div className='font-bold'>{question_result.wrong}</div>
                                </div>
                            </div>
                            <div className="text-center text-xl font-bold">Nilai Kamu : <b className='text-green-400'>{question_result.score}</b></div>
                            <div className="w-full justify-center flex mx-auto mt-6">
                                <button onClick={(e) => handleLoopTest(e, course.slug, activity_id)} className="button-primary max-w-56">
                                    Ulangi
                                </button>
                            </div>
                        </div>)
                    : (<div className='border-2 max-w-lg mx-auto flex flex-col items-center p-5'>
                        <div className='border-2 w-24 h-24 flex items-center justify-center rounded-full border-red-400 mb-3'>
                            <box-icon name='dislike' size='lg' color="#f87171"></box-icon>
                        </div>
                        <div className="text-center text-xl text-gray-700 font-semibold mb-5">
                            Nilai kamu belum cukup untuk lulus dari test ini
                        </div>
                        <div className="flex text-center justify-center gap-8 text-xl mb-4">
                            <div>
                                <div>Benar</div>
                                <div className='font-bold'>{question_result.correct}</div>
                            </div>
                            <div>
                                <div>Salah</div>
                                <div className='font-bold'>{question_result.wrong}</div>
                            </div>
                        </div>
                        <div className="text-center text-xl font-bold">Nilai Kamu : <b className='text-red-400'>{question_result.score}</b></div>
                        <div>Nilai Minimal : {activity.data.min_score} </div>
                        <div className="w-full justify-center flex mx-auto mt-6">
                            <button onClick={(e) => handleLoopTest(e, course.slug, activity_id)} className="button-primary max-w-56">
                                Ulangi
                            </button>
                        </div>
                    </div>
                    )}



            </div>
            <div className='h-14 w-56 mx-auto'>
                <button onClick={(e) => handleSubmit(e, course.slug, activity_id)} className='button-primary'>Kegiatan Selanjutnya</button>
            </div>
        </Learn >
    }

    return <Learn type="result" course={course} progress={progress} activities={activities} activity={activity}>
        <div className='h-[562px] bg-gray-50 py-2.5 mb-5 '>
            <div className='p-2.5 flex-1 h-[600px]'>
                <div dangerouslySetInnerHTML={{ __html: instruction }} />
            </div>
        </div>
        <div className='h-14 w-56 mx-auto'>
            <button onClick={(e) => handleSubmit(e, course.slug, activity_id)} className='button-primary'>Kegiatan Selanjutnya</button>
        </div>
    </Learn>
}

export default Index