import MeetingForm from '@/Components/Admin/Course/MeetingForm';

export default function Edit({ slug, activity }: { slug: string; activity: any }) {
    return <MeetingForm slug={slug} activity={activity} />;
}
