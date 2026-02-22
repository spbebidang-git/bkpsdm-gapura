import Learn from '@/Layouts/Participant/Learn';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'react-toastify';

const handleSubmit = (e, slug, activityId, scheduleId) => {
    e.preventDefault();

    router.post(route('participant.learn.instruction.store', { course: slug, schedule: scheduleId, activity: activityId }), []);
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
            <div className='flex flex-col h-full bg-white'>
                <div className='flex-1 rounded-xl bg-gray-50 p-4 mb-4 shadow-inner border border-gray-100 flex flex-col min-h-0'>
                    <div className='flex-1 overflow-y-auto pr-2'>
                        <div className='prose prose-sm sm:prose-base max-w-none text-gray-700' dangerouslySetInnerHTML={{ __html: instruction }} />
                    </div>
                </div>
                <div className='w-full sm:w-64 mx-auto shrink-0 pb-1'>
                    <button onClick={(e) => handleSubmit(e, course.slug, activity_id, schedule.id)} className='w-full px-6 py-3.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md transition-all hover:-translate-y-0.5 flex justify-center items-center gap-2'>
                        Lanjut ke Materi
                        <box-icon name="right-arrow-alt" color="white" size="20px"></box-icon>
                    </button>
                </div>
            </div>
        </Learn>
    );


}

export default Index