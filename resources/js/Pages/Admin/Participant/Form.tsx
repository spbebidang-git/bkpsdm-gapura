import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import FormInput from '@/Components/FormInput';
import FormSelect from '@/Components/FormSelect';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';

type Participant = {
    id: string;
    name: string;
    email: string;
    nip?: string | null;
    status_pegawai?: string | null;
    jabatan?: string | null;
    employee_status_id?: string | null;
    opd_id?: number | null;
} | null;

export default function Form({ participant, employeeStatuses = [], opds = [] }: {
    participant: Participant;
    employeeStatuses?: { id: string; title: string }[];
    opds?: { id: number; name: string; code: string }[];
}) {
    const isEdit = Boolean(participant);
    const { data, setData, post, put, processing, errors } = useForm({
        name: participant?.name ?? '',
        email: participant?.email ?? '',
        nip: participant?.nip ?? '',
        employee_status_id: participant?.employee_status_id ?? '',
        opd_id: participant?.opd_id ? String(participant.opd_id) : '',
        jabatan: participant?.jabatan ?? '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (isEdit && participant) put(route('admin.participant.update', participant.id));
        else post(route('admin.participant.store'));
    };

    const title = isEdit ? 'Edit pengguna lokal' : 'Tambah pengguna lokal';

    return <AdminLayout title={title} hideTitle>
        <Head title={title} />
        <div className="course-index admin-crud-form">
            <CrudPageHeader
                title={title}
                description="Akun lokal GAPURA dapat digunakan pengguna untuk masuk dengan email dan kata sandi."
                actions={<>
                    <Button variant="secondary" href={route('admin.participant.index')} className="min-w-28"><i className="bx bx-arrow-back text-base" aria-hidden />Kembali</Button>
                    <Button type="submit" form="participant-form" loading={processing} disabled={processing} className="min-w-28">{processing ? 'Menyimpan…' : 'Simpan'}</Button>
                </>}
            />
            <form noValidate id="participant-form" onSubmit={submit} aria-busy={processing}>
                <CrudFormSection title="Informasi pengguna" description="Lengkapi identitas pengguna dan informasi kepegawaiannya." icon="bx-user">
                    <div className="admin-crud-fields">
                        <FormInput id="name" label="Nama lengkap" isRequired value={data.name} onChange={(event) => setData('name', event.target.value)} error={errors.name} />
                        <FormInput id="email" label="Email" type="email" isRequired value={data.email} onChange={(event) => setData('email', event.target.value)} error={errors.email} autoComplete="email" />
                        <FormInput id="nip" label="NIP" value={data.nip} onChange={(event) => setData('nip', event.target.value)} error={errors.nip} small="Opsional. Harus unik jika diisi." />
                        <FormSelect id="employee_status_id" label="Status pegawai" value={data.employee_status_id} onChange={(event) => setData('employee_status_id', event.target.value)} error={errors.employee_status_id} options={employeeStatuses.map((status) => ({ value: status.id, label: status.title }))} emptyOption="Pilih status pegawai" small="Pilihan diambil dari Master Status Pegawai." />
                        <FormSelect id="opd_id" label="OPD" value={data.opd_id} onChange={(event) => setData('opd_id', event.target.value)} error={errors.opd_id} options={opds.map((opd) => ({ value: opd.id, label: `${opd.name} (${opd.code})` }))} emptyOption="Pilih OPD" small="Pilihan OPD aktif dari Master OPD." />
                        <FormInput id="jabatan" label="Jabatan" value={data.jabatan} onChange={(event) => setData('jabatan', event.target.value)} error={errors.jabatan} />
                        <FormInput id="password" label={isEdit ? 'Kata sandi baru' : 'Kata sandi'} type="password" isRequired={!isEdit} value={data.password} onChange={(event) => setData('password', event.target.value)} error={errors.password} autoComplete="new-password" minLength={8} small={isEdit ? 'Kosongkan jika tidak ingin mengganti kata sandi.' : 'Minimal 8 karakter.'} />
                        <FormInput id="password_confirmation" label="Konfirmasi kata sandi" type="password" isRequired={!isEdit} value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} error={errors.password_confirmation} autoComplete="new-password" />
                    </div>
                </CrudFormSection>
            </form>
        </div>
    </AdminLayout>;
}
