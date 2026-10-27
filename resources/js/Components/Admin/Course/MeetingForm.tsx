import ActivityCrudFormLayout from '@/Components/Admin/Course/ActivityCrudFormLayout';
import FormInput from '@/Components/FormInput';
import FormLabel from '@/Components/FormLabel';
import FormTextArea from '@/Components/FormTextArea';
import useTransientFieldError from '@/Components/useTransientFieldError';
import { CrudFormSection } from '@/Components/Admin/CrudPrimitives';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';

type MeetingActivity = {
    id: number | string; title: string; description: string; instruction: string;
    value: string | number; url: string; duration: string | number;
} | null;

export default function MeetingForm({ slug, activity = null }: { slug: string; activity?: MeetingActivity }) {
    const isEdit = Boolean(activity);
    const formId = isEdit ? 'edit-meeting-form' : 'create-meeting-form';
    const { data, setData, post, put, processing, errors } = useForm({
        title: activity?.title ?? '',
        description: activity?.description ?? '',
        value: activity?.value ?? '',
        instruction: activity?.instruction ?? '',
        url: activity?.url ?? '',
        duration: activity?.duration ? Number(activity.duration) / 60 : '',
    });
    const { containerRef: instructionRef, visibleError: instructionError, dismissError: dismissInstructionError } = useTransientFieldError(errors.instruction);
    const handleChange = (event: any) => {
        const { id, type, value, checked } = event.target;
        setData(id, type === 'checkbox' ? checked : value);
    };
    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (isEdit && activity) put(`/admin/course/${slug}/activity/meeting/${activity.id}`);
        else post(`/admin/course/${slug}/activity/meeting`);
    };

    return <ActivityCrudFormLayout slug={slug} formId={formId} processing={processing}
        title={isEdit ? 'Ubah Pertemuan Daring' : 'Pertemuan Daring'}
        description="Buat sesi pertemuan online beserta URL yang digunakan pengguna untuk bergabung.">
        <form id={formId} noValidate onSubmit={handleSubmit} className="course-test-form w-full">
            <CrudFormSection title="Informasi pertemuan" icon="bx-video" description="Jelaskan tujuan dan isi sesi yang akan diikuti pengguna.">
                <div className="course-test-fields">
                    <FormInput id="title" label="Judul Pertemuan" isRequired value={data.title} onChange={handleChange} error={errors.title}
                        small="Minimal 3 karakter. Isikan judul pertemuan yang jelas dan mudah dipahami." />
                    <FormTextArea id="description" label="Deskripsi Pertemuan" isRequired rows={3} value={data.description} onChange={handleChange} error={errors.description}
                        small="Berikan penjelasan singkat mengenai tujuan dan isi pertemuan daring." />
                </div>
            </CrudFormSection>
            <CrudFormSection title="Akses dan penilaian" icon="bx-link" description="Atur tautan bergabung, durasi, dan bobot aktivitas.">
                <div className="course-test-fields course-test-score-fields">
                    <FormInput id="value" label="Bobot Nilai" isRequired type="number" value={data.value} onChange={handleChange} error={errors.value} small="Isikan angka antara 1–100." />
                    <FormInput id="duration" label="Durasi Pertemuan (Menit)" isRequired type="number" step="0.1" min={0.1} value={data.duration} onChange={handleChange} error={errors.duration} placeholder="Contoh: 30 atau 0,5" small="Masukkan durasi dalam menit. Contoh: 0,5 untuk 30 detik atau 30 untuk 30 menit." />
                    <FormInput id="url" label="Tautan Meeting (URL)" isRequired type="url" value={data.url} onChange={handleChange} error={errors.url} placeholder="https://meet.google.com/xxx-xxxx-xxx" small="Masukkan URL meeting yang valid (Zoom, Google Meet, atau platform lainnya)." />
                </div>
            </CrudFormSection>
            <CrudFormSection title="Instruksi untuk pengguna" icon="bx-message-detail" description="Tambahkan panduan lengkap yang harus diikuti sebelum pertemuan berlangsung.">
                <div ref={instructionRef} className={`course-test-editor ${instructionError ? 'is-invalid' : ''}`}>
                    <FormLabel title="Instruksi" id="instruction" />
                    <ReactQuill value={data.instruction} onChange={(value) => { dismissInstructionError(); setData('instruction', value); }} placeholder="Tuliskan instruksi atau panduan yang harus diikuti pengguna." />
                    {instructionError && <small className="mt-1 block text-red-500">{instructionError}</small>}
                </div>
            </CrudFormSection>
        </form>
    </ActivityCrudFormLayout>;
}
