import {
    btnDanger,
    btnPrimary,
    btnPrimarySm,
    btnSecondary,
    card,
    fieldError,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
    tableClass,
    tableScroller,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';
import Swal from 'sweetalert2';
import ModalAddSchedule from './Partials/ModalAddSchedule';

function ErrorMessage({ message }) {
    return message ? <p className={fieldError}>{message}</p> : null;
}

const Create = ({ categories = [], employee_status = [], trainers = [] }) => {
    const { errors = {} } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [data, setData] = useState([]);
    const [thumbnailPreview, setThumbnailPreview] = useState(null);
    const [values, setValues] = useState({
        type: '',
        category: '',
        title: '',
        short_description: '',
        description: '',
        thumbnail: null,
        teaser_video: '',
        expired: '',
        jam_pelajaran: '',
        status: '',
        employee_status: [],
        trainers: [],
    });

    const handleChange = (e) => {
        const { id, value, checked, files, type: inputType } = e.target;
        if (inputType === 'checkbox' && id.startsWith('employee_status_')) {
            setValues((current) => ({
                ...current,
                employee_status: checked
                    ? [...current.employee_status, value]
                    : current.employee_status.filter((status) => String(status) !== String(value)),
            }));
            return;
        }
        if (inputType === 'checkbox' && id.startsWith('trainer_')) {
            setValues((current) => ({
                ...current,
                trainers: checked
                    ? [...current.trainers, value]
                    : current.trainers.filter((trainer) => String(trainer) !== String(value)),
            }));
            return;
        }
        setValues((v) => ({
            ...v,
            [id]: inputType === 'file' ? files?.[0] ?? null : value,
        }));
    };

    useEffect(() => {
        if (!values.thumbnail) {
            setThumbnailPreview(null);
            return undefined;
        }

        const previewUrl = URL.createObjectURL(values.thumbnail);
        setThumbnailPreview(previewUrl);
        return () => URL.revokeObjectURL(previewUrl);
    }, [values.thumbnail]);

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('type', values.type);
        formData.append('category', values.category);
        values.employee_status.forEach((id) => formData.append('employee_status[]', id));
        values.trainers.forEach((id) => formData.append('trainers[]', id));
        formData.append('title', values.title);
        formData.append('short_description', values.short_description);
        formData.append('description', values.description);
        if (values.thumbnail) formData.append('thumbnail', values.thumbnail);
        formData.append('teaser_video', values.teaser_video);
        formData.append('expired', values.expired);
        formData.append('jam_pelajaran', values.jam_pelajaran);
        formData.append('status', values.status);
        if (values.type === 'schedule' && data.length) formData.append('schedules', JSON.stringify(data));

        router.post('/admin/course', formData, {
            onSuccess: () => Swal.fire('Sukses', 'Kelas berhasil dibuat', 'success'),
        });
    };

    return (
        <AdminLayout title="Buat Kelas" hideTitle>
            <Head title="Buat Kelas" />

            <div className="max-w-none">
                <div className={pageHeader}>
                    <div>
                        <p className={pageEyebrow}>Kelas</p>
                        <h1 className={pageTitle}>Buat kelas</h1>
                        <p className={pageDescription}>Susun informasi utama, atur peserta, lalu tentukan bagaimana kelas dipublikasikan.</p>
                    </div>
                    <Link href={route('admin.course.index')} className={btnSecondary}>
                        <i className="bx bx-left-arrow-alt text-lg" aria-hidden />
                        Kembali
                    </Link>
                </div>

                <form onSubmit={handleSubmit} className={`${card} mt-6 max-w-none space-y-6 p-5 sm:p-7`}>
                    <div className="space-y-6">
                        <div className="space-y-6">

                            <div>
                                <label htmlFor="title" className="form-label">Judul kelas <span className="text-[#A43B2F]">*</span></label>
                                <input
                                    id="title"
                                    type="text"
                                    value={values.title}
                                    onChange={handleChange}
                                    className="form-control"
                                    placeholder="Contoh: Dasar Manajemen Risiko"
                                    required
                                />
                                <p className="mt-1.5 text-xs text-steel">Gunakan judul yang jelas dan mudah dicari peserta.</p>
                                <ErrorMessage message={errors.title} />
                            </div>

                            <div>
                                <label htmlFor="short_description" className="form-label">Deskripsi singkat <span className="text-[#A43B2F]">*</span></label>
                                <textarea
                                    id="short_description"
                                    value={values.short_description}
                                    onChange={handleChange}
                                    rows={3}
                                    className="form-control resize-y"
                                    placeholder="Ringkas manfaat dan materi utama kelas dalam beberapa kalimat."
                                    required
                                />
                                <ErrorMessage message={errors.short_description} />
                            </div>

                            <div>
                                <div className="space-y-6">
                                    <div>
                                        <label htmlFor="thumbnail" className="form-label">Gambar sampul <span className="text-[#A43B2F]">*</span></label>
                                        <div className="overflow-hidden rounded-xl border border-hairline bg-surface-soft p-3 sm:p-4">
                                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                                                <div className="relative aspect-video w-full shrink-0 overflow-hidden rounded-lg border border-hairline bg-canvas sm:w-56">
                                                    {thumbnailPreview ? (
                                                        <img src={thumbnailPreview} alt="Pratinjau gambar sampul" className="h-full w-full object-cover" />
                                                    ) : (
                                                        <span className="flex h-full w-full flex-col items-center justify-center gap-1 text-steel">
                                                            <i className="bx bx-image-alt text-3xl" aria-hidden />
                                                            <span className="text-xs">Belum ada gambar</span>
                                                        </span>
                                                    )}
                                                    {thumbnailPreview && (
                                                        <span className="absolute bottom-2 left-2 rounded bg-black/60 px-2 py-1 text-[11px] font-medium text-white">Pratinjau baru</span>
                                                    )}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-sm font-semibold text-ink">{values.thumbnail ? 'Gambar siap digunakan' : 'Tambahkan gambar sampul'}</p>
                                                    <p className="mt-1 text-xs leading-5 text-steel">Gunakan gambar PNG atau JPG dengan ukuran maksimal 10 MB. Rasio lanskap 16:9 direkomendasikan.</p>
                                                    <label htmlFor="thumbnail" className="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-primary/25 bg-canvas px-3 py-2 text-xs font-semibold text-primary-pressed transition-colors hover:border-primary/50 hover:bg-primary-mist">
                                                        <i className="bx bx-image-add text-base" aria-hidden />
                                                        {values.thumbnail?.name || 'Pilih gambar sampul'}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <input id="thumbnail" type="file" onChange={handleChange} className="sr-only" accept="image/*" required />
                                        <ErrorMessage message={errors.thumbnail} />
                                    </div>

                                    <div>
                                        <p className="form-label">Deskripsi lengkap <span className="text-[#A43B2F]">*</span></p>
                                        <div className="[&_.ql-container]:min-h-48 [&_.ql-container]:rounded-b-lg [&_.ql-container]:border-hairline [&_.ql-editor]:min-h-48 [&_.ql-toolbar]:rounded-t-lg [&_.ql-toolbar]:border-hairline [&_.ql-toolbar]:bg-surface-soft">
                                            <ReactQuill
                                            theme="snow"
                                            value={values.description}
                                            onChange={(value) => setValues((current) => ({ ...current, description: value }))}
                                            placeholder="Jelaskan tujuan, materi, metode pembelajaran, dan hasil yang diharapkan."
                                            />
                                        </div>
                                        <ErrorMessage message={errors.description} />
                                    </div>

                                    <div>
                                        <label htmlFor="teaser_video" className="form-label">Tautan video teaser <span className="font-normal text-stone">(opsional)</span></label>
                                        <input
                                            id="teaser_video"
                                            type="url"
                                            value={values.teaser_video}
                                            onChange={handleChange}
                                            className="form-control"
                                            placeholder="https://youtube.com/watch?v=..."
                                        />
                                        <ErrorMessage message={errors.teaser_video} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-6 border-t border-hairline pt-6">

                            <div className="space-y-5">
                                <div>
                                    <label htmlFor="type" className="form-label">Jenis kelas <span className="text-[#A43B2F]">*</span></label>
                                    <select
                                        id="type"
                                        value={values.type}
                                        onChange={handleChange}
                                        className="form-control"
                                        required
                                    >
                                        <option value="">Pilih jenis</option>
                                        <option value="unschedule">Tidak Terjadwal</option>
                                        <option value="schedule">Terjadwal</option>
                                    </select>
                                    <ErrorMessage message={errors.type} />
                                </div>

                                <div>
                                    <label htmlFor="category" className="form-label">Kategori <span className="text-[#A43B2F]">*</span></label>
                                    <select
                                        id="category"
                                        value={values.category}
                                        onChange={handleChange}
                                        className="form-control"
                                        required
                                    >
                                        <option value="">Pilih kategori</option>
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>{c.title}</option>
                                        ))}
                                    </select>
                                    <ErrorMessage message={errors.category} />
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label htmlFor="status" className="form-label">Status <span className="text-[#A43B2F]">*</span></label>
                                        <select
                                            id="status"
                                            value={values.status}
                                            onChange={handleChange}
                                            className="form-control"
                                            required
                                        >
                                            <option value="">Pilih</option>
                                            <option value="publish">Publikasi</option>
                                            <option value="draft">Draft</option>
                                        </select>
                                        <ErrorMessage message={errors.status} />
                                    </div>
                                    <div>
                                        <label htmlFor="jam_pelajaran" className="form-label">Jam pelajaran <span className="text-[#A43B2F]">*</span></label>
                                        <input
                                            id="jam_pelajaran"
                                            type="text"
                                            value={values.jam_pelajaran}
                                            onChange={handleChange}
                                            className="form-control"
                                            placeholder="Contoh: 8 JP"
                                            required
                                        />
                                        <ErrorMessage message={errors.jam_pelajaran} />
                                    </div>
                                </div>

                                {values.type !== 'schedule' && <div>
                                    <label htmlFor="expired" className="form-label">Kedaluwarsa (jam) <span className="text-[#A43B2F]">*</span></label>
                                    <input
                                        id="expired"
                                        type="number"
                                        min="0"
                                        value={values.expired}
                                        onChange={handleChange}
                                        className="form-control"
                                        placeholder="Contoh: 72"
                                        required
                                    />
                                    <p className="mt-1.5 text-xs text-steel">Waktu akses setelah peserta memulai kelas.</p>
                                    <ErrorMessage message={errors.expired} />
                                </div>}
                            </div>

                            <div className="border-t border-hairline pt-5">
                                <label className="form-label">Status peserta</label>
                                <p className="mb-3 text-xs leading-5 text-steel">Pilih kelompok pegawai yang dapat mengikuti kelas ini.</p>
                                <div className="grid grid-cols-2 gap-2">
                                    {employee_status.map((status) => {
                                        const statusId = status?.id ?? status;
                                        const statusLabel = status?.title ?? String(status);
                                        const isChecked = values.employee_status.some((value) => String(value) === String(statusId));
                                        return (
                                            <label key={statusId} className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-sm font-medium transition-colors ${isChecked ? 'border-primary/45 bg-primary-mist text-primary-pressed' : 'border-hairline bg-canvas text-charcoal hover:border-primary/30'}`}>
                                                <input
                                                    type="checkbox"
                                                    id={`employee_status_${statusId}`}
                                                    value={statusId}
                                                    checked={isChecked}
                                                    onChange={handleChange}
                                                    className="h-4 w-4 rounded border-hairline text-primary focus:ring-primary"
                                                />
                                                <span className="truncate">{statusLabel}</span>
                                            </label>
                                        );
                                    })}
                                </div>
                                <ErrorMessage message={errors.employee_status} />
                            </div>

                            <div className="border-t border-hairline pt-5">
                                <label className="form-label">Trainer</label>
                                <p className="mb-3 text-xs leading-5 text-steel">Pilih trainer yang mengampu kelas ini.</p>
                                <div className="grid grid-cols-2 gap-2">
                                    {trainers.map((trainer) => {
                                        const trainerId = trainer?.id ?? trainer;
                                        const trainerLabel = trainer?.name ?? String(trainer);
                                        const isChecked = values.trainers.some((value) => String(value) === String(trainerId));
                                        return (
                                            <label key={trainerId} className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-sm font-medium transition-colors ${isChecked ? 'border-primary/45 bg-primary-mist text-primary-pressed' : 'border-hairline bg-canvas text-charcoal hover:border-primary/30'}`}>
                                                <input
                                                    type="checkbox"
                                                    id={`trainer_${trainerId}`}
                                                    value={trainerId}
                                                    checked={isChecked}
                                                    onChange={handleChange}
                                                    className="h-4 w-4 rounded border-hairline text-primary focus:ring-primary"
                                                />
                                                <span className="truncate">{trainerLabel}</span>
                                            </label>
                                        );
                                    })}
                                    {!trainers.length && (
                                        <p className="text-sm text-steel">Belum ada data trainer.</p>
                                    )}
                                </div>
                                <ErrorMessage message={errors.trainers} />
                            </div>
                        </div>
                    </div>

                    {values.type === 'schedule' && (
                        <section className={`${card} overflow-hidden`} aria-labelledby="new-schedule-title">
                            <header className="flex flex-col gap-3 border-b border-hairline px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 id="new-schedule-title" className="font-semibold text-ink">Jadwal kegiatan</h2>
                                    <p className="mt-1 text-sm text-steel">Tambahkan minimal satu jadwal.</p>
                                </div>
                                <button type="button" className={btnPrimarySm} onClick={() => setIsModalOpen(true)}>Tambah jadwal</button>
                            </header>
                            <div className={tableScroller}>
                                <table className={`${tableClass} min-w-[620px]`}>
                                    <thead><tr><th>No.</th><th>Tanggal</th><th>Waktu</th><th className="text-right">Kuota</th><th className="text-right">Aksi</th></tr></thead>
                                    <tbody>
                                        {data.length > 0 ? data.map((schedule, index) => (
                                            <tr key={schedule.id ?? `${schedule.date}-${index}`}>
                                                <td>{index + 1}</td>
                                                <td>{schedule.date}</td>
                                                <td>{schedule.time_from}–{schedule.time_to}</td>
                                                <td className="text-right tabular-nums">{schedule.quota}</td>
                                                <td className="text-right"><button type="button" className={btnDanger} onClick={() => setData((current) => current.filter((_, rowIndex) => rowIndex !== index))}>Hapus</button></td>
                                            </tr>
                                        )) : (
                                            <tr><td colSpan={5} className="admin-table-state"><p className="font-semibold text-ink">Belum ada jadwal</p><p className="text-sm text-steel">Gunakan tombol “Tambah jadwal”.</p></td></tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            <ErrorMessage message={errors.schedules} />
                        </section>
                    )}

                    <div className="flex flex-col-reverse gap-3 border-t border-hairline pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-xs text-steel"><span className="text-[#A43B2F]">*</span> Wajib diisi sebelum menyimpan kelas.</p>
                        <div className="flex flex-col-reverse gap-3 sm:flex-row">
                            <Link href={route('admin.course.index')} className={btnSecondary}>Batal</Link>
                            <button type="submit" className={btnPrimary}>
                                <i className="bx bx-save text-lg" aria-hidden />
                                Simpan Kelas
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <ModalAddSchedule
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onScheduleAdded={(schedule) => setData((current) => [...current, schedule])}
            />
        </AdminLayout>
    );
};

export default Create;
