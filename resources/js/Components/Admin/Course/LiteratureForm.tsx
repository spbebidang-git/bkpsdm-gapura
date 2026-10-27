import ActivityCrudFormLayout from '@/Components/Admin/Course/ActivityCrudFormLayout';
import LiteratureFileField from '@/Components/Admin/Course/LiteratureFileField';
import { CrudFormSection } from '@/Components/Admin/CrudPrimitives';
import FormInput from '@/Components/FormInput';
import FormLabel from '@/Components/FormLabel';
import useTransientFieldError from '@/Components/useTransientFieldError';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';

type LiteratureActivity = {
    id?: number | string; title: string; sub_title: string; instruction: string;
    value: string | number; duration: string | number;
    content: string | null; content_url: string | null;
} | null;

export default function LiteratureForm({ slug, activity = null }: { slug: string; activity?: LiteratureActivity }) {
    const isEdit = Boolean(activity?.id);
    const formId = isEdit ? 'edit-literature-form' : 'create-literature-form';
    const { data, setData, post, put, processing, errors } = useForm<{
        title: string; sub_title: string; value: string | number; duration: string | number;
        instruction: string; content: File | null;
    }>({
        title: activity?.title ?? '',
        sub_title: activity?.sub_title ?? '',
        value: activity?.value ?? '',
        duration: activity?.duration ? Number(activity.duration) / 60 : '',
        instruction: activity?.instruction ?? '',
        content: null,
    });
    const { containerRef: instructionRef, visibleError: instructionError, dismissError: dismissInstructionError } = useTransientFieldError(errors.instruction);
    const { visibleError: contentError, dismissError: dismissContentError } = useTransientFieldError(errors.content);
    const handleChange = (event: any) => {
        const { id, type, value, checked } = event.target;
        setData(id, type === 'checkbox' ? checked : value);
    };
    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (isEdit && activity?.id) put(`/admin/course/${slug}/activity/literature/${activity.id}`, { forceFormData: true });
        else post(`/admin/course/${slug}/activity/literature`, { forceFormData: true });
    };

    return <ActivityCrudFormLayout slug={slug} formId={formId} processing={processing}
        title={isEdit ? 'Ubah Literatur' : 'Literatur'}
        description="Upload file PDF literatur yang dapat diakses dan dibaca oleh pengguna.">
        <form id={formId} noValidate onSubmit={handleSubmit} encType="multipart/form-data" className="course-test-form w-full">
            <CrudFormSection title="Informasi literatur" icon="bx-book-open" description="Tambahkan judul dan subjudul agar materi mudah dikenali.">
                <div className="course-test-fields course-test-score-fields">
                    <FormInput id="title" label="Judul" isRequired value={data.title} onChange={handleChange} error={errors.title}
                        placeholder="Masukkan judul materi" small="Judul harus singkat dan jelas, misalnya: 'Modul Dasar Manajemen Risiko'." />
                    <FormInput id="sub_title" label="Sub Judul" isRequired value={data.sub_title} onChange={handleChange} error={errors.sub_title}
                        placeholder="Masukkan sub judul materi" small="Sub judul menjelaskan detail tambahan dari materi." />
                </div>
            </CrudFormSection>
            <CrudFormSection title="Pengaturan belajar" icon="bx-slider-alt" description="Tentukan bobot nilai dan estimasi durasi membaca.">
                <div className="course-test-fields course-test-score-fields">
                    <FormInput id="value" label="Bobot Nilai" isRequired type="number" value={data.value} onChange={handleChange} error={errors.value}
                        placeholder="Contoh: 10, 20, 100" small="Bobot nilai digunakan bila literatur ini menjadi bagian penilaian." />
                    <FormInput id="duration" label="Durasi Membaca (Menit)" isRequired type="number" step="0.1" min={0.1} value={data.duration} onChange={handleChange} error={errors.duration}
                        placeholder="Contoh: 5 atau 0,5" small="Masukkan durasi dalam menit. Contoh: 0,5 untuk 30 detik atau 5 untuk 5 menit." />
                </div>
            </CrudFormSection>
            <CrudFormSection title="Instruksi tambahan" icon="bx-message-detail" description="Berikan catatan penting atau arahan sebelum pengguna membaca materi.">
                <div ref={instructionRef} className={`course-test-editor ${instructionError ? 'is-invalid' : ''}`}>
                    <FormLabel title="Instruksi Tambahan" id="instruction" />
                    <ReactQuill value={data.instruction} onChange={(value) => { dismissInstructionError(); setData('instruction', value); }} placeholder="Tulis catatan penting atau arahan untuk pengguna." />
                    {instructionError && <small className="mt-1 block text-red-500">{instructionError}</small>}
                </div>
            </CrudFormSection>
            <CrudFormSection title="Berkas literatur" icon="bx-cloud-upload" description="Unggah materi PDF yang akan dapat diakses oleh pengguna.">
                <LiteratureFileField
                    file={data.content}
                    error={contentError}
                    existingUrl={activity?.content_url}
                    existingName={activity?.content}
                    isRequired={!isEdit}
                    disabled={processing}
                    onChange={(file) => setData('content', file)}
                    onDismissError={dismissContentError}
                />
            </CrudFormSection>
        </form>
    </ActivityCrudFormLayout>;
}
