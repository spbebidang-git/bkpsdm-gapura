import App from '@/Layouts/Participant/App';
import { getBrowserSupportStatus, getUnsupportedBrowserMessage } from '@/Utils/browserSupport';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'react-toastify';
import { ParticipantButton, ParticipantEmpty } from '@/Components/Participant/Primitives';
import '../../../../css/participant-course-index.css';

type Course = {
    visibility: 'public' | 'private'; employee_status: string;
    id: string; schedule_id: string; slug: string; title: string; type: string;
    thumbnail: string | null; short_description: string | null; category: string | null;
    finished_at: string | null; initial_activity: string | number; opd_name: string | null;
};

function Icon({ name, size = '20px' }: { name: string; size?: string }) {
    return <box-icon name={name} color="currentColor" size={size} aria-hidden="true" />;
}

export default function Index({ courses = [] }: { courses: Course[] }) {
    const [query, setQuery] = useState('');
    const [filter, setFilter] = useState('all');
    const ongoing = courses.filter(item => item.finished_at == null).length;
    const completedCount = courses.length - ongoing;
    const tabs = [{ id: 'all', label: 'Kelas diikuti', count: courses.length }, { id: 'ongoing', label: 'Berjalan', count: ongoing }, { id: 'completed', label: 'Selesai', count: completedCount }];
    const normalizedQuery = query.trim().toLocaleLowerCase('id-ID');
    const visible = courses.filter(item => (filter === 'all' || (filter === 'ongoing' ? item.finished_at == null : item.finished_at != null)) && item.title.toLocaleLowerCase('id-ID').includes(normalizedQuery));
    const reset = () => { setQuery(''); setFilter('all'); };
    const join = (item: Course) => {
        const support = getBrowserSupportStatus();
        if (!support.supported) { toast.error(getUnsupportedBrowserMessage(support.reason), { toastId: 'unsupported-browser-join-class' }); return; }
        router.visit(`/learn/${item.slug}/${item.schedule_id}/${item.initial_activity}/instruction`);
    };
    return <App>
        <Head title="Kelas Saya" />
        <div className="my-courses">
            <header className="my-courses-header">
                <div><h1>Kelas Saya</h1><p>Semua pelatihan Anda, dalam satu ruang belajar.</p></div>
                <Link className="my-courses-explore" href={route('course.index')}><Icon name="compass" />Jelajahi kelas<Icon name="right-arrow-alt" size="18px" /></Link>
            </header>
            <section className="my-courses-collection" aria-label="Daftar kelas Anda">
                <div className="my-courses-toolbar">
                    <div role="group" aria-label="Filter status kelas" className="my-courses-filters">{tabs.map(tab => <button key={tab.id} type="button" aria-pressed={filter === tab.id} onClick={() => setFilter(tab.id)} className={filter === tab.id ? 'is-active' : ''}>{tab.label}<span>{tab.count}</span></button>)}</div>
                    <div className="my-courses-search"><label htmlFor="participant-course-search" className="sr-only">Cari judul kelas</label><Icon name="search" /><input id="participant-course-search" type="search" value={query} onChange={event => setQuery(event.target.value)} placeholder="Cari judul kelas…" /></div>
                </div>
                <div className="my-courses-results"><p aria-live="polite">Menampilkan <strong>{visible.length}</strong> dari {courses.length} kelas</p>{(query || filter !== 'all') && <button type="button" onClick={reset}>Reset filter<Icon name="reset" size="15px" /></button>}</div>
                {courses.length === 0 ? <ParticipantEmpty title="Belum ada kelas" description="Temukan pelatihan untuk mengembangkan kompetensi Anda. Kelas yang diikuti akan tampil di sini." action={<ParticipantButton href={route('course.index')}>Jelajahi kelas</ParticipantButton>} /> : visible.length === 0 ? <ParticipantEmpty title="Kelas tidak ditemukan" description="Coba judul lain atau ubah filter status kelas." action={<ParticipantButton variant="secondary" onClick={reset}>Reset pencarian dan filter</ParticipantButton>} /> : <div className="my-courses-grid">{visible.map(item => {
                    const scheduled = item.type === 'schedule' || item.type === 'scheduled';
                    const completed = item.finished_at != null;
                    const detailUrl = route('participant.course.show', { course: item.slug });
                    return <article key={`${item.id}-${item.schedule_id}`} className="my-courses-card">
                        <Link href={detailUrl} className="my-courses-card__image" tabIndex={-1} aria-hidden="true"><img src={item.thumbnail || '/assets/media/images/static/default-class.jpg'} alt="" loading="lazy" onError={event => { event.currentTarget.onerror = null; event.currentTarget.src = '/assets/media/images/static/default-class.jpg'; }} /><span className="my-courses-card__mode"><Icon name={scheduled ? 'calendar' : 'book-open'} size="15px" />{scheduled ? 'Terjadwal' : 'Belajar mandiri'}</span></Link>
                        <div className="my-courses-card__body">
                            <div className="my-courses-card__tags"><span className="my-courses-card__category">{item.category || 'Pelatihan ASN'}</span><span className={`my-courses-card__status ${completed ? 'is-completed' : ''}`}><i aria-hidden="true" />{completed ? 'Selesai' : 'Berjalan'}</span></div>
                            <h2><Link href={detailUrl}>{item.title}</Link></h2>
                            <p className="mt-2 text-xs text-primary">{item.visibility === 'public' ? 'Publik — semua OPD' : `Privat — ${item.opd_name || 'OPD tertentu'}`}</p>
                            <p className="mt-1 text-xs text-steel">{item.employee_status || 'Semua status pegawai'}</p>
                            <p className="my-courses-card__description">{item.short_description || 'Kembangkan kompetensi Anda melalui rangkaian pembelajaran kelas ini.'}</p>
                            {!completed && <div className="my-courses-card__meta"><Icon name="time-five" size="17px" /><span>{scheduled ? 'Belajar sesuai jadwal kelas' : 'Waktu belajar fleksibel'}</span></div>}
                            <div className="my-courses-card__actions"><Link href={detailUrl} className="my-courses-card__detail">Detail kelas<Icon name="right-arrow-alt" size="17px" /></Link>{!completed && <button type="button" className="my-courses-card__start" onClick={() => join(item)} disabled={!item.initial_activity}><Icon name="play-circle" size="18px" />{item.initial_activity ? 'Lanjut belajar' : 'Materi belum tersedia'}</button>}</div>
                        </div>
                    </article>;
                })}</div>}
            </section>
        </div>
    </App>;
}
