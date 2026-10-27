import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import FormInput from '@/Components/FormInput';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import '@/../css/admin-employee-status.css';

type EmployeeStatus = { id: string; title: string } | null;

export default function Form({ employeeStatus }: { employeeStatus: EmployeeStatus }) {
    const isEdit = Boolean(employeeStatus);
    const { data, setData, post, put, processing, errors } = useForm({
        title: employeeStatus?.title ?? '',
    });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (isEdit && employeeStatus) put(route('admin.employee-status.update', employeeStatus.id));
        else post(route('admin.employee-status.store'));
    };

    return <AdminLayout title={isEdit ? 'Ubah status pegawai' : 'Tambah status pegawai'} hideTitle>
        <Head title={isEdit ? `Ubah ${employeeStatus?.title}` : 'Tambah status pegawai'} />
        <div className="employee-status-admin employee-status-admin-form">
            <CrudPageHeader
                title={isEdit ? 'Ubah status pegawai' : 'Tambah status pegawai'}
                description="Status kepegawaian digunakan untuk mengelompokkan pengguna dan syarat kelas."
                actions={<>
                    <Button variant="secondary" href={route('admin.employee-status.index')} className="min-w-28"><i className="bx bx-arrow-back text-base" aria-hidden />Kembali</Button>
                    <Button type="submit" form="employee-status-form" loading={processing} disabled={processing} className="min-w-28">
                        {processing ? 'Menyimpan…' : 'Simpan'}
                    </Button>
                </>}
            />
            <form noValidate id="employee-status-form" onSubmit={submit} aria-busy={processing} className="admin-crud-form">
                <CrudFormSection title="Informasi status" description="Masukkan nama status kepegawaian." icon="bx-id-card">
                    <div className="admin-crud-fields employee-status-form-fields">
                        <FormInput id="title" label="Nama status pegawai" value={data.title} onChange={(event) => setData('title', event.target.value)} error={errors.title} placeholder="Contoh: PNS, PPPK, THL" />
                    </div>
                </CrudFormSection>
            </form>
        </div>
    </AdminLayout>;
}
