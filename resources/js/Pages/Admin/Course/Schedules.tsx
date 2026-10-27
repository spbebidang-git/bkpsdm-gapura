import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import { tableClass } from '@/Components/Admin/ui';
import ErrorMessage from '@/Components/ErrorMessage';
import FormInput from '@/Components/FormInput';
import Modal from '@/Components/Modal';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import '@/../css/admin-course-index.css';
import '@/../css/admin-course-schedules.css';

const blank = { date: '', time_from: '', time_to: '', quota: '' };

export default function Schedules({ course, schedules = [] }) {
    const { errors = {} } = usePage().props;
    const [dialog, setDialog] = useState(false);
    const [editing, setEditing] = useState(null);
    const [values, setValues] = useState(blank);
    const [processing, setProcessing] = useState(false);
    useEffect(() => { if (errors.date || errors.time_from || errors.time_to || errors.quota) setDialog(true); }, [errors]);
    const open = (schedule = null) => {
        setEditing(schedule);
        setValues(schedule ? { date: schedule.date || '', time_from: schedule.time_from || '', time_to: schedule.time_to || '', quota: String(schedule.quota ?? '') } : blank);
        setDialog(true);
    };
    const close = () => { setDialog(false); setEditing(null); setValues(blank); };
    const change = (event) => setValues((current) => ({ ...current, [event.target.id]: event.target.value }));
    const submit = (event) => {
        event.preventDefault(); if (processing) return;
        const url = editing ? route('admin.course.schedules.update', [course.slug, editing.id]) : route('admin.course.schedules.store', course.slug);
        router.visit(url, { method: editing ? 'put' : 'post', data: values, preserveScroll: true, onStart: () => setProcessing(true), onFinish: () => setProcessing(false), onSuccess: close });
    };

    return <AdminLayout title="Kelola jadwal" hideTitle><Head title={`Jadwal · ${course.title}`} />
        <div className="course-index course-schedules-page">
            <CrudPageHeader title={course.title} description="Kelola jadwal kelas dan kuota pengguna." className="course-index-header" actions={<>
                <Link href={route('admin.course.index')} className="course-schedules-secondary"><i className="bx bx-left-arrow-alt" aria-hidden />Kembali</Link>
                <button className="course-index-create" type="button" onClick={() => open()}><i className="bx bx-plus" aria-hidden />Tambah jadwal</button>
            </>} />
            {course.status === 'draft' && schedules.length > 0 && <div className="course-schedules-notice"><i className="bx bx-info-circle" aria-hidden /><span>Jadwal sudah tersedia. Kelas ini masih berupa draft.</span><Link href={route('admin.course.edit', course.slug)}>Edit kelas untuk memublikasikan</Link></div>}
            <section className="admin-table-frame course-index-table admin-crud-table course-schedules-table" aria-label="Jadwal kelas">
                <div className="admin-table-toolbar"><span className="course-schedules-table-title">Jadwal kelas</span><div className="admin-table-count"><span aria-hidden /><span>{schedules.length.toLocaleString('id-ID')} jadwal</span></div></div>
                <div className="admin-table-scroll course-schedules-table-wrap"><table className={tableClass}>
                    <thead><tr><th>No.</th><th>Tanggal</th><th>Waktu</th><th>Kuota</th><th>Pengguna</th><th className="text-right">Aksi</th></tr></thead>
                    <tbody>{schedules.length ? schedules.map((schedule, index) => <tr key={schedule.id}>
                        <td>{index + 1}</td><td className="font-medium">{schedule.date}</td><td>{schedule.time_from}–{schedule.time_to}</td><td>{schedule.quota}</td><td>{schedule.participant_count}</td>
                        <td><div className="course-schedules-row-actions">
                            <button type="button" onClick={() => open(schedule)} className="course-index-action is-edit" aria-label={`Edit jadwal ${schedule.date}`} title="Edit jadwal"><i className="bx bx-edit-alt" aria-hidden /><span className="course-index-action-label">Edit</span></button>
                            <button type="button" disabled={!schedule.can_delete} onClick={() => confirmDelete(route('admin.course.schedules.destroy', [course.slug, schedule.id]), { message: course.status === 'publish' && schedules.length === 1 ? 'Jadwal terakhir kelas publikasi tidak dapat dihapus. Ubah kelas menjadi draft terlebih dahulu.' : 'Jadwal ini akan dihapus.' })} className="course-index-action is-delete" aria-label={`Hapus jadwal ${schedule.date}`} title={schedule.can_delete ? 'Hapus jadwal' : schedule.participant_count > 0 ? 'Jadwal memiliki pengguna terdaftar' : 'Jadwal terakhir kelas publikasi'}><i className="bx bx-trash" aria-hidden /><span className="course-index-action-label">Hapus</span></button>
                        </div></td>
                    </tr>) : null}</tbody>
                </table></div>
                {!schedules.length && <div className="course-schedules-empty"><span className="course-schedules-empty-icon"><i className="bx bx-calendar-plus" aria-hidden /></span><strong>Belum ada jadwal</strong><span>Tambahkan jadwal agar pengguna dapat mengikuti kelas terjadwal.</span><button type="button" className="course-index-create" onClick={() => open()}>Tambah jadwal</button></div>}
            </section>
        </div>
        <Modal show={dialog} onClose={close} maxWidth="lg"><div className="course-schedules-modal">
            <header><span className="course-schedules-modal-icon"><i className="bx bx-calendar-plus" aria-hidden /></span><div><h2>{editing ? 'Edit jadwal' : 'Tambah jadwal'}</h2><p>{editing?.participant_count ? 'Tanggal dan waktu terkunci karena pengguna sudah terdaftar. Kuota dapat dinaikkan.' : 'Tentukan tanggal, waktu, dan kuota pengguna.'}</p></div><button type="button" onClick={close} aria-label="Tutup dialog"><i className="bx bx-x" aria-hidden /></button></header>
            <form noValidate onSubmit={submit}><div className="course-schedules-fields">
                <FormInput id="date" label="Tanggal kegiatan" type="date" required isRequired disabled={Boolean(editing?.participant_count)} value={values.date} onChange={change} error={errors.date} />
                <div className="grid grid-cols-2 gap-4"><FormInput id="time_from" label="Waktu mulai" type="time" required isRequired disabled={Boolean(editing?.participant_count)} value={values.time_from} onChange={change} error={errors.time_from} /><FormInput id="time_to" label="Waktu selesai" type="time" required isRequired disabled={Boolean(editing?.participant_count)} value={values.time_to} onChange={change} error={errors.time_to} /></div>
                <FormInput id="quota" label="Kuota pengguna" type="number" min={Math.max(1, editing?.participant_count || 1)} step="1" required isRequired value={values.quota} onChange={change} error={errors.quota} placeholder="Contoh: 30" />
                <ErrorMessage message={errors.schedule} />
            </div><footer><button type="button" className="course-schedules-secondary" onClick={close}>Batal</button><button type="submit" disabled={processing} className="course-schedules-primary"><i className={`bx ${processing ? 'bx-loader-alt bx-spin' : 'bx-save'}`} aria-hidden />{processing ? 'Menyimpan...' : 'Simpan jadwal'}</button></footer></form>
        </div></Modal>
    </AdminLayout>;
}
