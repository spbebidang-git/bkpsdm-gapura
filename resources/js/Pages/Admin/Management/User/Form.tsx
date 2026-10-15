import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import FormInput from '@/Components/FormInput';
import FormSelect from '@/Components/FormSelect';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import '@/../css/admin-user.css';

export default function Form({ user, opds = [] }) {
    const isEdit = Boolean(user);
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '', email: user?.email ?? '', nip: user?.nip ?? '',
        opd_id: user?.opd_id ? String(user.opd_id) : '',
        auth_source: user?.auth_source ?? 'local',
        password: '', password_confirmation: '', roles: ['administrator'],
    });
    const isSso = data.auth_source === 'sso';
    const lockedIdentity = isEdit && isSso;
    const title = isEdit ? 'Ubah admin' : 'Tambah admin';
    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (isEdit) put(route('admin.management.user.update', user.id));
        else post(route('admin.management.user.store'));
    };

    return <AdminLayout title={title} hideTitle>
        <Head title={title} />
        <div className="user-admin user-admin-form">
            <CrudPageHeader title={title} description="Kelola identitas dan metode masuk akun administrator GAPURA." actions={<>
                <Button variant="secondary" href={route('admin.management.user.index')} className="min-w-28"><i className="bx bx-arrow-back text-base" aria-hidden />Kembali</Button>
                <Button type="submit" form="user-form" loading={processing} disabled={processing} className="min-w-28">{processing ? 'Menyimpan…' : 'Simpan'}</Button>
            </>} />
            <form noValidate id="user-form" onSubmit={submit} aria-busy={processing} className="admin-crud-form">
                <CrudFormSection title="Informasi admin" description={lockedIdentity ? 'Identitas akun dikelola melalui Kedaton SSO.' : 'Isi identitas akun yang akan mengelola GAPURA.'} icon="bx-user">
                    <div className="admin-crud-fields">
                        {!isEdit && <FormSelect id="auth_source" label="Metode masuk" value={data.auth_source} onChange={(e) => setData('auth_source', e.target.value)} error={errors.auth_source} emptyOption={null} options={[{ value: 'local', label: 'GAPURA lokal' }, { value: 'sso', label: 'Kedaton SSO' }]} />}
                        <FormInput id="name" label="Nama lengkap" required disabled={lockedIdentity} value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} />
                        <FormInput id="email" label="Email" type="email" required disabled={lockedIdentity} value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} />
                        {isSso && <FormInput id="nip" label="NIP" disabled={lockedIdentity} value={data.nip} onChange={(e) => setData('nip', e.target.value)} error={errors.nip} />}
                        {!isSso && <FormSelect id="opd_id" label="OPD" value={data.opd_id} onChange={(e) => setData('opd_id', e.target.value)} error={errors.opd_id} emptyOption="Pilih OPD (opsional)" options={opds.map((opd) => ({ value: opd.id, label: opd.name }))} />}
                        <FormInput id="role" label="Peran" value="Administrator" onChange={() => {}} disabled error={errors.roles} />
                        {isEdit && <FormInput id="auth_source" label="Metode masuk" value={isSso ? 'Kedaton SSO' : 'GAPURA lokal'} onChange={() => {}} disabled />}
                    </div>
                </CrudFormSection>
                {!isSso && <CrudFormSection title="Keamanan akun" description={isEdit ? 'Kosongkan password jika tidak ingin mengubahnya.' : 'Gunakan password minimal 8 karakter.'} icon="bx-lock-alt">
                    <div className="admin-crud-fields">
                        <FormInput id="password" label={isEdit ? 'Password baru' : 'Password'} type="password" required={!isEdit} minLength={8} autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} error={errors.password} />
                        <FormInput id="password_confirmation" label="Konfirmasi password" type="password" required={!isEdit || Boolean(data.password)} autoComplete="new-password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} error={errors.password_confirmation} />
                    </div>
                </CrudFormSection>}
            </form>
        </div>
    </AdminLayout>;
}
