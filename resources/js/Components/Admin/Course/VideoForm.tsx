import ActivityCrudFormLayout from '@/Components/Admin/Course/ActivityCrudFormLayout';
import { CrudFormSection } from '@/Components/Admin/CrudPrimitives';
import FormInput from '@/Components/FormInput';
import FormLabel from '@/Components/FormLabel';
import FormTextArea from '@/Components/FormTextArea';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';

type VideoActivity = {
    id: number | string; title: string; description: string; instruction: string;
    value: string | number; url: string; duration: string | number;
} | null;

export default function VideoForm({ slug, activity = null }: { slug: string; activity?: VideoActivity }) {
    const isEdit = Boolean(activity);
    const formId = isEdit ? 'edit-video-form' : 'create-video-form';
    const { data, setData, post, put, processing, errors } = useForm({
        title: activity?.title ?? '',
        description: activity?.description ?? '',
        instruction: activity?.instruction ?? '',
        value: activity?.value ?? '',
        url: activity?.url ?? '',
        duration: activity?.duration ? Number(activity.duration) / 60 : '',
    });
    const handleChange = (event: any) => {
        const { id, type, value, checked } = event.target;
        setData(id, type === 'checkbox' ? checked : value);
    };
    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (isEdit && activity) put(`/admin/course/${slug}/activity/video/${activity.id}`);
        else post(`/admin/course/${slug}/activity/video`);
    };

    return <ActivityCrudFormLayout slug={slug} formId={formId} processing={processing}
        title={isEdit ? 'Ubah Presentasi Video' : 'Presentasi Video'}
        description="Tambahkan video pembelajaran beserta URL yang dapat diakses peserta.">
        <form id={formId} noValidate onSubmit={handleSubmit} className="course-test-form w-full">
            <CrudFormSection title="Informasi video" icon="bx-play-circle" description="Berikan judul dan gambaran materi yang akan ditonton peserta.">
                <div className="course-test-fields">
                    <FormInput id="title" label="Judul Video" isRequired value={data.title} onChange={handleChange} error={errors.title}
                        placeholder="Contoh: Pengenalan Materi Bab 1" small="Judul harus singkat dan jelas, misalnya 'Pengenalan Materi Bab 1'." />
                    <FormTextArea id="description" label="Deskripsi Video" isRequired rows={3} value={data.description} onChange={handleChange} error={errors.description}
                        placeholder="Tuliskan gambaran singkat isi video atau poin pembelajaran penting." small="Deskripsi membantu peserta memahami tujuan pembelajaran dari video tersebut." />
                </div>
            </CrudFormSection>
            <CrudFormSection title="Akses dan penilaian" icon="bx-link" description="Masukkan tautan video, durasi, dan bobot aktivitas.">
                <div className="course-test-fields course-test-score-fields">
                    <FormInput id="value" label="Bobot Nilai" isRequired type="number" value={data.value} onChange={handleChange} error={errors.value} placeholder="Contoh: 10, 20, 100" small="Bobot nilai digunakan bila video ini memiliki penilaian." />
                    <FormInput id="duration" label="Durasi Video (Menit)" isRequired type="number" step="0.1" min={0.1} value={data.duration} onChange={handleChange} error={errors.duration} placeholder="Contoh: 5 atau 0,5" small="Masukkan durasi dalam menit. Contoh: 0,5 untuk 30 detik atau 5 untuk 5 menit." />
                    <div>
                        <FormInput id="url" label="URL Video (Embed)" isRequired type="url" value={data.url} onChange={handleChange} error={errors.url}
                            placeholder="https://www.youtube.com/embed/VIDEO_ID"
                            small="Hanya tautan embed yang didukung. Ambil dari YouTube → Bagikan → Sematkan, lalu salin URL pada kolom src. Contoh: https://www.youtube.com/embed/dQw4w9WgXcQ" />
                    </div>
                </div>
            </CrudFormSection>
            <CrudFormSection title="Instruksi tambahan" icon="bx-message-detail" description="Tambahkan catatan penting atau arahan saat peserta menonton video.">
                <div className={`course-test-editor ${errors.instruction ? 'is-invalid' : ''}`}>
                    <FormLabel title="Instruksi Tambahan" id="instruction" />
                    <ReactQuill value={data.instruction} onChange={(value) => setData('instruction', value)} placeholder="Tulis catatan penting atau arahan untuk peserta." />
                </div>
            </CrudFormSection>
        </form>
    </ActivityCrudFormLayout>;
}
