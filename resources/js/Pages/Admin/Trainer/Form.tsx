import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import ErrorMessage from '@/Components/ErrorMessage';
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
    opd_ids?: number[];
    auth_source?: string;
    source_label?: string;
};
type Trainer = Profile & {
    source: 'local' | 'user';
    user_id?: string | null;
    opd_id?: number | null;
    auth_source?: string | null;
    email?: string | null;
};
type TrainerForm = {
    source: 'local' | 'user';
    user_id: string;
    name: string;
    jabatan: string;
    photo: File | null;
    opd_ids: string[];
    email: string;
    password: string;
    password_confirmation: string;
};

function ProfilePreview({ profile }: { profile: Profile }) {
    return <div className="trainer-profile">
        <img src={profile.photo_src || '/assets/media/images/static/default-profile.jpg'} alt="Foto pemateri" />
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
    const localAccount = !linkedUser || trainer?.auth_source === 'local';
    const title = isEdit ? 'Edit pemateri' : linkedUser ? 'Daftarkan pengguna sebagai pemateri' : 'Buat pemateri';
    const { data, setData, post, transform, processing, errors } = useForm<TrainerForm>({
        opd_ids: (trainer?.opd_ids ?? (trainer?.opd_id ? [trainer.opd_id] : [])).map(String),
        source,
        user_id: trainer?.user_id ?? '',
        name: trainer?.name ?? '',
        jabatan: trainer?.jabatan ?? '',
        photo: null,
        email: trainer?.email ?? '',
        password: '',
        password_confirmation: '',
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
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (processing) return;

        transform((values) => ({
            source: values.source,
            ...(values.source === 'user' && !isEdit
                ? { user_id: values.user_id, opd_ids: values.opd_ids }
                : { name: values.name, jabatan: values.jabatan, photo: values.photo, opd_ids: values.opd_ids,
                    ...(localAccount ? { email: values.email,
                        password: values.password, password_confirmation: values.password_confirmation } : {}) }),
            ...(isEdit ? { _method: 'PUT' } : {}),
        }));
        post(isEdit ? route('admin.trainer.update', trainer!.id) : route('admin.trainer.store'), { forceFormData: true });
    };

    const content = <CrudFormSection
        title={linkedUser ? (isEdit ? 'Profil pengguna pemateri' : 'Pilih pengguna') : 'Informasi pemateri'}
        icon="bx-user"
        description={linkedUser
            ? (isEdit ? 'Perubahan diterapkan pada akun pengguna. Data Kedaton SSO dapat tersinkron kembali.' : 'Pilih akun dengan OPD aktif, atau tetapkan OPD untuk akun GAPURA lokal.')
            : 'Lengkapi data pemateri lokal.'}
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
                const userOpdIds = user?.opd_id && opds.some((opd) => String(opd.id) === String(user.opd_id))
                    ? [String(user.opd_id)] : [];
                setData((current) => ({ ...current, user_id: userId, opd_ids: userOpdIds }));
            }}
            small={users.length
                ? 'Menampilkan akun dari semua sumber dengan OPD aktif yang belum menjadi trainer.'
                : 'Tidak ada pengguna yang memenuhi syarat.'}
        />}
        {linkedUser && !isEdit && selectedUser && <ProfilePreview profile={selectedUser} />}
        {(!linkedUser || isEdit) && <div className="trainer-details-grid">
            <FormInput id="name" label="Nama" isRequired value={data.name} onChange={(event) => setData('name', event.target.value)} error={errors.name} />
            {localAccount &&
                <FormInput id="email" label="Email" type="email" isRequired value={data.email} onChange={(event) => setData('email', event.target.value)} error={errors.email} />
            }
            <FormInput id="jabatan" label="Jabatan" value={data.jabatan} onChange={(event) => setData('jabatan', event.target.value)} error={errors.jabatan} small="Opsional." />
            {localAccount && <div className="trainer-password-fields">
                <FormInput id="password" label={linkedUser && isEdit ? 'Password baru' : 'Password'} type="password" isRequired={!linkedUser} value={data.password} onChange={(event) => setData('password', event.target.value)} error={errors.password} small={linkedUser && isEdit ? 'Kosongkan jika tidak ingin mengubah password.' : 'Minimal 8 karakter.'} />
                <FormInput id="password_confirmation" label="Konfirmasi password" type="password" isRequired={!linkedUser} value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} error={errors.password_confirmation} />
            </div>}
            <div className="trainer-photo-field">
                <label htmlFor="photo" className="trainer-photo-label">Foto</label>
                <label htmlFor="photo" className={`trainer-photo-upload ${errors.photo ? 'has-error' : ''}`}>
                    <img src={photoPreview || '/assets/media/images/static/default-profile.jpg'} alt="Pratinjau foto pemateri" />
                    <span className="trainer-photo-copy">
                        <strong>Klik untuk upload</strong>
                        <span title={data.photo?.name}>{data.photo?.name || 'PNG/JPG · Maks. 2 MB'}</span>
                    </span>
                    <input id="photo" type="file" className="sr-only" accept="image/png,image/jpeg" aria-invalid={Boolean(errors.photo)}
                        onChange={(event) => setData('photo', event.target.files?.[0] ?? null)} />
                </label>
                <ErrorMessage message={errors.photo} />
            </div>
        </div>}
        {(!linkedUser || isEdit || selectedUser) && <div className="trainer-opd-picker">
            <div><strong>OPD pemateri <span aria-hidden="true">*</span></strong><p>OPD menunjukkan afiliasi pemateri. Hak mengelola kelas mengikuti penugasan kelas.</p></div>
            <div className="trainer-opd-actions">
                <button type="button" onClick={() => setData('opd_ids', opds.map((opd) => String(opd.id)))} disabled={opds.length === 0 || data.opd_ids.length === opds.length}>Pilih semua</button>
                <button type="button" onClick={() => setData('opd_ids', [])} disabled={data.opd_ids.length === 0}>Reset</button>
            </div>
            <div className="trainer-opd-options">{opds.map((opd) => <label key={opd.id}>
                <input type="checkbox" value={opd.id} checked={data.opd_ids.includes(String(opd.id))}
                    onChange={(event) => setData('opd_ids', event.target.checked
                        ? [...new Set([...data.opd_ids, String(opd.id)])]
                        : data.opd_ids.filter((id) => id !== String(opd.id)))} />
                <span>{opd.name}</span>
            </label>)}</div>
            {data.opd_ids.length === 0 && !errors.opd_ids && <p className="trainer-opd-hint">Pilih minimal satu OPD agar form dapat disimpan.</p>}
            {errors.opd_ids && <ErrorMessage message={errors.opd_ids} />}
        </div>}
    </CrudFormSection>;

    return <AdminLayout title={title} hideTitle>
        <Head title={title} />
        <div className="trainer-admin admin-crud-form">
            <CrudPageHeader
                title={title}
                description={linkedUser ? 'Kelola profil dan OPD akun pemateri.' : 'Kelola pemateri yang dapat dipilih pada kelas.'}
                actions={<>
                    <Button href={route('admin.trainer.index')} variant="secondary"><i className="bx bx-arrow-back text-base" aria-hidden />Kembali</Button>
                    <Button type="submit" form="trainer-form" loading={processing} disabled={processing}>
                        {processing ? 'Menyimpan…' : linkedUser && !isEdit ? 'Daftarkan sebagai pemateri' : 'Simpan'}
                    </Button>
                </>}
            />
            <form id="trainer-form" noValidate onSubmit={submit}>{content}</form>
        </div>
    </AdminLayout>;
}
