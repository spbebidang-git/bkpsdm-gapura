import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import '@/../css/admin-course-index.css';
import '@/../css/admin-activity-picker.css';

type Section = 'question' | 'interactive' | null;
type ChoiceProps = { title: string; description: string; icon: string; href?: string; onClick?: () => void };

function ActivityChoice({ title, description, icon, href, onClick }: ChoiceProps) {
    const content = <>
        <span className="activity-picker-choice-icon"><i className={`bx ${icon}`} aria-hidden /></span>
        <span className="activity-picker-choice-copy"><span className="activity-picker-choice-title">{title}</span><span className="activity-picker-choice-description">{description}</span></span>
        <i className="bx bx-chevron-right activity-picker-choice-arrow" aria-hidden />
    </>;
    return href ? <Link href={href} className="activity-picker-choice">{content}</Link>
        : <button type="button" onClick={onClick} className="activity-picker-choice">{content}</button>;
}

export default function Activity({ slug, type }: { slug: string; type: string }) {
    const [activeSection, setActiveSection] = useState<Section>(null);
    const nested = activeSection !== null;
    const sectionTitle = activeSection === 'question' ? 'Jenis evaluasi' : activeSection === 'interactive' ? 'Jenis interaktif' : 'Jenis aktivitas';
    const description = activeSection === 'question' ? 'Pilih evaluasi untuk mengukur pemahaman peserta.'
        : activeSection === 'interactive' ? 'Pilih metode interaktif untuk menyampaikan pembelajaran.'
            : 'Pilih salah satu jenis aktivitas untuk melanjutkan.';
    const createHref = (activityType: string) => `/admin/course/${slug}/activity/${activityType}/create`;

    return <AdminLayout title="Tambah aktivitas" hideTitle>
        <Head title="Tambah aktivitas" />
        <div className="course-index activity-picker-page">
            <CrudPageHeader title="Tambah aktivitas" description="Pilih aktivitas pembelajaran yang ingin ditambahkan ke kelas ini." className="course-index-header"
                actions={<Link href={route('admin.course.activities.index', slug)} className="activity-picker-back"><i className="bx bx-left-arrow-alt" aria-hidden />Kembali</Link>} />
            <CrudFormSection title={sectionTitle} description={description} icon={activeSection === 'question' ? 'bx-list-check' : activeSection === 'interactive' ? 'bx-video' : 'bx-category'}>
                <ol className="activity-picker-steps" aria-label="Tahapan pemilihan aktivitas">
                    <li className={nested ? 'is-complete' : 'is-current'} aria-current={!nested ? 'step' : undefined}><span className="activity-picker-step-number">{nested ? <i className="bx bx-check" aria-hidden /> : '1'}</span>Jenis aktivitas</li>
                    <li className={nested ? 'is-current' : ''} aria-current={nested ? 'step' : undefined}><span className="activity-picker-step-number">2</span>{nested ? activeSection === 'question' ? 'Evaluasi' : 'Interaktif' : 'Pilihan lanjutan'}</li>
                </ol>
                <div className="activity-picker-choices" key={activeSection || 'all'}>
                    {!activeSection && <>
                        <ActivityChoice title="Pertanyaan" description="Buat pre-test, kuis, atau post-test untuk mengukur pemahaman peserta." icon="bx-list-check" onClick={() => setActiveSection('question')} />
                        <ActivityChoice title="Tugas" description="Tetapkan instruksi dan bobot tugas untuk peserta." icon="bx-task" href={createHref('task')} />
                        <ActivityChoice title="Interaktif" description="Tambahkan pertemuan daring atau presentasi video." icon="bx-video" onClick={() => setActiveSection('interactive')} />
                        <ActivityChoice title="Literatur" description="Unggah materi PDF yang dapat dibaca peserta." icon="bx-book-open" href={createHref('literature')} />
                    </>}
                    {activeSection === 'question' && <>
                        <ActivityChoice title="Pre-Test / Kuis" description="Ukur pemahaman awal peserta sebelum materi dimulai." icon="bx-list-check" href={createHref('pre-test')} />
                        <ActivityChoice title="Post-Test" description="Nilai pemahaman akhir dan tetapkan batas kelulusan." icon="bx-check-square" href={createHref('post-test')} />
                    </>}
                    {activeSection === 'interactive' && <>
                        {type !== 'unschedule' && <ActivityChoice title="Pertemuan daring" description="Atur jadwal dan tautan pertemuan daring." icon="bx-calendar" href={createHref('meeting')} />}
                        <ActivityChoice title="Presentasi video" description="Unggah video dan atur durasi belajar peserta." icon="bx-play-circle" href={createHref('video')} />
                    </>}
                </div>
                {nested && <div className="activity-picker-footer"><button type="button" className="activity-picker-back" onClick={() => setActiveSection(null)}><i className="bx bx-left-arrow-alt" aria-hidden />Kembali ke jenis aktivitas</button></div>}
            </CrudFormSection>
        </div>
    </AdminLayout>;
}
