import StarRating from '@/Components/StarRating';
import { showCourseActivationToast } from '@/Components/CourseActivationToast';
import App from '@/Layouts/App';
import { getBrowserSupportStatus, getUnsupportedBrowserMessage } from '@/Utils/browserSupport';
import { getYouTubeEmbedUrl } from '@/Utils/youtube';
import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import DOMPurify from 'dompurify';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { useState } from 'react';
import '../../../css/public-course-detail.css';

type Course = {
    id: string;
    type: 'schedule' | 'unschedule';
    title: string;
    employee_status: string;
    short_description: string | null;
    description: string | null;
    teaser_video: string | null;
    slug: string;
    quota: string | number | null;
    thumbnail: string;
    category: string;
    total_participant: number;
    total_passed: number;
    opd_name?: string | null;
    visibility?: 'public' | 'private';
};

type Schedule = {
    id: string;
    date: string | null;
    time: string | null;
    time_from: string | null;
    quota: string | number | null;
    can_enter: boolean;
    start_label: string | null;
    is_active: boolean | number;
};

type Review = { name: string; star: number; description: string | null; created_at: string };
type Rating = { star?: number; value?: string | number; total?: number };
type Trainer = { id: string; name: string; position: string | null; photo: string };
type PageProps = { course: Course; schedules?: Schedule[]; reviews?: Review[]; rating?: Rating; trainers?: Trainer[] };

const primaryButton = 'inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-[10px] bg-[linear-gradient(90deg,#019B91_0%,#018083_100%)] px-5 font-poppins text-sm font-semibold text-white transition duration-200 hover:opacity-90 active:scale-[0.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60';

function formatNumber(value) {
    return Number(value || 0).toLocaleString('id-ID');
}

function formatDate(value) {
    if (!value || value === 'Tidak Terjadwal') return 'Fleksibel';
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

export default function CourseShow({ course, schedules = [], reviews = [], rating = {}, trainers = [] }: PageProps) {
    const { auth } = usePage().props as { auth: { user?: unknown } };
    const [activeSchedules, setActiveSchedules] = useState<string[]>([]);
    const [loadingScheduleId, setLoadingScheduleId] = useState<string | null>(null);
    const [reviewPage, setReviewPage] = useState(1);
    const [loginDialogOpen, setLoginDialogOpen] = useState(false);
    const reviewsPerPage = 6;
    const totalReviewPages = Math.ceil(reviews.length / reviewsPerPage);
    const currentReviews = reviews.slice((reviewPage - 1) * reviewsPerPage, reviewPage * reviewsPerPage);
    const ratingValue = Number(rating.value || rating.star || 0).toFixed(1);
    const employeeStatuses = [...new Set((course.employee_status || '').split(',').map((status) => status.trim()).filter(Boolean))];
    const teaserVideoUrl = getYouTubeEmbedUrl(course.teaser_video);

    const scrollToSection = (id) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });

    const handleActivation = (event: React.FormEvent, schedule: Schedule) => {
        event.preventDefault();
        const browserSupport = getBrowserSupportStatus();
        if (!browserSupport.supported) {
            showCourseActivationToast({ tone: 'error', title: 'Browser belum mendukung', message: getUnsupportedBrowserMessage(browserSupport.reason) }, 'unsupported-browser');
            return;
        }
        if (!auth.user) {
            setLoginDialogOpen(true);
            return;
        }
        if (!schedule.can_enter) {
            const time = schedule.start_label ?? schedule.time_from ?? '';
            showCourseActivationToast({ tone: 'warning', title: 'Kelas belum dimulai', message: time ? `Aktivasi tersedia mulai pukul ${time}.` : 'Aktivasi tersedia ketika jadwal kelas dimulai.' }, `not-started-${schedule.id}`);
            return;
        }
        setLoadingScheduleId(schedule.id);
        axios.post(`/course/${course.slug}/${schedule.id}`)
            .then(() => {
                setActiveSchedules((current) => [...current, schedule.id]);
                showCourseActivationToast({ tone: 'success', title: 'Kelas berhasil diaktifkan', message: 'Materi sudah siap. Mulai belajar dan lanjutkan progres Anda di Kelas Saya.', action: { label: 'Buka kelas saya', href: '/my-course' } }, `success-${schedule.id}`);
            })
            .catch((error) => showCourseActivationToast({ tone: 'error', title: 'Kelas belum berhasil diaktifkan', message: error.response?.data?.message || 'Silakan coba lagi dalam beberapa saat.' }, `error-${schedule.id}`))
            .finally(() => setLoadingScheduleId(null));
    };

    const handleViewCourse = (event: React.MouseEvent, schedule: Schedule) => {
        event.preventDefault();
        if (!schedule.can_enter) {
            const time = schedule.start_label ?? '';
            showCourseActivationToast({ tone: 'warning', title: 'Kelas belum dimulai', message: time ? `Kelas dapat dibuka mulai pukul ${time}.` : 'Kelas dapat dibuka ketika jadwal dimulai.' }, `not-started-view-${schedule.id}`);
            return;
        }
        router.visit('/my-course');
    };

    return (
        <App>
            <Head title={course.title} />
            <Dialog open={loginDialogOpen} onClose={setLoginDialogOpen} className="course-activation-dialog">
                <div className="course-activation-dialog__backdrop" aria-hidden="true" />
                <div className="course-activation-dialog__position">
                    <DialogPanel className="course-activation-dialog__panel">
                        <span className="course-activation-dialog__icon" aria-hidden="true"><i className="bx bx-lock-alt" /></span>
                        <DialogTitle as="h2">Masuk untuk mengaktifkan kelas</DialogTitle>
                        <p>Masuk ke akun pengguna agar kelas dapat diaktifkan dan progres belajar Anda tersimpan.</p>
                        <div className="course-activation-dialog__actions">
                            <button type="button" onClick={() => setLoginDialogOpen(false)}>Batal</button>
                            <Link href={route('sign-in.index')} className="course-activation-dialog__primary">Masuk ke akun</Link>
                        </div>
                    </DialogPanel>
                </div>
            </Dialog>
            <div className="public-course-detail">
                <section className="course-detail-hero">
                    <img src={course.thumbnail} alt="" className="course-detail-hero-background" aria-hidden="true" />
                    <div className="course-detail-hero-overlay" />
                    <div className="course-detail-hero-inner">
                        <div>
                            <nav aria-label="Breadcrumb" className="mb-6 flex flex-wrap items-center gap-2 font-poppins text-xs text-[#BDE8E2]">
                                <Link href="/" className="hover:text-white">Beranda</Link>
                                <i className="bx bx-chevron-right" aria-hidden="true" />
                                <Link href={route('course.index')} className="hover:text-white">Materi pilihan</Link>
                                <i className="bx bx-chevron-right" aria-hidden="true" />
                                <span aria-current="page" className="text-white">Detail kelas</span>
                            </nav>
                            <h1 className="font-poppins text-3xl font-semibold leading-tight text-white sm:text-4xl lg:text-[40px]">{course.title}</h1>
                            <div className="mt-4 flex flex-wrap gap-2 font-poppins text-xs font-semibold">
                                <span className="rounded-md bg-[#BEAF0D] px-3 py-1.5 text-white">{course.category || 'Pelatihan ASN'}</span>
                                <span className="rounded-md bg-[#E6F3F3] px-3 py-1.5 text-[#075D5E]">{course.type === 'schedule' ? 'Terjadwal' : 'Belajar fleksibel'}</span>
                            </div>
                            <p className="mt-4 max-w-xl font-poly text-base leading-7 text-[#DFF7F3]">{course.short_description || 'Pelatihan untuk mendukung pengembangan kompetensi aparatur sipil negara.'}</p>
                            <a href="#course-schedules" className="mt-6 inline-flex items-center gap-2 font-poppins text-sm font-medium text-[#9AE5D9] underline decoration-[#9AE5D9]/40 underline-offset-4 hover:text-white">Lihat jadwal dan aktivasi<i className="bx bx-down-arrow-alt text-lg" aria-hidden="true" /></a>
                        </div>
                        <div>
                            <div className="relative aspect-video overflow-hidden rounded-[10px] bg-[#044849]">
                                <img src={course.thumbnail} alt={course.title} onError={(event) => { event.currentTarget.onerror = null; event.currentTarget.src = '/assets/media/images/static/default-class.jpg'; }} className="h-full w-full object-cover" />
                                {teaserVideoUrl && <button type="button" onClick={() => scrollToSection('course-video')} className="absolute bottom-3 left-3 inline-flex min-h-10 items-center gap-2 rounded-[10px] bg-white px-4 font-poppins text-xs font-semibold text-primary shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"><i className="bx bx-play-circle text-lg" aria-hidden="true" />Pratinjau kelas</button>}
                            </div>
                            <dl className="mt-3 grid grid-cols-2 gap-3 rounded-[10px] bg-[#044849]/75 p-4">
                                <div className="flex items-start gap-2">
                                    <i className="bx bx-calendar mt-0.5 text-lg text-[#9AE5D9]" aria-hidden="true" />
                                    <div><dt className="font-inter text-[11px] text-[#BDE8E2]">Jadwal pembelajaran</dt><dd className="mt-1 font-poppins text-xs leading-5 text-white">{course.type === 'schedule' ? (schedules[0] ? formatDate(schedules[0].date) : 'Belum tersedia') : 'Kapan saja'}</dd></div>
                                </div>
                                <div className="flex items-start gap-2">
                                    <i className="bx bx-group mt-0.5 text-lg text-[#9AE5D9]" aria-hidden="true" />
                                    <div><dt className="font-inter text-[11px] text-[#BDE8E2]">Sasaran pengguna</dt><dd className="mt-1 font-poppins text-xs leading-5 text-white">{course.visibility === 'public' ? 'Publik — semua OPD' : `Privat — ${course.opd_name || 'OPD tertentu'}`}</dd></div>
                                </div>
                            </dl>
                        </div>
                    </div>
                </section>

                <div className="course-detail-content">
                    <div className="order-2 min-w-0 space-y-5 lg:order-1">
                        <section id="course-description" className="course-detail-panel scroll-mt-24" aria-labelledby="description-heading">
                            <SectionHeading eyebrow="Tentang pelatihan" title="Apa yang akan Anda pelajari?" id="description-heading" />
                            <div className="course-detail-description prose prose-sm max-w-none font-inter text-[#526176] prose-headings:font-poppins prose-headings:text-[#075D5E] prose-strong:text-[#075D5E] prose-a:text-primary" dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(course.description || '<p>Deskripsi kelas belum tersedia.</p>') }} />
                        </section>
                        <section className="course-detail-panel" aria-labelledby="audience-heading">
                            <SectionHeading eyebrow="Sasaran pengguna" title="Untuk siapa kelas ini?" id="audience-heading" />
                            <p className="mt-4 font-inter text-sm leading-6 text-[#526176]">Kelas ini ditujukan bagi {course.opd_name ? `pegawai ${course.opd_name}` : 'aparatur sipil negara'} dengan status kepegawaian berikut.</p>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {(employeeStatuses.length ? employeeStatuses : ['Semua pegawai']).map((status) => <span key={status} className="rounded-md border border-primary/20 bg-transparent px-3 py-1 font-poppins text-xs text-primary">{status}</span>)}
                            </div>
                        </section>
                        <section className="course-detail-panel" aria-labelledby="overview-heading">
                            <SectionHeading eyebrow="Ringkasan kelas" title="Informasi pembelajaran" id="overview-heading" />
                            <dl className="mt-4 grid grid-cols-2 gap-5 font-inter text-sm sm:grid-cols-3">
                                {[
                                    ['group', 'Pengguna terdaftar', formatNumber(course.total_participant)],
                                    ['check-circle', 'Pengguna lulus', formatNumber(course.total_passed)],
                                    ['star', 'Penilaian kelas', ratingValue],
                                ].map(([icon, label, value]) => <div key={label}><dt className="text-xs text-[#66778C]">{label}</dt><dd className="mt-2 flex items-center gap-2 font-poppins text-lg font-semibold text-[#075D5E]"><i className={`bx ${icon === 'star' ? 'bxs-star text-[#F68104]' : `bx-${icon} text-primary`} text-lg`} aria-hidden="true" />{value}</dd></div>)}
                            </dl>
                        </section>
                        {teaserVideoUrl && <section id="course-video" className="scroll-mt-24 overflow-hidden rounded-[15px] border border-[#E8ECF1] bg-white shadow-[0_2px_16px_rgba(1,128,131,0.06)]" aria-labelledby="video-heading">
                            <header className="border-b border-[#E8ECF1] px-5 py-5 sm:px-7">
                                <SectionHeading eyebrow="Pratinjau" title="Kenali kelas sebelum memulai" id="video-heading" compact />
                            </header>
                            <div className="bg-white p-3 sm:p-5">
                                <div className="aspect-video overflow-hidden rounded-[10px] bg-[#075D5E] shadow-sm">
                                    <iframe
                                        src={teaserVideoUrl}
                                        title={`Video pengantar ${course.title}`}
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowFullScreen
                                        referrerPolicy="strict-origin-when-cross-origin"
                                        loading="lazy"
                                        className="h-full w-full border-0"
                                    />
                                </div>
                            </div>
                        </section>}
                        <section id="course-reviews" className="scroll-mt-24 overflow-hidden rounded-[15px] border border-[#E8ECF1] bg-white shadow-[0_2px_16px_rgba(1,128,131,0.06)]" aria-labelledby="reviews-heading">
                            <header className="flex flex-col gap-5 border-b border-[#E8ECF1] px-6 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-8"><SectionHeading eyebrow="Pengalaman pengguna" title="Ulasan kelas" id="reviews-heading" compact /><div className="flex min-w-[210px] items-center gap-4 rounded-[12px] bg-[#F6F9F8] px-4 py-3"><strong className="font-poppins text-3xl font-semibold text-[#018083]">{ratingValue}</strong><div><StarRating value={Number(rating.star || 0)} size="16px" /><p className="mt-1 font-inter text-xs text-[#8496B0]">{formatNumber(rating.total)} ulasan</p></div></div></header>
                            {currentReviews.length > 0 ? <div className="divide-y divide-[#E8ECF1]">{currentReviews.map((review, index) => <article key={`${review.name}-${index}`} className="px-6 py-6 sm:px-8"><div className="flex items-start justify-between gap-4"><div className="flex min-w-0 items-center gap-3"><span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#E6F3F3] font-poppins font-semibold text-[#018083]">{review.name?.charAt(0)?.toUpperCase() || 'P'}</span><div className="min-w-0"><h3 className="truncate font-poppins text-sm font-semibold text-[#075D5E]">{review.name || 'Pengguna GAPURA'}</h3><p className="mt-0.5 font-inter text-xs text-[#8496B0]">{review.created_at}</p></div></div><StarRating value={Number(review.star || 0)} size="15px" /></div><p className="mt-4 border-l-2 border-[#018083]/30 pl-4 font-inter text-sm leading-6 text-[#526176]">{review.description || 'Pengguna tidak menuliskan komentar.'}</p></article>)}</div> : <div className="px-6 py-12 text-center"><span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#E6F3F3]"><box-icon name="message-rounded-dots" color="#018083" size="25px" /></span><h3 className="mt-4 font-poppins font-semibold text-[#075D5E]">Belum ada ulasan</h3><p className="mt-1 font-inter text-sm text-[#8496B0]">Ulasan pengguna akan tampil setelah kelas selesai.</p></div>}
                            {totalReviewPages > 1 && <nav aria-label="Halaman ulasan" className="flex items-center justify-between border-t border-[#E8ECF1] bg-[#FAFCFB] px-6 py-4"><p className="font-inter text-sm text-[#8496B0]">Halaman {reviewPage} dari {totalReviewPages}</p><div className="flex gap-2"><PageButton label="Sebelumnya" icon="chevron-left" disabled={reviewPage === 1} onClick={() => setReviewPage((page) => Math.max(page - 1, 1))} /><PageButton label="Berikutnya" icon="chevron-right" disabled={reviewPage === totalReviewPages} onClick={() => setReviewPage((page) => Math.min(page + 1, totalReviewPages))} /></div></nav>}
                        </section>
                    </div>

                    <aside id="course-schedules" className="course-detail-sidebar order-1 scroll-mt-24 lg:order-2 lg:sticky lg:top-24" aria-labelledby="schedules-heading">
                        <div className="overflow-hidden rounded-[15px] bg-white shadow-[0_2px_16px_rgba(1,128,131,0.06)]">
                            <div className="border-b border-[#E8ECF1] p-5 text-center"><p className="font-poppins text-[10px] font-semibold uppercase tracking-[0.12em] text-[#018083]">Pendaftaran kelas</p><h2 id="schedules-heading" className="mt-2 font-poppins text-lg font-semibold text-[#075D5E]">{course.title}</h2><p className="mt-2 font-inter text-sm leading-6 text-[#526176]">{course.type === 'schedule' ? 'Pilih jadwal yang paling sesuai untuk Anda.' : 'Aktifkan kelas dan pelajari materi sesuai ritme Anda.'}</p></div>
                            <div className="space-y-4 p-5">
                                {schedules.map((schedule, index) => {
                                    const isActive = activeSchedules.includes(schedule.id) || schedule.is_active;
                                    const isLoading = loadingScheduleId === schedule.id;
                                    return <article key={schedule.id} className="rounded-[10px] border border-[#E8ECF1] bg-[#F6FAF9] p-4">{course.type === 'schedule' && <p className="mb-3 font-poppins text-xs font-semibold uppercase tracking-[0.06em] text-[#018083]">Jadwal {index + 1}</p>}<dl className="space-y-3 font-inter text-sm"><ScheduleRow icon="calendar" label="Tanggal" value={formatDate(schedule.date)} /><ScheduleRow icon="time-five" label="Waktu" value={schedule.time && schedule.time !== 'Tidak Terjadwal' ? `${schedule.time} WIB` : 'Fleksibel'} /><ScheduleRow icon="group" label="Kuota" value={schedule.quota || course.quota || 'Tidak Terbatas'} /></dl>{isActive ? <button type="button" onClick={(event) => handleViewCourse(event, schedule)} className={`${primaryButton} mt-5`}>Lihat kelas<box-icon name="right-arrow-alt" color="#FFFFFF" size="20px" /></button> : <form onSubmit={(event) => handleActivation(event, schedule)}><button type="submit" disabled={isLoading} className={`${primaryButton} mt-5`}>{isLoading && <box-icon name="loader-alt" animation="spin" color="#FFFFFF" size="18px" />}{isLoading ? 'Mengaktifkan...' : 'Aktifkan kelas'}{!isLoading && <box-icon name="right-arrow-alt" color="#FFFFFF" size="20px" />}</button></form>}{!schedule.can_enter && course.type === 'schedule' && <p className="mt-2 text-center font-inter text-xs leading-5 text-[#8496B0]">Aktivasi tersedia ketika jadwal dimulai.</p>}</article>;
                                })}
                                {schedules.length === 0 && <div className="rounded-[12px] border border-dashed border-[#C2C9D6] bg-[#FAFCFB] px-5 py-8 text-center"><box-icon name="calendar-x" color="#8496B0" size="30px" /><p className="mt-2 font-poppins text-sm font-semibold text-[#075D5E]">Belum ada jadwal</p><p className="mt-1 font-inter text-xs leading-5 text-[#8496B0]">Silakan kembali untuk melihat jadwal terbaru.</p></div>}
                            </div>
                            <div className="flex items-start gap-3 border-t border-[#E8ECF1] bg-[#FAFCFF] px-5 py-4"><box-icon name="shield-quarter" color="#018083" size="20px" /><p className="font-inter text-xs leading-5 text-[#66778C]">Progres belajar tersimpan otomatis dan dapat dilanjutkan kapan saja.</p></div>
                        </div>
                        {trainers.length > 0 && <section className="course-detail-panel mt-5" aria-labelledby="trainers-heading">
                            <div className="text-center"><SectionHeading eyebrow="Belajar bersama" title="Pemateri" id="trainers-heading" compact /></div>
                            <div className="mt-4 divide-y divide-[#E8ECF1]">
                                {trainers.map((trainer) => <article key={trainer.id} className="flex flex-col items-center gap-2 py-4 text-center first:pt-0 last:pb-0">
                                    <img src={trainer.photo} alt="" className="h-16 w-16 shrink-0 rounded-full object-cover" onError={(event) => { event.currentTarget.onerror = null; event.currentTarget.src = '/assets/media/images/static/default-profile.jpg'; }} />
                                    <div><h3 className="font-poppins text-sm font-semibold text-[#075D5E]">{trainer.name}</h3>{trainer.position && <p className="mt-1 font-inter text-xs leading-5 text-[#66778C]">{trainer.position}</p>}</div>
                                </article>)}
                            </div>
                        </section>}
                    </aside>
                </div>
            </div>
        </App>
    );
}

function SectionHeading({ eyebrow, title, id, compact = false }) {
    return <div className="course-detail-section-heading"><p className="mb-1 font-poppins text-[10px] font-medium italic text-primary">{eyebrow}</p><h2 id={id} className={`font-poppins font-semibold leading-snug text-[#075D5E] ${compact ? 'text-base' : 'text-lg'}`}>{title}</h2></div>;
}

function ScheduleRow({ icon, label, value }) {
    return <div className="flex items-start justify-between gap-4"><dt className="flex items-center gap-2 text-[#8496B0]"><box-icon name={icon} color="#8496B0" size="17px" />{label}</dt><dd className="text-right font-semibold text-[#1A2B3F]">{value}</dd></div>;
}

function PageButton({ label, icon, disabled, onClick }) {
    return <button type="button" onClick={onClick} disabled={disabled} aria-label={label} className="flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#E8ECF1] bg-white transition hover:border-[#018083] disabled:bg-[#FAFCFB] disabled:opacity-40"><box-icon name={icon} color="#526176" size="18px" /></button>;
}
