import { Head, Link, usePage } from '@inertiajs/react';
import '../../../../css/participant-survey.css';

const Index = ({ course, progress, survey_url = 'http://bit.ly/SurveiKepuasanPenggunaGAPURA' }) => {
    const { site } = usePage().props;

    return (
        <div className="survey-page">
            <Head title={`Survei Kepuasan - ${course.title}`} />
            <div className="survey-shell">
                <header className="survey-header">
                    <Link href="/my-course" className="survey-brand" aria-label="Gapura — Kelas Saya"><img src={site?.logo} alt="GAPURA" /></Link>
                    <div className="survey-header__course"><span>Kelas Anda</span><h1>{course.title}</h1></div>
                    <span className="survey-progress" aria-label={`Progres belajar ${progress}`}><i className="bx bx-check-circle" aria-hidden="true" />{progress}</span>
                </header>

                <main className="survey-body">
                    <section className="survey-card" aria-labelledby="survey-title">
                        <div className="survey-card__intro">
                            <span className="survey-complete"><i className="bx bx-check-circle" aria-hidden="true" />Kegiatan kelas selesai</span>
                            <div>
                                <p className="survey-eyebrow">Survei kepuasan</p>
                                <h2 id="survey-title">Terima kasih sudah belajar bersama GAPURA.</h2>
                                <p className="survey-description">Sebelum menutup kelas ini, bagikan pengalaman Anda melalui survei kepuasan pengguna. Masukan Anda membantu kami meningkatkan layanan pembelajaran.</p>
                            </div>
                            <div className="survey-course"><span>Kelas yang Anda ikuti</span><strong>{course.title}</strong></div>
                        </div>

                        <div className="survey-content">
                            <div className="survey-content__heading"><span className="survey-icon" aria-hidden="true"><i className="bx bx-clipboard" /></span><div><h3>Satu langkah terakhir</h3><p>Isi survei, lalu kembali ke daftar kelas Anda.</p></div></div>
                            <ol className="survey-steps">
                                <li>
                                    <span className="survey-step-number" aria-hidden="true">1</span>
                                    <div className="survey-step-copy">
                                        <h4>Bagikan pengalaman Anda</h4>
                                        <p>Buka formulir dan isi pertanyaan sesuai pengalaman Anda menggunakan GAPURA.</p>
                                        <a href={survey_url} target="_blank" rel="noreferrer" className="survey-open" aria-describedby="survey-external-note">Buka formulir survei<i className="bx bx-link-external" aria-hidden="true" /></a>
                                        <span id="survey-external-note" className="survey-external-note">Formulir terbuka di tab baru.</span>
                                    </div>
                                </li>
                                <li>
                                    <span className="survey-step-number" aria-hidden="true">2</span>
                                    <div className="survey-step-copy"><h4>Kembali setelah selesai</h4><p>Setelah mengirim formulir survei, kembali ke halaman ini untuk menuju Kelas Saya.</p></div>
                                </li>
                            </ol>
                            <div className="survey-actions"><span>Sudah selesai mengisi survei?</span><Link href={route('participant.course.index')} className="survey-return">Kembali ke Kelas Saya<i className="bx bx-right-arrow-alt" aria-hidden="true" /></Link></div>
                        </div>
                    </section>
                </main>
                <footer className="survey-footer">&copy; {new Date().getFullYear()} GAPURA. All Rights Reserved.</footer>
            </div>
        </div>
    );
};

export default Index;
