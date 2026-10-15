import TaskForm from '@/Components/Admin/Course/TaskForm';

export default function Edit({ slug, activity }: { slug: string; activity: any }) {
    return <TaskForm slug={slug} activity={activity} />;
}
