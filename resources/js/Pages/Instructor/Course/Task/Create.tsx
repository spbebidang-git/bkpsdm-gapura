import TaskForm from '@/Components/Admin/Course/TaskForm';

export default function Create({ slug }: { slug: string }) {
    return <TaskForm slug={slug} />;
}
