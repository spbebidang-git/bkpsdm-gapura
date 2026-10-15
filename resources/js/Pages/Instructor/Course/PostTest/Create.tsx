import TestForm from '@/Components/Admin/Course/Question/TestForm';

export default function Create({ slug }: { slug: string }) {
    return <TestForm slug={slug} kind="post-test" />;
}
