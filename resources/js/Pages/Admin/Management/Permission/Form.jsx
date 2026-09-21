import { btnLink, btnPrimary, fieldError, formCard } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

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
        <AdminLayout
            title={isEdit ? 'Edit Hak Akses' : 'Tambah Hak Akses'}
            actions={
                <Link href={route('admin.management.permission.index')} className={btnLink}>
                    ← Kembali
                </Link>
            }
        >
            <Head title={isEdit ? 'Edit Hak Akses' : 'Tambah Hak Akses'} />
            <form onSubmit={submit} className={`${formCard} max-w-xl space-y-5`}>
                <div>
                    <label className="form-label">Nama</label>
                    <input
                        className="form-control"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                    <p className="mt-1.5 text-xs text-steel">Gunakan nama singkat yang sesuai dengan aksi sistem.</p>
                    {errors.name && <p className={fieldError}>{errors.name}</p>}
                </div>
                <button type="submit" disabled={processing} className={btnPrimary}>
                    {processing ? 'Menyimpan...' : 'Simpan hak akses'}
                </button>
            </form>
        </AdminLayout>
    );
}
