import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

/**
 * Course management pages (ex-instructor) now live inside admin shell.
 */
export default function App({ children, title = 'Kelas', breadcrumbs = null, fullWidth = false }) {
    return (
        <AdminLayout title={title} breadcrumbs={breadcrumbs} hideTitle fullWidth={fullWidth}>
            <Head title={title} />
            {children}
        </AdminLayout>
    );
}
