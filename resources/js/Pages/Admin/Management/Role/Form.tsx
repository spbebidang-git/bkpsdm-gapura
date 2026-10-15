import { btnPrimary } from '@/Components/Admin/ui';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import FormInput from '@/Components/FormInput';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link, useForm } from '@inertiajs/react';
import '@/../css/admin-form.css';

export default function Form({ role, permissions = [] }) {
    const isEdit = Boolean(role);
    const availablePermissions = Array.isArray(permissions) ? permissions : Object.values(permissions || {});
    const initialPermissions = Array.isArray(role?.permissions) ? role.permissions : Object.values(role?.permissions || {});
    const { data, setData, post, put, processing, errors } = useForm({
        name: role?.name ?? '',
        permissions: initialPermissions,
    });
    const selectedPermissions = Array.isArray(data.permissions) ? data.permissions : initialPermissions;
    const roleLabels = { administrator: 'Administrator', trainer: 'Trainer', participant: 'Participant' };

    const togglePermission = (id, checked) => setData('permissions', checked
        ? [...selectedPermissions, id]
        : selectedPermissions.filter((permissionId) => permissionId !== id));

    return (
        <AdminLayout title={isEdit ? 'Edit hak akses' : 'Role baru'} hideTitle>
            <Head title={isEdit ? `Hak akses ${roleLabels[role.name] || role.name}` : 'Role baru'} />
            <div className="admin-managed-form">
                <CrudPageHeader
                    title={isEdit ? `Hak akses ${roleLabels[role.name] || role.name}` : 'Buat role baru'}
                    description="Tentukan nama role dan tindakan yang dapat dilakukan oleh penggunanya."
                    actions={<Link href={route('admin.management.role.index')} className="admin-form-back">
                        <i className="bx bx-arrow-back text-lg" aria-hidden />
                        Kembali ke peran
                    </Link>}
                />

                <form noValidate onSubmit={(event) => { event.preventDefault(); isEdit ? put(route('admin.management.role.update', role.id)) : post(route('admin.management.role.store')); }} className="admin-managed-form-card admin-managed-form-card--flush">
                    <div className="admin-managed-form-section-head flex flex-col gap-2 border-b px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                        <div>
                            <h2 className="text-base font-semibold text-ink">Identitas dan hak akses</h2>
                            <p className="mt-1 text-sm text-steel">{selectedPermissions.length} dari {availablePermissions.length} hak akses dipilih.</p>
                        </div>
                        <button type="button" onClick={() => setData('permissions', availablePermissions.map((permission) => permission.id))} className="text-sm font-semibold text-primary hover:text-primary-pressed">
                            Pilih semua
                        </button>
                    </div>

                    <div className="border-b border-hairline p-5 sm:p-7">
                        <FormInput
                            id="name"
                            label="Nama role"
                            required
                            readOnly={Boolean(role?.is_core)}
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value.toLowerCase())}
                            error={errors.name}
                            placeholder="contoh: reviewer"
                            inputClassName="max-w-xl font-mono"
                            small="Gunakan huruf kecil, angka, tanda hubung, atau garis bawah."
                        />
                    </div>

                    <div className="grid gap-2 p-5 sm:grid-cols-2 sm:p-7 lg:grid-cols-3">
                        {availablePermissions.map((permission) => {
                            const selected = selectedPermissions.includes(permission.id);
                            return (
                                <label key={permission.id} className={`flex cursor-pointer items-center gap-3 rounded-xl border px-3.5 py-3 transition ${selected ? 'border-primary/35 bg-primary-mist/60' : 'border-[#E1E6E4] hover:border-primary/25 hover:bg-[#F8FBFA]'}`}>
                                    <input type="checkbox" checked={selected} onChange={(event) => togglePermission(permission.id, event.target.checked)} className="h-4 w-4 rounded border-[#D0D9E6] text-primary focus:ring-primary" />
                                    <span className="min-w-0 font-mono text-xs font-medium text-ink">{permission.name}</span>
                                </label>
                            );
                        })}
                    </div>

                    <div className="admin-managed-form-footer flex justify-end border-t px-5 py-4 sm:px-7">
                        <button type="submit" disabled={processing} className={btnPrimary}>
                            <i className="bx bx-save text-lg" aria-hidden />
                            {processing ? 'Menyimpan...' : isEdit ? 'Simpan hak akses' : 'Buat role'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
