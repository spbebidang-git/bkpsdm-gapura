import {
    btnLink,
    btnPrimary,
    card,
    fieldError,
    formCard,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Index({ courses = [], importResults, importCourse }) {
    const { data, setData, post, processing, errors } = useForm({
        course_id: '',
        finished_at: '',
        file: null,
        create_missing_users: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.import-learn.store'), { forceFormData: true });
    };

    return (
        <AdminLayout title="Import Data Belajar" hideTitle>
            <Head title="Import Data Belajar" />

            <div className={pageHeader}>
                <div className="min-w-0">
                    <p className={pageEyebrow}>Operasional</p>
                    <h1 className={pageTitle}>Import data belajar</h1>
                    <p className={pageDescription}>Masukkan riwayat penyelesaian peserta dari file Excel atau CSV.</p>
                </div>
            </div>

            <form onSubmit={submit} className={`${formCard} max-w-xl space-y-5`}>
                <div>
                    <label className="form-label">Kelas (scheduled)</label>
                    <select
                        className="form-control"
                        value={data.course_id}
                        onChange={(e) => setData('course_id', e.target.value)}
                        required
                    >
                        <option value="">Pilih kelas</option>
                        {courses.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.title}
                            </option>
                        ))}
                    </select>
                    {errors.course_id && <p className={fieldError}>{errors.course_id}</p>}
                </div>
                <div>
                    <label className="form-label">Tanggal selesai</label>
                    <input
                        type="date"
                        className="form-control"
                        value={data.finished_at}
                        onChange={(e) => setData('finished_at', e.target.value)}
                        required
                    />
                    {errors.finished_at && (
                        <p className={fieldError}>{errors.finished_at}</p>
                    )}
                </div>
                <div>
                    <label className="form-label">File Excel</label>
                    <input
                        type="file"
                        className="form-control"
                        accept=".xlsx,.xls,.csv"
                        onChange={(e) => setData('file', e.target.files[0])}
                        required
                    />
                    {errors.file && <p className={fieldError}>{errors.file}</p>}
                    <a href={route('admin.import-learn.template')} className={`${btnLink} mt-1 inline-block text-xs`}>
                        Unduh template
                    </a>
                </div>
                <label className="flex items-center gap-2 text-sm text-charcoal">
                    <input
                        type="checkbox"
                        checked={data.create_missing_users}
                        onChange={(e) => setData('create_missing_users', e.target.checked)}
                    />
                    Buat akun peserta jika NIP belum terdaftar
                </label>
                <button type="submit" disabled={processing} className={btnPrimary}>
                    {processing ? 'Mengimpor...' : 'Import data'}
                </button>
            </form>

            {importResults && (
                <div className={`${card} mt-6 p-4 text-sm`}>
                    <div className="font-semibold text-ink mb-2">
                        Hasil import {importCourse ? `· ${importCourse}` : ''}
                    </div>
                    <pre className="text-xs overflow-auto bg-surface p-3 rounded-md text-charcoal">
                        {JSON.stringify(importResults, null, 2)}
                    </pre>
                </div>
            )}
        </AdminLayout>
    );
}
