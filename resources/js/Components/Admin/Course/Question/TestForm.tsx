import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import ErrorMessage from '@/Components/ErrorMessage';
import FormCheckbox from '@/Components/FormCheckbox';
import FormInput from '@/Components/FormInput';
import FormLabel from '@/Components/FormLabel';
import FormTextArea from '@/Components/FormTextArea';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import '@/../css/admin-course-form.css';
import '@/../css/admin-course-test-form.css';

type TestKind = 'pre-test' | 'post-test';
type TestValues = {
    title: string; description: string; instruction: string; value: string | number;
    timer: string | number; min_score: string | number; repeatable: boolean; is_loop: boolean;
};
type TestActivity = {
    id: number | string; title: string; description: string; instruction: string;
    value: string | number; timer: string | number; min_score: string | number; is_loop: boolean;
} | null;

export default function TestForm({ slug, kind, activity = null }: { slug: string; kind: TestKind; activity?: TestActivity }) {
    const isPreTest = kind === 'pre-test';
    const isEdit = Boolean(activity);
    const label = isPreTest ? 'Pre-Test / Kuis' : 'Post-Test';
    const title = isEdit
        ? `Ubah ${label}`
        : (isPreTest ? 'Pengaturan Pre-Test / Kuis' : 'Pengaturan Post-Test');
    const repeatField = isPreTest ? 'repeatable' : 'is_loop';
    const { data, setData, transform, post, put, processing, errors } = useForm<TestValues>({
        title: activity?.title ?? '',
        description: activity?.description ?? '',
        instruction: activity?.instruction ?? '',
        value: activity?.value ?? '',
        timer: activity?.timer ? Number(activity.timer) / 60 : '',
        min_score: activity?.min_score ?? 0,
        repeatable: isPreTest ? Boolean(activity?.is_loop) : false,
        is_loop: isPreTest ? false : Boolean(activity?.is_loop),
    });
    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        transform((values) => {
            const { repeatable, is_loop, ...common } = values;
            return { ...common, [repeatField]: values[repeatField] } as TestValues;
        });
        if (isEdit && activity) put(`/admin/course/${slug}/activity/${kind}/${activity.id}`);
        else post(`/admin/course/${slug}/activity/${kind}`);
    };
    const handleChange = (event: any) => {
        const { id, type, value, checked } = event.target;
        setData(id, type === 'checkbox' ? checked : value);
    };
    const instructions = isPreTest
        ? 'Atur soal pilihan ganda untuk menilai pemahaman awal pengguna.'
        : 'Atur evaluasi akhir sebagai salah satu syarat penerbitan sertifikat digital pengguna.';

    return <AdminLayout title={title} hideTitle breadcrumbs={[
        { label: 'Kegiatan', href: route('admin.course.activities.index', slug) },
        { label: 'Aktivitas', href: `/admin/course/${slug}/activity` },
        { label },
    ]}>
        <Head title={title} />
        <div className="course-index course-test-page admin-crud-form">
            <CrudPageHeader title={title} description={instructions} className="course-index-header" actions={<>
                <Button variant="secondary" href={`/admin/course/${slug}/activity`} className="min-w-28"><i className="bx bx-arrow-back" aria-hidden />Kembali</Button>
                <Button type="submit" form="course-test-form" loading={processing} disabled={processing} className="min-w-28">
                    {processing ? 'Menyimpan…' : 'Simpan'}
                </Button>
            </>} />
            <form id="course-test-form" noValidate onSubmit={handleSubmit} className="course-test-form">
                <CrudFormSection title={`Informasi ${isPreTest ? 'pre-test' : 'post-test'}`} icon="bx-detail"
                    description="Berikan nama dan ringkasan agar pengguna memahami tujuan evaluasi ini.">
                    <div className="course-test-fields">
                        <FormInput id="title" label={`Judul ${isPreTest ? 'Pre-Test / Kuis' : 'Post-Test / Kuis'}`} isRequired value={data.title} onChange={handleChange} error={errors.title}
                            small={isPreTest ? 'Gunakan judul singkat dan spesifik, misalnya “Tes Bab 1” atau “Kuis Pengantar”.' : 'Gunakan judul singkat dan spesifik, misalnya “Tes Akhir Bab” atau “Kuis Evaluasi”.'} />
                        <FormTextArea id="description" label="Deskripsi" isRequired value={data.description} onChange={handleChange} error={errors.description} rows={3}
                            small={`Berikan gambaran singkat mengenai ${isPreTest ? 'tujuan atau isi pre-test' : 'isi atau tujuan post-test'}.`} />
                    </div>
                </CrudFormSection>
                <CrudFormSection title="Pengaturan penilaian" icon="bx-slider-alt" description="Tentukan bobot, batas kelulusan, dan waktu pengerjaan pengguna.">
                    <div className="course-test-fields course-test-score-fields">
                        <FormInput id="value" label="Bobot Nilai" isRequired type="number" value={data.value} onChange={handleChange} error={errors.value} small="Contoh: 10, 20, atau 100." />
                        <FormInput id="min_score" label="Nilai Minimum Kelulusan" isRequired type="number" value={data.min_score} onChange={handleChange} error={errors.min_score} small="Nilai minimal agar pengguna dinyatakan lulus." />
                        <FormInput id="timer" label="Durasi Waktu (Menit)" isRequired type="number" min={1} value={data.timer} onChange={handleChange} error={errors.timer} placeholder="Contoh: 30" small="Masukkan durasi pengerjaan dalam menit. Contoh: 30 untuk 30 menit." />
                    </div>
                </CrudFormSection>
                <CrudFormSection title="Instruksi untuk pengguna" icon="bx-message-detail" description="Tambahkan aturan menjawab atau petunjuk teknis sebelum pengguna memulai.">
                    <div className={`course-test-editor ${errors.instruction ? 'is-invalid' : ''}`}>
                        <FormLabel title="Instruksi soal" id="instruction" isRequired={isPreTest} />
                        <ReactQuill theme="snow" value={data.instruction} onChange={(value) => setData('instruction', value)} placeholder="Tulis aturan menjawab atau petunjuk teknis untuk pengguna." />
                        <ErrorMessage message={errors.instruction} />
                    </div>
                </CrudFormSection>
                <CrudFormSection title="Opsi pengerjaan" icon="bx-refresh" description="Atur apakah pengguna dapat mencoba evaluasi lebih dari sekali.">
                    <div className="course-test-repeat-option">
                        <FormCheckbox id={repeatField} title={`Izinkan pengguna mengulang ${isPreTest ? 'pre-test' : 'post-test'}`} checked={data[repeatField]} onChange={handleChange} />
                        <p>{isPreTest ? 'Aktifkan bila pengguna dapat mencoba kembali setelah menyelesaikan pre-test.' : 'Jika diaktifkan, pengguna mendapat maksimal tiga percobaan. Jika tidak, progres kelas langsung direset saat pengguna gagal.'}</p>
                    </div>
                </CrudFormSection>
            </form>
        </div>
    </AdminLayout>;
}
