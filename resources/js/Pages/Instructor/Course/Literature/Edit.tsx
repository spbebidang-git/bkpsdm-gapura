import LiteratureForm from '@/Components/Admin/Course/LiteratureForm';

export default function Edit({ slug, activity }: { slug: string; activity: any }) {
    return <LiteratureForm slug={slug} activity={activity} />;
}
