import MeetingForm from '@/Components/Admin/Course/MeetingForm';

export default function Create({ slug }: { slug: string }) {
    return <MeetingForm slug={slug} />;
}
