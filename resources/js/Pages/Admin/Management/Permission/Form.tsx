import { btnPrimary } from '@/Components/Admin/ui';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import FormInput from '@/Components/FormInput';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link, useForm } from '@inertiajs/react';
import '@/../css/admin-form.css';

export default function Form({ permission }) {
    const isEdit = !!permission;
    const { data, setData, post, put, processing, errors } = useForm({
        name: permission?.name ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) put(route('admin.management.permission.update', permission.id));
        else post(route('admin.management.permission.store'));
    };

    return (
        <AdminLayout title={isEdit ? 'Edit Hak Akses' : 'Tambah Hak Akses'} hideTitle>
            <Head title={isEdit ? 'Edit Hak Akses' : 'Tambah Hak Akses'} />
            <div className="admin-managed-form">
                <CrudPageHeader
                    title={isEdit ? 'Edit hak akses' : 'Hak akses baru'}
                    description="Atur izin yang dapat diberikan kepada peran admin."
                    actions={<div className="admin-form-header-actions">
                        <button type="submit" form="permission-form" disabled={processing} className={btnPrimary}>Simpan</button>
                        <Link href={route('admin.management.permission.index')} className="admin-form-back">
                            <i className="bx bx-arrow-back text-lg" aria-hidden />Kembali
                        </Link>
                    </div>}
                />
                <form id="permission-form" noValidate onSubmit={submit} className="admin-managed-form-card admin-managed-form-card--flush">
                    <div className="admin-managed-form-body">
                    <FormInput
                        id="name"
                        label="Nama"
                        required
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        small="Gunakan nama singkat yang sesuai dengan aksi sistem."
                    />
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
