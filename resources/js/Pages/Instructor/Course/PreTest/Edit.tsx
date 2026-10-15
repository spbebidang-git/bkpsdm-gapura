import TestForm from '@/Components/Admin/Course/Question/TestForm';

export default function Edit({ slug, activity }: { slug: string; activity: any }) {
    return <TestForm slug={slug} kind="pre-test" activity={activity} />;
}
