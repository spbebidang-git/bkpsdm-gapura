import Learn from '@/Layouts/Participant/Learn';
import { router } from '@inertiajs/react';

const handleSubmit = (e, slug, activityId) => {
    e.preventDefault();

    router.post(route('participant.learn.instruction.store', { course: slug, schedule: 1, activity: activityId }), []);
};

const Index = ({ course, progress, activities, activity, activity_id, question_result, instruction }) => {
    return <Learn type="instruction" course={course} progress={progress} activities={activities} activity={activity}>
        <div className='h-[562px] bg-gray-50 py-2.5 mb-5 '>
            <div className='p-2.5 flex-1 h-[600px]'>
                <div dangerouslySetInnerHTML={{ __html: instruction }} />
            </div>
        </div>
        <div className='h-14 w-56 mx-auto'>
            <button onClick={(e) => handleSubmit(e, course.slug, activity_id)} className='button-primary'>Lanjut ke Materi</button>
        </div>
    </Learn>
}

export default Index