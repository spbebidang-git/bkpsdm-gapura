import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import CourseFormFields from '@/Components/Admin/Course/CourseFormFields';
import Button from '@/Components/Button';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const Create = ({ categories = [], employee_status = [], trainers = [], opds = [], current_trainer: currentTrainer = null, is_trainer: isTrainer = false }) => {
    const { errors = {} } = usePage().props;
    const isTrainerAccount = Boolean(isTrainer);
    const [thumbnailPreview, setThumbnailPreview] = useState(null);
    const [processing, setProcessing] = useState(false);
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
        status: 'draft',
        employee_status: [],
        trainers: [],
        visibility: 'private',
        opd_ids: [] as string[],
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
        setValues((v) => ({
            ...v,
            ...(id === 'type' && value === 'schedule' ? { status: 'draft' } : {}),
            ...(id === 'visibility' && value === 'public' ? { opd_ids: [] } : {}),
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
        if (processing) return;
        const formData = new FormData();
        formData.append('type', values.type);
        formData.append('category', values.category);
        values.employee_status.forEach((id) => formData.append('employee_status[]', id));
        values.trainers.forEach((id) => formData.append('trainers[]', id));
        formData.append('visibility', values.visibility);
        if (values.visibility === 'private') values.opd_ids.forEach((id) => formData.append('opd_ids[]', id));
        formData.append('title', values.title);
        formData.append('short_description', values.short_description);
        formData.append('description', values.description);
        if (values.thumbnail) formData.append('thumbnail', values.thumbnail);
        formData.append('teaser_video', values.teaser_video);
        formData.append('expired', values.expired);
        formData.append('jam_pelajaran', values.jam_pelajaran);
        formData.append('status', values.type === 'schedule' ? 'draft' : values.status);

        router.post('/admin/course', formData, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AdminLayout title="Buat Kelas" hideTitle>
            <Head title="Buat Kelas" />

            <div className="course-form">
                <CrudPageHeader title="Buat kelas" description="Susun informasi, media, dan pengaturan untuk kelas baru." actions={<>
                    <Button variant="secondary" href={route('admin.course.index')} className="min-w-28"><i className="bx bx-arrow-back text-base" aria-hidden />Kembali</Button>
                    <Button type="submit" form="create-course-form" loading={processing} disabled={processing} className="min-w-28">{processing ? 'Menyimpan…' : 'Simpan'}</Button>
                </>} />
                <form noValidate id="create-course-form" onSubmit={handleSubmit} aria-busy={processing} className="admin-crud-form">
                    <CourseFormFields
                        values={values} setValues={setValues} handleChange={handleChange} errors={errors}
                        categories={categories} employeeStatuses={employee_status}
                        trainers={trainers} opds={opds} isTrainerAccount={isTrainerAccount} currentTrainer={currentTrainer} thumbnailPreview={thumbnailPreview}

                    />
                </form>
            </div>


        </AdminLayout>
    );
};

export default Create;
