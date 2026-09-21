import { btnLink, btnPrimary, fieldError, formCard } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ user, roles = [] }) {
    const isEdit = !!user;
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        role: user?.role ?? roles[0]?.name ?? 'Admin',
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) put(route('admin.management.user.update', user.id));
        else post(route('admin.management.user.store'));
    };

    return (
        <AdminLayout
            title={isEdit ? 'Edit Pengguna Admin' : 'Tambah Pengguna Admin'}
            actions={
                <Link href={route('admin.management.user.index')} className={btnLink}>
                    ← Kembali
                </Link>
            }
        >
            <Head title={isEdit ? 'Edit Pengguna Admin' : 'Tambah Pengguna Admin'} />
            <form onSubmit={submit} className={`${formCard} max-w-xl space-y-5`}>
                <div>
                    <label className="form-label">Nama</label>
                    <input
                        className="form-control"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                    {errors.name && <p className={fieldError}>{errors.name}</p>}
                </div>
                <div>
                    <label className="form-label">Email</label>
                    <input
                        type="email"
                        className="form-control"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />
                    {errors.email && <p className={fieldError}>{errors.email}</p>}
                </div>
                {!isEdit && (
                    <div>
                        <label className="form-label">Peran</label>
                        <select
                            className="form-control"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                        >
                            {roles.map((r) => (
                                <option key={r.id} value={r.name}>
                                    {r.name}
                                </option>
                            ))}
                        </select>
                    </div>
                )}
                <p className="text-xs text-steel">Akun masuk melalui SSO menggunakan NIP. Tidak ada kata sandi lokal.</p>
                <button type="submit" disabled={processing} className={btnPrimary}>
                    {processing ? 'Menyimpan...' : 'Simpan pengguna'}
                </button>
            </form>
        </AdminLayout>
    );
}
