import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import FormInput from '@/Components/FormInput';
import FormSelect from '@/Components/FormSelect';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import '@/../css/admin-course-index.css';
import '@/../css/admin-assessment.css';

type Variant = { id: string; code: string; variant: number; mix: boolean | number };
export default function VariantForm({ slug, activityId, variant }: { slug: string; activityId: string; variant: Variant | number }) {
    const record = typeof variant === 'object' ? variant : null;
    const title = record ? 'Ubah varian' : 'Tambah varian';
    const base = `/admin/course/${slug}/activity/variant/${activityId}`;
    const { data, setData, post, put, processing, errors } = useForm({ code: record?.code ?? '', variant: record?.variant ?? variant as number, mix: Boolean(record?.mix) });
    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (record) put(`${base}/${record.id}`);
        else post(base);
    };
    return <AdminLayout title={title} hideTitle>
        <Head title={title} />
        <div className="assessment-admin assessment-form course-index">
            <CrudPageHeader title={title} description="Atur identitas paket soal dan pengacakan pertanyaan untuk tes ini." actions={<>
                <Button variant="secondary" href={base}><i className="bx bx-arrow-back" aria-hidden />Kembali</Button>
                <Button type="submit" form="variant-form" loading={processing}>{processing ? 'Menyimpan…' : 'Simpan'}</Button>
            </>} />
            <form id="variant-form" noValidate onSubmit={submit} aria-busy={processing} className="admin-crud-form">
                <CrudFormSection title="Informasi varian" description="Setiap varian merupakan satu paket soal. Samakan jumlah soal antarvarian." icon="bx-layer">
                    <div className="admin-crud-fields variant-fields">
                        <FormInput id="code" label="Kode varian" value={data.code} onChange={(e) => setData('code', e.target.value)} error={errors.code} placeholder="Contoh: PRE-A" isRequired />
                        <FormInput id="variant" label="Nomor varian" value={data.variant} onChange={() => {}} error={errors.variant} readOnly small="Nomor diberikan otomatis." />
                        <FormSelect id="mix" label="Acak soal" value={data.mix ? '1' : '0'} onChange={(e) => setData('mix', e.target.value === '1')} error={errors.mix} emptyOption={null} options={[{ value: '0', label: 'Tidak' }, { value: '1', label: 'Ya' }]} />
                    </div>
                </CrudFormSection>
            </form>
        </div>
    </AdminLayout>;
}
