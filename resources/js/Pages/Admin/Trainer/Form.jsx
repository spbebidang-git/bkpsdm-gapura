import {
    btnPrimary,
    btnSecondary,
    card,
    fieldError,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Form({ trainer }) {
    const isEdit = !!trainer;
    const { data, setData, post, processing, errors } = useForm({
        name: trainer?.name ?? '',
        jabatan: trainer?.jabatan ?? '',
        photo: null,
    });

    const currentPhoto = trainer?.photo_src || null;
    const [photoPreview, setPhotoPreview] = useState(currentPhoto);

    useEffect(() => {
        if (!data.photo) {
            setPhotoPreview(currentPhoto);
            return undefined;
        }

        const previewUrl = URL.createObjectURL(data.photo);
        setPhotoPreview(previewUrl);
        return () => URL.revokeObjectURL(previewUrl);
    }, [currentPhoto, data.photo]);

    const submit = (e) => {
        e.preventDefault();
        const payload = new FormData();
        payload.append('name', data.name);
        payload.append('jabatan', data.jabatan ?? '');
        if (data.photo) payload.append('photo', data.photo);

        if (isEdit) {
            payload.append('_method', 'PUT');
            post(route('admin.trainer.update', trainer.id), { data: payload, forceFormData: true });
        } else {
            post(route('admin.trainer.store'), { data: payload, forceFormData: true });
        }
    };

    return (
        <AdminLayout title={isEdit ? 'Edit Trainer' : 'Buat Trainer'} hideTitle>
            <Head title={isEdit ? 'Edit Trainer' : 'Buat Trainer'} />
            <div className="max-w-none">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Trainer</p>
                        <h1 className={pageTitle}>{isEdit ? 'Edit trainer' : 'Buat trainer'}</h1>
                        <p className={pageDescription}>
                            {isEdit ? 'Perbarui data trainer ini.' : 'Tambahkan data trainer yang dapat dipilih pada kelas.'}
                        </p>
                    </div>
                    <Link href={route('admin.trainer.index')} className={btnSecondary}>
                        <i className="bx bx-left-arrow-alt text-lg" aria-hidden />
                        Kembali
                    </Link>
                </div>

                <form onSubmit={submit} className={`${card} mt-6 max-w-none space-y-6 p-5 sm:p-7`}>
                    <div className="space-y-6">
                        <div>
                            <label htmlFor="name" className="form-label">
                                Nama <span className="text-[#A43B2F]">*</span>
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="form-control"
                                placeholder="Contoh: Dr. Budi Santoso, M.Pd."
                                required
                            />
                            {errors.name && <p className={fieldError}>{errors.name}</p>}
                        </div>

                        <div>
                            <label htmlFor="jabatan" className="form-label">Jabatan</label>
                            <input
                                id="jabatan"
                                type="text"
                                value={data.jabatan ?? ''}
                                onChange={(e) => setData('jabatan', e.target.value)}
                                className="form-control"
                                placeholder="Contoh: Dosen / Pranata Pelatih"
                            />
                            <p className="mt-1.5 text-xs text-steel">Opsional.</p>
                            {errors.jabatan && <p className={fieldError}>{errors.jabatan}</p>}
                        </div>

                        <div>
                            <label htmlFor="photo" className="form-label">Foto</label>
                            <div className="overflow-hidden rounded-xl border border-hairline bg-surface-soft p-3 sm:p-4">
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                                    <div className="h-24 w-24 shrink-0 overflow-hidden rounded-full border border-hairline bg-canvas">
                                        {photoPreview ? (
                                            <img src={photoPreview} alt="Pratinjau foto trainer" className="h-full w-full object-cover" />
                                        ) : (
                                            <div className="flex h-full w-full items-center justify-center text-steel">
                                                <i className="bx bx-user text-3xl" aria-hidden />
                                            </div>
                                        )}
                                    </div>
                                    <div className="min-w-0 space-y-1.5">
                                        <input
                                            id="photo"
                                            type="file"
                                            accept="image/png,image/jpeg"
                                            onChange={(e) => setData('photo', e.target.files?.[0] ?? null)}
                                            className="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-primary-pressed"
                                        />
                                        <p className="text-xs text-steel">PNG/JPG, maksimal 2 MB. Opsional.</p>
                                        {errors.photo && <p className={fieldError}>{errors.photo}</p>}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col-reverse gap-3 border-t border-hairline pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-xs text-steel"><span className="text-[#A43B2F]">*</span> Nama wajib diisi.</p>
                        <div className="flex flex-col-reverse gap-3 sm:flex-row">
                            <Link href={route('admin.trainer.index')} className={btnSecondary}>Batal</Link>
                            <button type="submit" disabled={processing} className={btnPrimary}>
                                <i className="bx bx-save text-lg" aria-hidden />
                                {processing ? 'Menyimpan...' : isEdit ? 'Simpan perubahan' : 'Simpan Trainer'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

