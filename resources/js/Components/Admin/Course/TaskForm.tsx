import ActivityCrudFormLayout from '@/Components/Admin/Course/ActivityCrudFormLayout';
import { CrudFormSection } from '@/Components/Admin/CrudPrimitives';
import FormInput from '@/Components/FormInput';
import FormLabel from '@/Components/FormLabel';
import FormSelect from '@/Components/FormSelect';
import FormTextArea from '@/Components/FormTextArea';
import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';

type TaskActivity = {
    id: number | string; title: string; description: string; instruction: string;
    value: string | number; is_calculated: boolean | number | string;
} | null;

const typeOptions = [
    { value: '0', label: 'Tidak' },
    { value: '1', label: 'Ya' },
];

export default function TaskForm({ slug, activity = null }: { slug: string; activity?: TaskActivity }) {
    const isEdit = Boolean(activity);
    const formId = isEdit ? 'edit-task-form' : 'create-task-form';
    const { data, setData, post, put, processing, errors } = useForm({
        title: activity?.title ?? '',
        description: activity?.description ?? '',
        instruction: activity?.instruction ?? '',
        value: activity?.value ?? '',
        is_calculated: activity?.is_calculated ? '1' : '0',
    });
    const handleChange = (event: any) => {
        const { id, type, value, checked } = event.target;
        setData(id, type === 'checkbox' ? checked : value);
    };
    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (isEdit && activity) put(`/admin/course/${slug}/activity/task/${activity.id}`);
        else post(`/admin/course/${slug}/activity/task`);
    };

    return <ActivityCrudFormLayout slug={slug} formId={formId} processing={processing}
        title={isEdit ? 'Ubah Tugas' : 'Pengaturan Tugas'}
        description="Atur penugasan yang hasilnya akan dikirim ke email instruktur.">
        <form id={formId} noValidate onSubmit={handleSubmit} className="course-test-form w-full">
            <CrudFormSection title="Informasi tugas" icon="bx-task" description="Gunakan judul yang memudahkan peserta mengenali penugasan.">
                <div className="course-test-fields">
                    <FormInput id="title" label="Judul Tugas" isRequired value={data.title} onChange={handleChange} error={errors.title}
                        placeholder="Contoh: Tugas Bab 1" small="Judul harus singkat dan jelas. Contoh: 'Tugas Bab 1' atau 'Analisis Studi Kasus'." />
                </div>
            </CrudFormSection>
            <CrudFormSection title="Pengaturan penilaian" icon="bx-slider-alt" description="Tentukan bobot tugas dan cara penilaiannya.">
                <div className="course-test-fields course-test-score-fields">
                    <FormInput id="value" label="Bobot Nilai" isRequired type="number" value={data.value} onChange={handleChange} error={errors.value}
                        placeholder="Contoh: 10, 20, 100" small="Masukkan bobot nilai untuk tugas ini." />
                    <FormSelect id="is_calculated" label="Nilai Otomatis?" value={data.is_calculated} onChange={handleChange} error={errors.is_calculated}
                        emptyOption={null} options={typeOptions} small="Pilih 'Ya' jika tugas dinilai otomatis oleh sistem, 'Tidak' jika dinilai instruktur." />
                </div>
            </CrudFormSection>
            <CrudFormSection title="Instruksi tugas" icon="bx-message-detail" description="Berikan aturan penulisan, format pengumpulan, atau panduan teknis.">
                <div className={`course-test-editor ${errors.instruction ? 'is-invalid' : ''}`}>
                    <FormLabel title="Instruksi Tugas" id="instruction" />
                    <ReactQuill value={data.instruction} onChange={(value) => setData('instruction', value)} placeholder="Tuliskan aturan atau panduan pengumpulan tugas." />
                </div>
            </CrudFormSection>
            <CrudFormSection title="Soal atau deskripsi tugas" icon="bx-detail" description="Jelaskan keluaran yang harus dikumpulkan dan standar pengerjaannya.">
                <div className="course-test-fields">
                    <FormTextArea id="description" label="Deskripsi Tugas" isRequired rows={4} value={data.description} onChange={handleChange} error={errors.description}
                        placeholder="Masukkan soal, penjelasan tugas, atau panduan yang harus dikerjakan peserta." />
                </div>
            </CrudFormSection>
        </form>
    </ActivityCrudFormLayout>;
}
