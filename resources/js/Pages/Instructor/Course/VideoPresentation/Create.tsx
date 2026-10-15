import VideoForm from '@/Components/Admin/Course/VideoForm';

export default function Create({ slug }: { slug: string }) {
    return <VideoForm slug={slug} />;
}
