import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import CourseFormFields from '@/Components/Admin/Course/CourseFormFields';
import Button from '@/Components/Button';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Edit({
    course,
    categories = [],
    employee_status: employeeStatuses = [],
    selected_employee_status: selectedEmployeeStatuses = [],
    trainers = [],
    selected_trainers: selectedTrainers = [],
    opds = [],
    is_trainer: isTrainer = false,
}) {
    const { errors = {} } = usePage().props;
    const isTrainerAccount = Boolean(isTrainer);
    const currentType = course.type || (course.access_mode === 'scheduled' ? 'schedule' : 'unschedule');
    const hasSchedules = (course.schedules || []).length > 0;
    const currentThumbnail = course.thumbnail_url || course.thumbnail_src || (
        course.thumbnail === 'default-class.jpg'
            ? '/assets/media/images/static/default-class.jpg'
            : course.thumbnail
                ? `/assets/media/images/courses/${course.thumbnail}`
                : null
    );
    const [thumbnailPreview, setThumbnailPreview] = useState(currentThumbnail);
    const [processing, setProcessing] = useState(false);
    const [values, setValues] = useState({
        type: currentType,
        category: course.category_id || '',
        title: course.title || '',
        short_description: course.short_description || '',
        description: course.description || '',
        thumbnail: null,
        teaser_video: course.teaser_video || '',
        expired: course.expired ?? course.default_access_hours ?? '',
        jam_pelajaran: course.jam_pelajaran || '',
        status: course.status || '',
        employee_status: selectedEmployeeStatuses,
        trainers: selectedTrainers,
        visibility: course.visibility || 'private',
        opd_ids: (course.opds || []).map((opd) => String(opd.id)),
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
            ...(id === 'visibility' && value === 'public' ? { opd_ids: [] } : {}),
            [id]: inputType === 'file' ? files?.[0] ?? null : value,
        }));
    };

    useEffect(() => {
        if (!values.thumbnail) {
            setThumbnailPreview(currentThumbnail);
            return undefined;
        }

        const previewUrl = URL.createObjectURL(values.thumbnail);
        setThumbnailPreview(previewUrl);
        return () => URL.revokeObjectURL(previewUrl);
    }, [currentThumbnail, values.thumbnail]);

    const submit = (event) => {
        event.preventDefault();
        if (processing) return;
        const formData = new FormData();
        formData.append('_method', 'PUT');
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
        formData.append('status', values.status);

        router.post(`/admin/course/${course.slug}`, formData, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AdminLayout title="Edit Kelas" hideTitle>
            <Head title={`Edit ${course.title}`} />
            <div className="course-form">
                <CrudPageHeader title="Edit kelas" description="Perbarui informasi dan pengaturan kelas sesuai kebutuhan." actions={<>
                    <Button variant="secondary" href={route('admin.course.index')} className="min-w-28"><i className="bx bx-arrow-back text-base" aria-hidden />Kembali</Button>
                    <Button type="submit" form="edit-course-form" loading={processing} disabled={processing} className="min-w-28">{processing ? 'Menyimpan…' : 'Simpan'}</Button>
                </>} />
                <form noValidate id="edit-course-form" onSubmit={submit} aria-busy={processing} className="admin-crud-form">
                    <CourseFormFields
                        values={values} setValues={setValues} handleChange={handleChange} errors={errors}
                        categories={categories} employeeStatuses={employeeStatuses}
                        trainers={trainers} opds={[...opds, ...(course.opds || []).filter((selected) => !opds.some((opd) => String(opd.id) === String(selected.id))).map((opd) => ({ ...opd, is_active: false }))]} isTrainerAccount={isTrainerAccount} thumbnailPreview={thumbnailPreview}
                        editing typeLocked={Boolean(course.type || course.access_mode)} hasSchedules={hasSchedules}

                    />
                </form>
            </div>


        </AdminLayout>
    );
}
