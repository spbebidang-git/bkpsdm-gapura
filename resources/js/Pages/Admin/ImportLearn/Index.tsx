import {
    btnLink,
    btnPrimary,
    card,
    formCard,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
} from '@/Components/Admin/ui';
import ErrorMessage from '@/Components/ErrorMessage';
import FormInput from '@/Components/FormInput';
import FormLabel from '@/Components/FormLabel';
import FormSelect from '@/Components/FormSelect';
import useTransientFieldError from '@/Components/useTransientFieldError';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';

export default function Index({ courses = [], importResults, importCourse }) {
    const { data, setData, post, processing, errors } = useForm({
        course_id: '',
        finished_at: '',
        file: null,
        create_missing_users: false,
    });
    const { containerRef: fileFieldRef, visibleError: fileError, dismissError: dismissFileError } = useTransientFieldError(errors.file);

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

            <form noValidate onSubmit={submit} className={`${formCard} max-w-xl space-y-5`}>
                <FormSelect
                    id="course_id"
                    label="Kelas (scheduled)"
                    required
                    value={data.course_id}
                    onChange={(e) => setData('course_id', e.target.value)}
                    error={errors.course_id}
                    options={courses.map((c) => ({ value: c.id, label: c.title }))}
                    emptyOption="Pilih kelas"
                />
                <FormInput
                    id="finished_at"
                    label="Tanggal selesai"
                    type="date"
                    required
                    value={data.finished_at}
                    onChange={(e) => setData('finished_at', e.target.value)}
                    error={errors.finished_at}
                />
                <div ref={fileFieldRef}>
                    <FormLabel title="File Excel" id="file" />
                    <input
                        id="file"
                        type="file"
                        className={`form-control ${fileError ? '!border-red-600' : ''}`}
                        accept=".xlsx,.xls,.csv"
                        onChange={(e) => { dismissFileError(); setData('file', e.target.files[0]); }}
                        aria-invalid={Boolean(fileError)}
                        required
                    />
                    <ErrorMessage message={fileError} />
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
