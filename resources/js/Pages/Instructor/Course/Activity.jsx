import BreadcrumbLearn from "@/Components/BreadcrumbLearn";
import CardActivity from "@/Components/CardActivity";
import App from "@/Layouts/Instructor/App";
import { Link } from "@inertiajs/react";
import { useState } from "react";

const STEPS = [
    { key: null, label: 'Pilih Jenis Aktivitas' },
    { key: 'question', label: 'Jenis Pertanyaan' },
    { key: 'interactive', label: 'Jenis Interaktif' },
];

const Activity = ({ slug, type }) => {
    const [activeSection, setActiveSection] = useState(null);

    const currentStep = activeSection ? (activeSection === 'question' ? 1 : 2) : 0;
    const stepLabel = STEPS[activeSection === 'question' ? 1 : activeSection === 'interactive' ? 2 : 0].label;

    return (
        <App>
            <div className="max-w-5xl mx-auto mb-10">

                {/* Breadcrumb */}
                <div className="flex justify-center mb-8 w-full">
                    <div className="inline-flex flex-wrap items-center gap-2 md:gap-3 bg-white/90 backdrop-blur-md py-3 px-6 rounded-full shadow-[0_4px_25px_-4px_rgba(0,0,0,0.06)] border border-gray-100/80">
                        <Link href="/instructor" className="opacity-70 hover:opacity-100 transition-transform hover:-translate-y-0.5">
                            <BreadcrumbLearn title="Dashboard" activeTab={false} icon="grid-alt" size="lg" />
                        </Link>
                        <box-icon name="chevron-right" color="#cbd5e1" size="20px"></box-icon>
                        <Link href={`/instructor/course/${slug}`} className="opacity-70 hover:opacity-100 transition-transform hover:-translate-y-0.5">
                            <BreadcrumbLearn title="Detail Course" activeTab={false} icon="book-bookmark" size="lg" />
                        </Link>
                        <box-icon name="chevron-right" color="#cbd5e1" size="20px"></box-icon>
                        <BreadcrumbLearn title="Tambah Aktivitas" activeTab={true} icon="list-plus" size="lg" />
                    </div>
                </div>

                {/* Stepper */}
                <div className="flex items-center justify-center gap-3 mb-8">
                    {['Jenis Aktivitas', 'Sub Kategori'].map((label, i) => {
                        const isDone = (i === 0 && activeSection !== null);
                        const isActive = (i === 0 && activeSection === null) || (i === 1 && activeSection !== null);
                        return (
                            <div key={i} className="flex items-center gap-3">
                                <div className={`flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold transition-all ${isDone
                                    ? 'bg-green-100 text-green-700'
                                    : isActive
                                        ? 'bg-primary text-white shadow-sm'
                                        : 'bg-gray-100 text-gray-400'
                                    }`}>
                                    <span className={`w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold ${isDone ? 'bg-green-200' : isActive ? 'bg-white/20' : 'bg-gray-200'}`}>
                                        {isDone ? '✓' : i + 1}
                                    </span>
                                    {label}
                                </div>
                                {i < 1 && (
                                    <box-icon name="chevron-right" color={activeSection ? '#10b981' : '#cbd5e1'} size="18px"></box-icon>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* Title */}
                <div className="text-center mb-8">
                    <h1 className="text-2xl font-extrabold text-gray-800 tracking-tight">{stepLabel}</h1>
                    <p className="text-gray-500 text-sm mt-1">
                        {activeSection === null && 'Pilih jenis aktivitas yang ingin ditambahkan ke kursus ini.'}
                        {activeSection === 'question' && 'Pilih jenis pertanyaan yang ingin dibuat.'}
                        {activeSection === 'interactive' && 'Pilih metode interaktif untuk kursus ini.'}
                    </p>
                </div>

                {/* Cards - Semua Kegiatan */}
                {!activeSection && (
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <CardActivity
                            title="Pertanyaan"
                            description="Buat soal pilihan ganda untuk Pre Test, Quiz, dan Post Test guna menilai pemahaman peserta."
                            onClick={() => setActiveSection('question')}
                        />
                        <Link href={`/instructor/course/${slug}/activity/task/create`} className="block">
                            <CardActivity
                                title="Tugas"
                                description="Memasukkan deskripsi penugasan. Hasil tugas peserta dikirim langsung ke email pemateri."
                            />
                        </Link>
                        <CardActivity
                            title="Interaktif"
                            description="Buat kegiatan pertemuan interaktif secara Daring atau Presentasi Video."
                            onClick={() => setActiveSection('interactive')}
                        />
                        <Link href={`/instructor/course/${slug}/activity/literature/create`} className="block">
                            <CardActivity
                                title="Literatur"
                                description="Tambahkan judul, sub judul, dan upload file PDF literatur untuk diakses peserta."
                            />
                        </Link>
                        <Link href={`/instructor/course/${slug}/activity/h5p/create`} className="block">
                            <CardActivity
                                title="H5P Interaktif"
                                description="Unggah paket konten interaktif H5P (.h5p) seperti video interaktif atau kuis."
                            />
                        </Link>
                        <Link href={`/instructor/course/${slug}/activity/scorm/create`} className="block">
                            <CardActivity
                                title="SCORM"
                                description="Unggah paket SCORM (.zip, SCORM 1.2/2004) ekspor dari Articulate, iSpring, dll."
                            />
                        </Link>
                        <Link href={`/instructor/course/${slug}/activity/questionnaire/create`} className="block">
                            <CardActivity
                                title="Kuesioner"
                                description="Survei yang diisi peserta di aplikasi. Jawabannya terekap otomatis dan bisa diunduh."
                            />
                        </Link>
                    </div>
                )}

                {/* Cards - Pertanyaan */}
                {activeSection === 'question' && (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl mx-auto">
                        <Link href={`/instructor/course/${slug}/activity/pre-test/create`} className="block">
                            <CardActivity
                                title="Pre Test/Quiz"
                                description="Soal untuk menguji pemahaman awal peserta. Tidak menjadi syarat penerbitan Sertifikat Digital."
                            />
                        </Link>
                        <Link href={`/instructor/course/${slug}/activity/post-test/create`} className="block">
                            <CardActivity
                                title="Post Test"
                                description="Soal test akhir dengan skor minimum. Menjadi syarat penerbitan Sertifikat Digital."
                            />
                        </Link>
                    </div>
                )}

                {/* Cards - Interaktif */}
                {activeSection === 'interactive' && (
                    <div className={`grid gap-4 ${type === 'unschedule' ? 'grid-cols-1 max-w-xs' : 'grid-cols-1 sm:grid-cols-2 max-w-xl'} mx-auto`}>
                        {type !== 'unschedule' && (
                            <Link href={`/instructor/course/${slug}/activity/meeting/create`} className="block">
                                <CardActivity
                                    title="Pertemuan Daring"
                                    description="Memasukkan detail deskripsi pertemuan Daring dan URL yang digunakan peserta untuk bergabung."
                                />
                            </Link>
                        )}
                        <Link href={`/instructor/course/${slug}/activity/video/create`} className="block">
                            <CardActivity
                                title="Presentasi Video"
                                description="Memasukkan deskripsi dan upload file video yang nanti diakses oleh peserta."
                            />
                        </Link>
                    </div>
                )}

                {/* Back Button */}
                <div className="flex justify-center mt-10">
                    {activeSection ? (
                        <button
                            onClick={() => setActiveSection(null)}
                            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-semibold text-sm transition-all shadow-sm hover:shadow"
                        >
                            <box-icon name="left-arrow-alt" color="#6b7280" size="18px"></box-icon>
                            Kembali
                        </button>
                    ) : (
                        <Link href={`/instructor/course/${slug}`}>
                            <button className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-semibold text-sm transition-all shadow-sm hover:shadow">
                                <box-icon name="left-arrow-alt" color="#6b7280" size="18px"></box-icon>
                                Kembali ke Detail Course
                            </button>
                        </Link>
                    )}
                </div>

            </div>
        </App>
    );
};

export default Activity;