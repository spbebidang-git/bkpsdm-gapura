import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import FormInput from '@/Components/FormInput';
import FormSelect from '@/Components/FormSelect';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import '@/../css/admin-opd.css';

type Opd = { id: number; code: string; name: string; is_active: boolean } | null;

export default function Form({ opd }: { opd: Opd }) {
    const isEdit = Boolean(opd);
    const { data, setData, post, put, processing, errors } = useForm({
        code: opd?.code ?? '',
        name: opd?.name ?? '',
        is_active: opd?.is_active ?? true,
    });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (isEdit && opd) put(route('admin.opd.update', opd.id));
        else post(route('admin.opd.store'));
    };

    return <AdminLayout title={isEdit ? 'Ubah OPD' : 'Tambah OPD'} hideTitle>
        <Head title={isEdit ? `Ubah ${opd?.name}` : 'Tambah OPD'} />
        <div className="opd-admin opd-admin-form">
            <CrudPageHeader
                title={isEdit ? 'Ubah OPD' : 'Tambah OPD'}
                description="Data lokal dapat disunting dan akan diperbarui jika ditemukan pada sinkronisasi Kedaton berikutnya."
                actions={<>
                    <Button variant="secondary" href={route('admin.opd.index')} className="min-w-28"><i className="bx bx-arrow-back text-base" aria-hidden />Kembali</Button>
                    <Button type="submit" form="opd-form" loading={processing} disabled={processing} className="min-w-28">
                        {processing ? 'Menyimpan…' : 'Simpan'}
                    </Button>
                </>}
            />
            <form noValidate id="opd-form" onSubmit={submit} aria-busy={processing} className="admin-crud-form">
                <CrudFormSection title="Informasi OPD" description="Masukkan identitas dan status organisasi perangkat daerah." icon="bx-buildings">
                    <div className="admin-crud-fields opd-form-fields">
                        <FormInput id="code" label="Kode OPD" value={data.code} onChange={(event) => setData('code', event.target.value)} error={errors.code} placeholder="Contoh: 123456" />
                        <FormInput id="name" label="Nama OPD" value={data.name} onChange={(event) => setData('name', event.target.value)} error={errors.name} placeholder="Nama organisasi perangkat daerah" />
                        <FormSelect
                            id="is_active"
                            label="Status"
                            value={data.is_active ? '1' : '0'}
                            onChange={(event) => setData('is_active', event.target.value === '1')}
                            error={errors.is_active}
                            emptyOption={null}
                            options={[{ value: '1', label: 'Aktif' }, { value: '0', label: 'Nonaktif' }]}
                        />
                    </div>
                </CrudFormSection>
            </form>
        </div>
    </AdminLayout>;
}
