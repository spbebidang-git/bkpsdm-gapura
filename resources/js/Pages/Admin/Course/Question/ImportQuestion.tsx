import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import ErrorMessage from '@/Components/ErrorMessage';
import useTransientFieldError from '@/Components/useTransientFieldError';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import '@/../css/admin-course-index.css';
import '@/../css/admin-assessment.css';

export default function ImportQuestion({ course, activity, canImport = true }) {
    const input = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, progress, reset } = useForm<{ content: File | null }>({ content: null });
    const { containerRef: contentRef, visibleError: contentError, dismissError: dismissContentError } = useTransientFieldError(errors.content);
    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(route('admin.course.activity.import-question.store', [course.slug, activity.id]), { forceFormData: true });
    };
    const clearFile = () => { dismissContentError(); reset('content'); if (input.current) input.current.value = ''; };
    return <AdminLayout title="Impor soal" hideTitle>
        <Head title={`Impor soal · ${activity.title}`} />
        <div className="assessment-admin assessment-form course-index">
            <CrudPageHeader title="Impor soal" description={`${course.title} · ${activity.title}. Tambahkan varian beserta soalnya dari file Excel.`} actions={<>
                <Button variant="secondary" href={route('admin.course.activity.variant.index', [course.slug, activity.id])}><i className="bx bx-arrow-back" aria-hidden />Kembali</Button>
                <Button type="submit" form="import-form" loading={processing} disabled={!canImport || !data.content}>{processing ? 'Mengimpor…' : 'Impor soal'}</Button>
            </>} />
            <div className="assessment-notice" role="status"><i className="bx bx-info-circle" aria-hidden /><p>{canImport ? 'Impor hanya dapat dilakukan satu kali, sebelum varian atau soal ditambahkan manual. Periksa isi file sebelum mengimpor.' : 'Tes ini sudah memiliki varian atau pernah diimpor. Kelola soal melalui daftar varian.'}</p></div>
            <form id="import-form" noValidate onSubmit={submit} aria-busy={processing} className="admin-crud-form">
                <CrudFormSection title="Siapkan file Excel" description="Gunakan template resmi agar nama sheet dan kolom sesuai." icon="bx-spreadsheet">
                    <ol className="assessment-steps">
                        {[
                            ['Unduh template', 'Gunakan format varian dan soal yang tersedia.'],
                            ['Lengkapi data', 'Isi kode varian, pertanyaan, empat pilihan, kunci jawaban, dan skor.'],
                            ['Unggah dan impor', 'Pilih file yang telah diperiksa, kemudian klik Impor soal.'],
                        ].map(([title, description], index) => <li key={title}><span>{index + 1}</span><div><h3>{title}</h3><p>{description}</p></div></li>)}
                    </ol>
                    <div><a className="course-index-create" href={route('admin.course.activity.import-question.download', [course.slug, activity.id])}><i className="bx bx-download" aria-hidden />Unduh template Excel</a></div>
                </CrudFormSection>
                <CrudFormSection title="Berkas soal" description="Format .xlsx atau .xls, ukuran maksimal 2 MB." icon="bx-cloud-upload">
                    <div ref={contentRef} className={`assessment-upload ${contentError ? 'is-error' : ''}`}>
                        <i className="bx bx-cloud-upload" aria-hidden />
                        <label htmlFor="content">Pilih file Excel</label>
                        <p>Varian dan pertanyaan akan ditambahkan sekaligus.</p>
                        <input ref={input} id="content" type="file" accept=".xlsx,.xls" disabled={processing || !canImport} onChange={(event) => { dismissContentError(); setData('content', event.target.files?.[0] ?? null); }} aria-invalid={Boolean(contentError)} aria-describedby={contentError ? 'content-error' : undefined} />
                    </div>
                    {data.content && <div className="assessment-file"><span><i className="bx bx-file" aria-hidden /> {data.content.name} · {(data.content.size / 1024).toLocaleString('id-ID', { maximumFractionDigits: 0 })} KB</span><Button size="sm" variant="secondary" onClick={clearFile} disabled={processing}>Hapus pilihan</Button></div>}
                    <div id="content-error"><ErrorMessage message={contentError} /></div>
                    {progress && <div role="status" className="text-sm"><progress value={progress.percentage} max={100} className="w-full" aria-label="Progres unggah" />Mengunggah {progress.percentage}%</div>}
                </CrudFormSection>
            </form>
        </div>
    </AdminLayout>;
}
