import CardActivity from "@/Components/CardActivity";
import AdminLayout from "@/Layouts/AdminLayout";
import { btnSecondary, card, pageDescription, pageHeader, pageTitle } from "@/Components/Admin/ui";
import { Head, Link } from "@inertiajs/react";
import { useState } from "react";

const STEPS = [
    { key: null, label: 'Pilih Jenis Aktivitas' },
    { key: 'question', label: 'Jenis Pertanyaan' },
    { key: 'interactive', label: 'Jenis Interaktif' },
];

const Activity = ({ slug, type }) => {
    const [activeSection, setActiveSection] = useState(null);

    const stepLabel = STEPS[activeSection === 'question' ? 1 : activeSection === 'interactive' ? 2 : 0].label;
    const isPickingCategory = activeSection !== null;

    return (
        <AdminLayout title="Tambah Aktivitas" hideTitle>
            <Head title="Tambah Aktivitas" />

            <div className="w-full">
                <div className={pageHeader}>
                    <div>
                        <p className="mb-1 text-sm font-medium text-primary-pressed">Kelas / Aktivitas</p>
                        <h1 className={pageTitle}>Tambah aktivitas</h1>
                        <p className={pageDescription}>Pilih jenis aktivitas yang ingin ditambahkan ke kelas ini.</p>
                    </div>
                    <Link href={`/admin/course/${slug}`} className={btnSecondary}>
                        <i className="bx bx-left-arrow-alt text-lg" aria-hidden />
                        Kembali
                    </Link>
                </div>

                <section className={`${card} p-5 sm:p-6`} aria-labelledby="activity-picker-title">
                    <ol className="mb-7 flex items-center gap-3 border-b border-hairline pb-5 text-sm" aria-label="Tahapan tambah aktivitas">
                        <li className="flex items-center gap-2 font-semibold text-primary-pressed">
                            <span className={`flex h-6 w-6 items-center justify-center rounded-full text-xs ${isPickingCategory ? 'bg-primary text-white' : 'bg-primary-mist text-primary-pressed'}`}>{isPickingCategory ? '✓' : '1'}</span>
                            Jenis aktivitas
                        </li>
                        <span className="h-px w-7 bg-hairline-strong" aria-hidden />
                        <li className={`flex items-center gap-2 font-semibold ${isPickingCategory ? 'text-primary-pressed' : 'text-steel'}`}>
                            <span className={`flex h-6 w-6 items-center justify-center rounded-full text-xs ${isPickingCategory ? 'bg-primary-mist text-primary-pressed' : 'bg-surface text-steel'}`}>2</span>
                            Subkategori
                        </li>
                    </ol>

                    <div className="mb-5">
                        <h2 id="activity-picker-title" className="text-base font-semibold text-ink">{stepLabel}</h2>
                        <p className="mt-1 text-sm leading-6 text-steel">
                            {activeSection === null && 'Pilih salah satu jenis aktivitas untuk melanjutkan.'}
                            {activeSection === 'question' && 'Pilih jenis evaluasi yang ingin dibuat.'}
                            {activeSection === 'interactive' && 'Pilih metode interaktif untuk kelas ini.'}
                        </p>
                    </div>

                    {!activeSection && (
                        <div className="grid gap-3 sm:grid-cols-2">
                            <CardActivity title="Pertanyaan" description="Buat pre-test, kuis, atau post-test untuk mengukur pemahaman peserta." onClick={() => setActiveSection('question')} />
                            <Link href={`/admin/course/${slug}/activity/task/create`} className="block"><CardActivity title="Tugas" description="Tetapkan instruksi dan bobot tugas untuk peserta." /></Link>
                            <CardActivity title="Interaktif" description="Tambahkan pertemuan daring atau presentasi video." onClick={() => setActiveSection('interactive')} />
                            <Link href={`/admin/course/${slug}/activity/literature/create`} className="block"><CardActivity title="Literatur" description="Unggah materi PDF yang dapat dibaca peserta." /></Link>
                        </div>
                    )}

                    {activeSection === 'question' && (
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Link href={`/admin/course/${slug}/activity/pre-test/create`} className="block"><CardActivity title="Pre Test/Quiz" description="Ukur pemahaman awal peserta sebelum materi dimulai." /></Link>
                            <Link href={`/admin/course/${slug}/activity/post-test/create`} className="block"><CardActivity title="Post Test" description="Nilai pemahaman akhir dan tetapkan batas kelulusan." /></Link>
                        </div>
                    )}

                    {activeSection === 'interactive' && (
                        <div className={`grid gap-3 ${type === 'unschedule' ? 'max-w-md' : 'sm:grid-cols-2'}`}>
                            {type !== 'unschedule' && <Link href={`/admin/course/${slug}/activity/meeting/create`} className="block"><CardActivity title="Pertemuan Daring" description="Atur jadwal dan tautan pertemuan daring." /></Link>}
                            <Link href={`/admin/course/${slug}/activity/video/create`} className="block"><CardActivity title="Presentasi Video" description="Unggah video dan atur durasi belajar peserta." /></Link>
                        </div>
                    )}

                    {activeSection && (
                        <div className="mt-6 border-t border-hairline pt-5">
                            <button type="button" onClick={() => setActiveSection(null)} className={btnSecondary}>
                                <i className="bx bx-left-arrow-alt text-lg" aria-hidden />
                                Kembali ke jenis aktivitas
                            </button>
                        </div>
                    )}
                </section>
            </div>
        </AdminLayout>
    );
};

export default Activity;
