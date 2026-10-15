import VideoForm from '@/Components/Admin/Course/VideoForm';

export default function Edit({ slug, activity }: { slug: string; activity: any }) {
    return <VideoForm slug={slug} activity={activity} />;
}
