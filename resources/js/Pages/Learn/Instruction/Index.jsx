import Learn from '@/Layouts/Participant/Learn';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'react-toastify';

const handleSubmit = (e, slug, activityId) => {
    e.preventDefault();

    router.post(route('participant.learn.instruction.store', { course: slug, schedule: 1, activity: activityId }), []);
};

const Index = ({ course, progress, activities, activity, activity_id, question_result, instruction }) => {
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
        <Learn type="instruction" course={course} progress={progress} activities={activities} activity={activity}>
            <div className='flex flex-col justify-between'>
                <div className='flex-1 rounded-xl bg-gray-50 py-2.5 mb-8 '>
                    <div className='p-3 flex-1 h-[550px] overflow-scroll'>
                        <div dangerouslySetInnerHTML={{ __html: instruction }} />
                    </div>
                </div>
                <div className='h-14 w-56 mx-auto'>
                    <button onClick={(e) => handleSubmit(e, course.slug, activity_id)} className='button-primary'>Lanjut ke Materi</button>
                </div>
            </div>
        </Learn>
    );


}

export default Index