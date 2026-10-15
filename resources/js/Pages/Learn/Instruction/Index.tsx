import Learn from '@/Layouts/Participant/Learn';
import { router, usePage } from '@inertiajs/react';
import DOMPurify from 'dompurify';
import { toast } from 'react-toastify';

const handleSubmit = (e, slug, activityId, scheduleId) => {
    e.preventDefault();

    router.post(route('participant.learn.instruction.store', { course: slug, schedule: scheduleId, activity: activityId }), [] as any);
};

const Index = ({ course, progress, activities, activity, activity_id, question_result, instruction, schedule }) => {
    const { errors } = usePage().props


    if (errors.error) {
        toast.error(errors.error, {
            position: "top-right",
            autoClose: 3000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
            theme: "colored",
        });
    }

    return (
        <Learn type="instruction" course={course} progress={progress} activities={activities} activity={activity} schedule={schedule}>
            <div className='learn-section'>
                <div className='learn-section__body learn-instruction-panel'>
                    <div className='min-w-0'>
                        <div className='prose prose-sm sm:prose-base max-w-none text-gray-700' dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(instruction) }} />
                    </div>
                </div>
                <div className='learn-section__actions'>
                    <button onClick={(e) => handleSubmit(e, course.slug, activity_id, schedule.id)} className='learn-action'>
                        Lanjut ke Materi
                        <box-icon name="right-arrow-alt" color="white" size="20px"></box-icon>
                    </button>
                </div>
            </div>
        </Learn>
    );


}

export default Index
