import App from '@/Layouts/Participant/App';
import { Head, Link, router } from '@inertiajs/react';
import { getBrowserSupportStatus, getUnsupportedBrowserMessage } from '@/Utils/browserSupport';
import { showCourseActivationToast } from '@/Components/CourseActivationToast';
import { useState } from 'react';
import DOMPurify from 'dompurify';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import '../../../../css/participant-course-detail.css';

type CourseDetail = {
    title: string;
    slug: string;
    type: string;
    category: string;
    employee_status: string;
    short_description: string | null;
    description: string | null;
    thumbnail: string | null;
    quota: string | number | null;
    total_participant: number;
    opd_name: string | null;
    visibility: 'public' | 'private';
    access_hours: number | null;
    schedule: { date: string | null; time: string | null; quota: string | number | null } | null;
    trainers: { id: string; name: string; position: string | null; photo: string }[];
    activities: { id: string; title: string; type: string }[];
    rating: { star: number; total: number };
    reviews: { id: string; name: string; star: number; description: string | null; date: string | null }[];
};

type LearningSummary = {
    activation_date: string | null;
    finished_at: string | null;
    post_test_avg: number | string | null;
    jam_pelajaran: number | string | null;
    url_certificate: string | null;
    entry_url: string | null;
    entry_message: string | null;
    can_reset: boolean;
    needs_activation: boolean;
};

function formatDate(value: string | null) {
    if (!value) return null;
    const date = new Date(value.includes('T') ? value : value.includes(' ') ? value.replace(' ', 'T') : `${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

function Icon({ name, size = '22px' }: { name: string; size?: string }) {
    return <box-icon name={name} color="currentColor" size={size} aria-hidden="true" />;
}

export default function Show({ course, learn }: { course: CourseDetail; learn: LearningSummary }) {
    const [tab, setTab] = useState('information');
    const [resetOpen, setResetOpen] = useState(false);
    const [resetting, setResetting] = useState(false);
    const tabs = [{ id: 'information', label: 'Informasi Program', icon: 'info-circle' }, { id: 'guide', label: 'Panduan Pelatihan', icon: 'book-reader' }, { id: 'certificate', label: 'Sertifikat', icon: 'award' }, { id: 'reviews', label: 'Ulasan', icon: 'star' }];
    const completed = Boolean(learn.finished_at);
    const scheduled = course.type === 'schedule' || course.type === 'scheduled';
    const score = Number(learn.post_test_avg ?? 0);
    const certificateAvailable = Boolean(learn.url_certificate);
    const startLearning = () => {
        if (!learn.entry_url) return;
        const support = getBrowserSupportStatus();
        if (!support.supported) {
            showCourseActivationToast({ tone: 'error', title: 'Browser belum mendukung', message: getUnsupportedBrowserMessage(support.reason) }, 'unsupported-browser');
            return;
        }
        router.visit(learn.entry_url);
    };
    const resetLearning = () => {
        setResetting(true);
        router.post(route('participant.course.reset', { course: course.slug }), {}, {
            onSuccess: () => {
                course.activities.forEach(({ id }) => {
                    localStorage.removeItem(`countdownStartTime_${id}`);
                    Object.keys(localStorage).filter((key) => key.startsWith('materialCountdown_') && key.endsWith(`_${id}`))
                        .forEach((key) => localStorage.removeItem(key));
                });
                setResetOpen(false);
            },
            onFinish: () => setResetting(false),
        });
    };
    const stats = [
        { icon: 'calendar-check', label: 'Tanggal aktivasi', value: formatDate(learn.activation_date) || 'Belum tercatat', note: 'Awal keikutsertaan Anda' },
        { icon: 'flag', label: 'Tanggal selesai', value: formatDate(learn.finished_at) || 'Belum selesai', note: completed ? 'Pembelajaran telah diselesaikan' : 'Selesaikan rangkaian pembelajaran' },
        { icon: 'bar-chart-alt-2', label: 'Rata-rata post-test', value: Number.isFinite(score) ? score.toLocaleString('id-ID', { maximumFractionDigits: 2 }) : '—', note: 'Nilai yang tercatat saat ini' },
        { icon: 'time-five', label: 'Jam pelajaran', value: `${learn.jam_pelajaran ?? 0} JP`, note: 'Alokasi pembelajaran kelas' },
    ];

    return <App>
        <Head title={`${course.title} · Kelas Saya`} />
        <div className="my-course-detail">
            <nav className="my-course-breadcrumb" aria-label="Breadcrumb">
                <Link href={route('participant.course.index')}><Icon name="left-arrow-alt" size="18px" />Kelas Saya</Link>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{course.title}</span>
            </nav>

            <section className="my-course-hero" aria-labelledby="my-course-title">
                <div className="my-course-hero__content">
                    <div className="my-course-hero__tags">
                        <span className="my-course-category">{course.category || 'Pelatihan ASN'}</span><span className="my-course-category">{scheduled ? 'Terjadwal' : 'Belajar mandiri'}</span>
                        <span className={`my-course-status ${completed ? 'is-completed' : ''}`}><span aria-hidden="true" />{completed ? 'Selesai' : learn.needs_activation ? 'Perlu aktivasi' : 'Berjalan'}</span>
                    </div>
                    <h1 id="my-course-title">{course.title}</h1>
                    <p className="my-course-hero__description">{course.short_description || 'Kembangkan kompetensi Anda melalui rangkaian pembelajaran kelas ini.'}</p>
                    <div className="my-course-hero__meta">
                        <span><Icon name="group" size="18px" />{course.employee_status || 'Semua pegawai'}</span>
                        <span><Icon name="time-five" size="18px" />{scheduled ? 'Sesuai jadwal' : 'Waktu belajar fleksibel'}</span>
                    </div>
                    <div className="my-course-hero__actions">
                        <button type="button" className="my-course-start" onClick={startLearning} disabled={!learn.entry_url} aria-describedby={learn.entry_message ? 'learning-entry-message' : undefined}><Icon name={completed ? 'book-open' : 'play-circle'} size="20px" />{completed ? 'Lihat materi pelatihan' : 'Mulai pelatihan'}<Icon name="right-arrow-alt" size="18px" /></button>
                        {learn.can_reset && <button type="button" className="my-course-reset" onClick={() => setResetOpen(true)}><Icon name="reset" size="18px" />Reset proses belajar</button>}
                        <a href={route('course.show', { course: course.slug })} target="_blank" rel="noopener noreferrer" className="my-course-public-link">{learn.needs_activation ? 'Aktifkan ulang kelas' : 'Lihat informasi kelas'}<Icon name="link-external" size="17px" /><span className="sr-only"> (buka tab baru)</span></a>
                    </div>
                    {learn.entry_message && <p id="learning-entry-message" className="my-course-entry-message">{learn.entry_message}</p>}
                </div>
                <div className="my-course-hero__image">
                    <img src={course.thumbnail || '/assets/media/images/static/default-class.jpg'} alt={course.title} onError={event => {
                        event.currentTarget.onerror = null;
                        event.currentTarget.src = '/assets/media/images/static/default-class.jpg';
                    }} />
                    <span className="my-course-hero__image-label"><Icon name={scheduled ? 'calendar' : 'book-open'} size="17px" />{scheduled ? 'Kelas terjadwal' : 'Belajar mandiri'}</span>
                </div>
            </section>

            <div className="my-course-columns">
                <nav className="my-course-menu" aria-label="Detail kelas" role="tablist" aria-orientation="vertical">
                    {tabs.map(item => <button key={item.id} type="button" id={`tab-${item.id}`} role="tab" aria-selected={tab === item.id} aria-controls={`panel-${item.id}`} tabIndex={tab === item.id ? 0 : -1} onClick={() => setTab(item.id)} onKeyDown={event => {
                        const direction = ['ArrowDown', 'ArrowRight'].includes(event.key) ? 1 : ['ArrowUp', 'ArrowLeft'].includes(event.key) ? -1 : 0;
                        if (!direction && event.key !== 'Home' && event.key !== 'End') return;
                        event.preventDefault();
                        const index = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (tabs.findIndex(t => t.id === tab) + direction + tabs.length) % tabs.length;
                        setTab(tabs[index].id); document.getElementById(`tab-${tabs[index].id}`)?.focus();
                    }} className={tab === item.id ? 'is-active' : ''}><Icon name={item.icon} size="18px" /><span>{item.label}</span><Icon name="chevron-right" size="17px" /></button>)}
                </nav>
                <div className="my-course-main" role="tabpanel" id={`panel-${tab}`} aria-labelledby={`tab-${tab}`} tabIndex={0}>
                    {tab === 'information' && <>
                        <section className="my-course-about">
                            <h2>Tentang program</h2>
                            <div className="my-course-about__body prose prose-sm max-w-none prose-headings:text-[#285C59] prose-a:text-[#287E78]" dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(course.description || '<p>Deskripsi kelas belum tersedia.</p>') }} />
                            <div className="my-course-program-meta">
                                <div><span>Pengajar</span>{course.trainers.length ? course.trainers.map(trainer => <div key={trainer.id} className="my-course-trainer"><img src={trainer.photo} alt="" onError={event => { event.currentTarget.onerror = null; event.currentTarget.src = '/assets/media/images/static/default-profile.jpg'; }} /><div><strong>{trainer.name}</strong><p>{trainer.position || 'Pengajar pelatihan'}</p></div></div>) : <strong>Belum ditetapkan</strong>}</div>
                                <div><span>Jadwal kelas</span><strong>{scheduled ? formatDate(course.schedule?.date ?? null) || 'Belum ditetapkan' : 'Belajar sesuai ritme Anda'}</strong><p>{scheduled ? course.schedule?.time || 'Waktu belum ditetapkan' : 'Akses materi secara mandiri'}</p></div>
                                <div><span>Alokasi belajar</span><strong>{learn.jam_pelajaran ?? 0} JP</strong><p>{course.access_hours ? `Masa akses ${course.access_hours} jam` : 'Mengikuti ketentuan kelas'}</p></div>
                            </div>
                        </section>
                        <section className="my-course-about"><h2>Apa yang akan Anda pelajari?</h2><p className="my-course-section-intro">Rangkaian materi dan aktivitas dalam kelas ini.</p>{course.activities.length ? <ul className="my-course-checklist">{course.activities.map(activity => <li key={activity.id}><Icon name="check" size="18px" /><span>{activity.title}<small>{({ video: 'Video pembelajaran', literature: 'Bahan bacaan', task: 'Tugas', pre_test: 'Pre-test', post_test: 'Post-test', meeting: 'Pertemuan daring' } as Record<string, string>)[activity.type] || 'Aktivitas pembelajaran'}</small></span></li>)}</ul> : <p className="my-course-section-intro">Materi pelatihan belum tersedia.</p>}</section>
                        <section className="my-course-about"><h2>Untuk siapa kelas ini?</h2><ul className="my-course-checklist"><li><Icon name="check" size="18px" /><span>{course.employee_status || 'Semua pegawai'}<small>Status kepegawaian peserta yang dapat mengikuti kelas.</small></span></li><li><Icon name="check" size="18px" /><span>{course.visibility === 'public' ? 'Publik — semua OPD' : `Privat — ${course.opd_name || 'OPD tertentu'}`}<small>Cakupan peserta pelatihan.</small></span></li></ul></section>
                        <section className="my-course-about"><h2>Detail keikutsertaan</h2><dl className="my-course-facts"><div><dt>Peserta terdaftar</dt><dd>{Number(course.total_participant || 0).toLocaleString('id-ID')} peserta</dd></div><div><dt>Kuota kelas</dt><dd>{course.schedule?.quota ?? course.quota ?? 'Belum ditetapkan'}</dd></div><div><dt>Metode pembelajaran</dt><dd>{scheduled ? 'Mengikuti jadwal pelatihan' : 'Pembelajaran mandiri'}</dd></div><div><dt>Status Anda</dt><dd>{completed ? 'Selesai' : 'Berjalan'}</dd></div></dl></section>
                    </>}
                    {tab === 'guide' && <>
                        <section className="my-course-about"><h2>Panduan pelatihan</h2><ol className="my-course-steps"><li><strong>Buka ruang belajar</strong><p>Klik Mulai pelatihan untuk membuka materi dan aktivitas kelas.</p></li><li><strong>Ikuti urutan pembelajaran</strong><p>Pelajari materi dan selesaikan aktivitas sesuai petunjuk di ruang belajar.</p></li><li><strong>Pantau hasil belajar</strong><p>Lihat catatan pembelajaran dan periksa sertifikat setelah diterbitkan.</p></li></ol><button type="button" className="my-course-start" onClick={startLearning} disabled={!learn.entry_url}><Icon name="play-circle" size="20px" />{completed ? 'Lihat materi pelatihan' : 'Mulai pelatihan'}</button>{learn.entry_message && <p className="my-course-entry-message">{learn.entry_message}</p>}</section>
                    <section aria-labelledby="learning-summary-heading">
                        <div className="my-course-section-heading"><span aria-hidden="true" /><div><p className="my-course-eyebrow">CATATAN BELAJAR</p><h2 id="learning-summary-heading">Ringkasan pembelajaran</h2></div></div>
                        <dl className="my-course-stats">
                            {stats.map(stat => <div key={stat.label} className="my-course-stat">
                                <div className="my-course-stat__top"><dt>{stat.label}</dt><span className="my-course-stat__icon"><Icon name={stat.icon} size="20px" /></span></div>
                                <dd>{stat.value}</dd>
                                <p>{stat.note}</p>
                            </div>)}
                        </dl>
                    </section>

                    </>}
                    {tab === 'certificate' && <>
                    <section className="my-course-certificate">
                        <div className="my-course-certificate__header"><span className="my-course-certificate__icon"><Icon name="award" size="32px" /></span><span className={`my-course-certificate__badge ${certificateAvailable ? 'is-available' : ''}`}>{certificateAvailable ? 'Tersedia' : 'Belum tersedia'}</span></div>
                        <p className="my-course-eyebrow">PENCAPAIAN ANDA</p>
                        <h2 id="certificate-heading">Sertifikat pelatihan</h2>
                        <p>{certificateAvailable ? 'Sertifikat Anda sudah tersedia. Simpan sebagai dokumentasi pengembangan kompetensi.' : 'Sertifikat akan tampil di sini ketika sudah diterbitkan. Pantau ketersediaannya setelah menyelesaikan kelas.'}</p>
                        {certificateAvailable ? <a href={learn.url_certificate!} target="_blank" rel="noopener noreferrer" className="my-course-download"><Icon name="download" size="19px" />Unduh sertifikat<span className="sr-only"> (buka tab baru)</span></a> : <button type="button" className="my-course-download" disabled><Icon name="lock-alt" size="18px" />Unduh sertifikat</button>}
                        <div className="my-course-certificate__footnote"><Icon name={certificateAvailable ? 'check-shield' : 'info-circle'} size="17px" /><span>{certificateAvailable ? 'Diterbitkan untuk keikutsertaan Anda.' : 'Tombol aktif setelah sertifikat tersedia.'}</span></div>
                    </section>
                    </>}
                    {tab === 'reviews' && <section className="my-course-about"><div className="my-course-review-heading"><h2>Ulasan peserta</h2><span><Icon name="star" size="18px" /><strong>{Number(course.rating.star).toLocaleString('id-ID', { maximumFractionDigits: 1 })}</strong> · {course.rating.total} ulasan</span></div>{course.reviews.length ? course.reviews.map(review => <article className="my-course-review" key={review.id}><div><strong>{review.name}</strong><span aria-label={`${review.star} dari 5 bintang`}>{'★'.repeat(Math.max(0, Math.min(5, Math.round(review.star))))}</span></div><p>{review.description || 'Peserta tidak menuliskan komentar.'}</p><small>{formatDate(review.date)}</small></article>) : <div className="my-course-empty"><Icon name="message-rounded-dots" size="32px" /><h3>Belum ada ulasan</h3><p>Pengalaman peserta akan tampil di sini setelah ulasan diberikan.</p></div>}</section>}
                </div>
            </div>
        </div>
        <Dialog open={resetOpen} onClose={setResetOpen} className="my-course-reset-dialog">
            <div className="my-course-reset-dialog__backdrop" aria-hidden="true" />
            <div className="my-course-reset-dialog__position"><DialogPanel className="my-course-reset-dialog__panel">
                <DialogTitle as="h2">Reset seluruh proses belajar?</DialogTitle>
                <p>Semua jawaban, nilai, tugas PDF, progres kegiatan, dan sertifikat kelas ini akan dihapus. Pendaftaran kelas tetap ada. Sertifikat yang sudah tercatat di sistem KEDATON/Prestige tidak dapat ditarik otomatis. Tindakan ini tidak dapat dibatalkan.</p>
                <div className="my-course-reset-dialog__actions"><button type="button" onClick={() => setResetOpen(false)} disabled={resetting}>Batal</button><button type="button" className="is-danger" onClick={resetLearning} disabled={resetting}>{resetting ? 'Mereset...' : 'Ya, reset belajar'}</button></div>
            </DialogPanel></div>
        </Dialog>
    </App>;
}
