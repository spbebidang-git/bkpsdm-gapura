import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import AdminLayout from '@/Layouts/Admin/App';
import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import '@/../css/admin-course-test-form.css';

export default function ActivityCrudFormLayout({
    slug,
    title,
    description,
    formId,
    processing,
    children,
}: {
    slug: string;
    title: string;
    description: string;
    formId: string;
    processing: boolean;
    children: ReactNode;
}) {
    return <AdminLayout title={title} hideTitle breadcrumbs={[
        { label: 'Kegiatan', href: route('admin.course.activities.index', slug) },
        { label: 'Aktivitas', href: `/admin/course/${slug}/activity` },
        { label: title },
    ]}>
        <Head title={title} />
        <div className="course-index course-test-page admin-crud-form">
            <CrudPageHeader title={title} description={description} className="course-index-header" actions={<>
                <Button variant="secondary" href={`/admin/course/${slug}/activity`} className="min-w-28"><i className="bx bx-arrow-back" aria-hidden />Kembali</Button>
                <Button type="submit" form={formId} loading={processing} disabled={processing} className="min-w-28">
                    {processing ? 'Menyimpan…' : 'Simpan'}
                </Button>
            </>} />
            {children}
        </div>
    </AdminLayout>;
}
