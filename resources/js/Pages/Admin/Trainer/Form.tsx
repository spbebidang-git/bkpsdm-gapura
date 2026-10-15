import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import ErrorMessage from '@/Components/ErrorMessage';
import FormFile from '@/Components/FormFile';
import FormInput from '@/Components/FormInput';
import FormSelect from '@/Components/FormSelect';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import '@/../css/admin-trainer.css';

type Profile = {
    id: string;
    name: string;
    nip?: string | null;
    jabatan?: string | null;
    photo_src?: string | null;
    opd?: string | null;
    opd_id?: number | null;
    auth_source?: string;
    source_label?: string;
};
type Trainer = Profile & {
    source: 'local' | 'user';
    user_id?: string | null;
    opd_id?: number | null;
    auth_source?: string | null;
};
type TrainerForm = {
    source: 'local' | 'user';
    user_id: string;
    name: string;
    jabatan: string;
    photo: File | null;
    opd_id: string;
};

function ProfilePreview({ profile }: { profile: Profile }) {
    return <div className="trainer-profile">
        <img src={profile.photo_src || '/assets/media/images/static/default-profile.jpg'} alt="Foto trainer" />
        <dl>{[['Nama', profile.name], ['NIP', profile.nip], ['Jabatan', profile.jabatan], ['OPD', profile.opd]].map(([label, value]) =>
            <div key={label}><dt>{label}</dt><dd>{value || '—'}</dd></div>
        )}</dl>
    </div>;
}

export default function Form({ trainer, users = [], opds = [], mode = 'local' }: {
    trainer?: Trainer | null;
    users?: Profile[];
    opds?: { id: number; name: string }[];
    mode?: 'local' | 'user';
}) {
    const isEdit = Boolean(trainer);
    const source = trainer?.source ?? mode;
    const linkedUser = source === 'user';
    const title = isEdit ? 'Edit trainer' : linkedUser ? 'Daftarkan pengguna sebagai trainer' : 'Buat trainer';
    const { data, setData, post, transform, processing, errors } = useForm<TrainerForm>({
        opd_id: trainer?.opd_id ? String(trainer.opd_id) : '',
        source,
        user_id: trainer?.user_id ?? '',
        name: trainer?.name ?? '',
        jabatan: trainer?.jabatan ?? '',
        photo: null,
    });
    const [photoPreview, setPhotoPreview] = useState(trainer?.photo_src ?? null);

    useEffect(() => {
        if (!data.photo) {
            setPhotoPreview(trainer?.photo_src ?? null);
            return;
        }
        const url = URL.createObjectURL(data.photo);
        setPhotoPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [data.photo, trainer?.photo_src]);

    const selectedUser = users.find((user) => user.id === data.user_id);
    const selectedLocalUser = selectedUser?.auth_source === 'local';
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (processing) return;

        transform((values) => ({
            source: values.source,
            ...(values.source === 'user' && !isEdit
                ? { user_id: values.user_id, ...(selectedLocalUser ? { opd_id: values.opd_id } : {}) }
                : { name: values.name, jabatan: values.jabatan, photo: values.photo, opd_id: values.opd_id }),
            ...(isEdit ? { _method: 'PUT' } : {}),
        }));
        post(isEdit ? route('admin.trainer.update', trainer!.id) : route('admin.trainer.store'), { forceFormData: true });
    };

    const content = <CrudFormSection
        title={linkedUser ? (isEdit ? 'Profil pengguna trainer' : 'Pilih pengguna') : 'Informasi trainer'}
        icon="bx-user"
        description={linkedUser
            ? (isEdit ? 'Perubahan diterapkan pada akun pengguna. Data Kedaton SSO dapat tersinkron kembali.' : 'Pilih akun dengan OPD aktif, atau tetapkan OPD untuk akun GAPURA lokal.')
            : 'Lengkapi data trainer lokal.'}
    >
        <ErrorMessage message={errors.source} />
        {linkedUser && !isEdit && <FormSelect
            id="user_id"
            label="Pengguna"
            isRequired
            value={data.user_id}
            error={errors.user_id}
            options={users.map((user) => ({
                value: user.id,
                label: `${user.name} — ${user.source_label || 'Pengguna'} — ${user.opd || 'OPD belum tersedia'}`,
            }))}
            emptyOption="Pilih pengguna"
            onChange={(event) => {
                const userId = event.target.value;
                const user = users.find((candidate) => candidate.id === userId);
                const userOpdId = user?.auth_source === 'local' && opds.some((opd) => String(opd.id) === String(user.opd_id))
                    ? String(user.opd_id)
                    : '';
                setData((current) => ({ ...current, user_id: userId, opd_id: userOpdId }));
            }}
            small={users.length
                ? 'Menampilkan akun dari semua sumber dengan OPD aktif yang belum menjadi trainer.'
                : 'Tidak ada pengguna yang memenuhi syarat.'}
        />}
        {linkedUser && (isEdit ? trainer : selectedUser) && <ProfilePreview profile={(isEdit ? trainer : selectedUser)!} />}
        {linkedUser && !isEdit && selectedLocalUser && <FormSelect
            id="opd_id"
            label="OPD pengguna"
            isRequired
            value={data.opd_id}
            onChange={(event) => setData('opd_id', event.target.value)}
            error={errors.opd_id}
            options={opds.map((opd) => ({ value: opd.id, label: opd.name }))}
            emptyOption="Pilih OPD"
            small="OPD ini disimpan ke akun GAPURA lokal dan menjadi asal trainer."
        />}
        {(!linkedUser || isEdit) && <>
            <div className="admin-crud-fields">
                <FormInput id="name" label="Nama" isRequired value={data.name} onChange={(event) => setData('name', event.target.value)} error={errors.name} />
                <FormInput id="jabatan" label="Jabatan" value={data.jabatan} onChange={(event) => setData('jabatan', event.target.value)} error={errors.jabatan} small="Opsional." />
            </div>
            <FormSelect
                id="opd_id"
                label={linkedUser ? 'OPD pengguna' : 'OPD trainer'}
                isRequired
                value={data.opd_id}
                onChange={(event) => setData('opd_id', event.target.value)}
                error={errors.opd_id}
                options={opds.map((opd) => ({ value: opd.id, label: opd.name }))}
                emptyOption="Pilih OPD"
                small="Trainer dapat dipilih pada kelas dengan OPD yang sama."
            />
            <FormFile
                title="photo"
                label="Foto"
                previewUrl={photoPreview}
                hasFile={Boolean(data.photo)}
                error={errors.photo}
                heading="Foto trainer"
                helperText="PNG/JPG, maksimal 2 MB. Opsional."
                buttonLabel="Pilih foto"
                accept="image/png,image/jpeg"
                onChange={(event) => setData('photo', event.target.files?.[0] ?? null)}
            />
        </>}
    </CrudFormSection>;

    return <AdminLayout title={title} hideTitle>
        <Head title={title} />
        <div className="trainer-admin admin-crud-form">
            <CrudPageHeader
                title={title}
                description={linkedUser ? 'Kelola profil dan OPD akun trainer.' : 'Kelola trainer yang dapat dipilih pada kelas.'}
                actions={<>
                    <Button href={route('admin.trainer.index')} variant="secondary"><i className="bx bx-arrow-back text-base" aria-hidden />Kembali</Button>
                    <Button type="submit" form="trainer-form" loading={processing} disabled={processing || (linkedUser && !isEdit && (!data.user_id || (selectedLocalUser && !data.opd_id)))}>
                        {processing ? 'Menyimpan…' : linkedUser && !isEdit ? 'Daftarkan sebagai trainer' : 'Simpan'}
                    </Button>
                </>}
            />
            <form id="trainer-form" noValidate onSubmit={submit}>{content}</form>
        </div>
    </AdminLayout>;
}
