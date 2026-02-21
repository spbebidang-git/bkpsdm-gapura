import BreadcrumbLearn from "@/Components/BreadcrumbLearn";
import StarRating from "@/Components/StarRating";
import { Link, router, usePage } from "@inertiajs/react";
import axios from "axios";
import { useState } from "react";
import { toast } from "react-toastify";
import App from "../../Layouts/App";

const Show = ({ course, schedules, reviews = [], rating }) => {
    const { auth } = usePage().props;
    const [activeSchedules, setActiveSchedules] = useState([]);
    const [loadingScheduleId, setLoadingScheduleId] = useState(null);

    // 🔹 Logic Pagination Review
    const [reviewPage, setReviewPage] = useState(1);
    const reviewsPerPage = 25;
    const totalReviewPages = Math.ceil(reviews.length / reviewsPerPage);
    const indexOfLastReview = reviewPage * reviewsPerPage;
    const indexOfFirstReview = indexOfLastReview - reviewsPerPage;
    const currentReviews = reviews.slice(indexOfFirstReview, indexOfLastReview);

    // 🔹 Fungsi Smooth Scroll
    const scrollToSection = (id) => {
        const element = document.getElementById(id);
        if (element) {
            element.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    };

    // Aktivasi kelas — cek can_enter dari server sebelum POST
    const handleActivation = (e, item) => {
        e.preventDefault();

        if (!item.can_enter) {
            const jam = item.start_label ?? item.time_from ?? '';
            toast.warn(
                jam
                    ? `Kelas belum dimulai. Silakan masuk pukul ${jam}.`
                    : `Kelas belum dimulai sesuai jadwal.`,
                { toastId: `not-started-${item.id}` }
            );
            return;
        }

        setLoadingScheduleId(item.id);
        axios
            .post(`/course/${course.slug}/${item.id}`)
            .then(() => {
                setActiveSchedules((prev) => [...prev, item.id]);
                toast.success("Berhasil! Kelas telah diaktivasi.");
            })
            .catch((error) => {
                toast.error(error.response?.data?.message || "Aktivasi gagal.");
            })
            .finally(() => {
                setLoadingScheduleId(null);
            });
    };

    // Lihat kelas — cek can_enter lalu navigasi SPA dengan Inertia
    const handleLihatKelas = (e, item) => {
        e.preventDefault();

        if (!item.can_enter) {
            const jam = item.start_label ?? '';
            toast.warn(
                jam
                    ? `Kelas belum dimulai. Silakan masuk pukul ${jam}.`
                    : `Kelas belum dimulai sesuai jadwal.`,
                { toastId: `not-started-lihat-${item.id}` }
            );
            return;
        }

        router.visit('/my-course');
    };

    return (
        <App>
            <div className="max-w-7xl mx-auto p-4 md:p-8">

                {/* Hero Section & Breadcrumb Container */}
                <div className="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 p-6 md:p-8 mb-8">
                    {/* Breadcrumb */}
                    <div className="flex mb-8 w-full relative z-10">
                        <div className="inline-flex flex-wrap items-center gap-2 md:gap-4 bg-gray-50 py-2.5 px-6 rounded-full border border-gray-100/80">
                            <Link href="/" className="transition-transform hover:-translate-y-0.5 opacity-80 hover:opacity-100">
                                <BreadcrumbLearn title="Beranda" activeTab={false} icon="home" />
                            </Link>
                            <box-icon name="chevron-right" color="#cbd5e1" size="20px"></box-icon>
                            <Link href={route("course.index")} className="transition-transform hover:-translate-y-0.5 opacity-80 hover:opacity-100">
                                <BreadcrumbLearn title="Materi Pilihan" activeTab={false} icon="book-bookmark" />
                            </Link>
                            <box-icon name="chevron-right" color="#cbd5e1" size="20px"></box-icon>
                            <BreadcrumbLearn title={course.title} activeTab={true} icon="chalkboard" />
                        </div>
                    </div>

                    {/* Course Header */}
                    <div className="flex flex-col lg:flex-row gap-8">
                        {/* Thumbnail */}
                        <div className="w-full lg:w-1/3 shrink-0">
                            <div className="aspect-[4/3] w-full rounded-2xl overflow-hidden shadow-sm relative group bg-gray-100">
                                <div className="absolute top-3 right-3 bg-secondary/90 backdrop-blur-md text-white text-xs font-bold px-3 py-1.5 rounded-full z-10 uppercase tracking-wider shadow-sm">
                                    {course.employee_status}
                                </div>
                                <img src={course.thumbnail} alt={course.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-in-out" />
                            </div>
                        </div>

                        {/* Title & Info */}
                        <div className="w-full lg:w-2/3 flex flex-col pt-2">
                            <h1 className="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3 leading-tight capitalize">
                                {course.title}
                            </h1>
                            <div className="text-base text-gray-500 mb-4 flex items-center gap-1.5">
                                <span className="italic">Oleh</span>
                                <span className="font-semibold text-gray-800">{course.instructor}</span>
                            </div>

                            <div className="flex items-center gap-3 mb-6 bg-amber-50/50 w-max px-4 py-2 rounded-xl border border-amber-100/50">
                                <StarRating value={rating.star} />
                                <div className="flex items-center gap-1.5">
                                    <span className="font-extrabold text-amber-600 text-lg">{rating.value}</span>
                                    <span className="text-amber-600/70 text-sm font-medium">({rating.total} Ulasan)</span>
                                </div>
                            </div>

                            <p className="text-gray-600 leading-relaxed text-lg mb-8 max-w-3xl">
                                {course.short_description}
                            </p>

                            {/* 🔹 TOMBOL NAVIGASI */}
                            <div className="mt-auto flex flex-wrap gap-3">
                                {course.type === 'schedule' && (
                                    <button onClick={() => scrollToSection('section-schedule')} className="px-5 py-2.5 bg-primary/10 hover:bg-primary text-primary hover:text-white font-semibold rounded-xl transition-all duration-300 flex items-center gap-2 border border-primary/20 hover:shadow-lg hover:-translate-y-0.5">
                                        <box-icon name='calendar' size="20px" color="currentColor"></box-icon>
                                        Jadwal
                                    </button>
                                )}

                                {course.teaser_video && (
                                    <button onClick={() => scrollToSection('section-teaser')} className="px-5 py-2.5 bg-primary/10 hover:bg-primary text-primary hover:text-white font-semibold rounded-xl transition-all duration-300 flex items-center gap-2 border border-primary/20 hover:shadow-lg hover:-translate-y-0.5">
                                        <box-icon name='play-circle' size="20px" color="currentColor"></box-icon>
                                        Teaser
                                    </button>
                                )}

                                <button onClick={() => scrollToSection('section-description')} className="px-5 py-2.5 bg-primary/10 hover:bg-primary text-primary hover:text-white font-semibold rounded-xl transition-all duration-300 flex items-center gap-2 border border-primary/20 hover:shadow-lg hover:-translate-y-0.5">
                                    <box-icon name='info-circle' size="20px" color="currentColor"></box-icon>
                                    Deskripsi
                                </button>

                                <button onClick={() => scrollToSection('section-review')} className="px-5 py-2.5 bg-primary/10 hover:bg-primary text-primary hover:text-white font-semibold rounded-xl transition-all duration-300 flex items-center gap-2 border border-primary/20 hover:shadow-lg hover:-translate-y-0.5">
                                    <box-icon name='message-square-detail' size="20px" color="currentColor"></box-icon>
                                    Ulasan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* 🔹 Section Jadwal Kegiatan */}
                <div id="section-schedule" className="bg-white p-6 md:p-8 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-8 scroll-mt-24">
                    <div className="flex items-center gap-3 mb-8">
                        <div className="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                            <box-icon name="calendar" size="24px" color="currentColor"></box-icon>
                        </div>
                        <h2 className="text-2xl font-bold text-gray-900">Jadwal Kegiatan</h2>
                    </div>

                    <div className="grid grid-cols-1 gap-5">
                        {schedules.map((item, index) => (
                            <div key={index} className="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-5 md:p-6 group">
                                <div className="flex flex-col md:flex-row gap-5 md:items-center justify-between mb-6 pb-5 border-b border-gray-50">
                                    <h3 className="font-bold text-lg md:text-xl text-gray-800 leading-snug break-words max-w-2xl group-hover:text-primary transition-colors">
                                        {course.title}
                                    </h3>
                                    <div className="shrink-0">
                                        {activeSchedules.includes(item.id) || item.is_active ? (
                                            <button
                                                onClick={(e) => handleLihatKelas(e, item)}
                                                className="w-full md:w-auto px-6 py-2.5 bg-primary hover:bg-teal-600 text-white font-bold rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5"
                                            >
                                                Lihat Kelas
                                            </button>
                                        ) : (
                                            <form onSubmit={(e) => handleActivation(e, item)}>
                                                <button
                                                    className="w-full md:w-auto px-6 py-2.5 bg-primary hover:bg-teal-600 text-white font-bold rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 flex items-center justify-center gap-2 disabled:opacity-50 disabled:hover:translate-y-0"
                                                    type="submit"
                                                    disabled={loadingScheduleId === item.id}
                                                >
                                                    {loadingScheduleId === item.id ? (
                                                        <>
                                                            <box-icon name='loader-alt' animation='spin' color='white' size="20px"></box-icon>
                                                            Memproses...
                                                        </>
                                                    ) : "Aktivasi Kelas"}
                                                </button>
                                            </form>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-gray-600 text-sm">
                                    <div className="flex items-center gap-3 bg-gray-50/50 p-3 rounded-xl border border-gray-100/50">
                                        <div className="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                                            <box-icon name='calendar-event' size="18px" color="currentColor"></box-icon>
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="text-xs text-gray-400 font-medium uppercase tracking-wider">Tanggal</span>
                                            <span className="font-semibold text-gray-700">{item.date}</span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 bg-gray-50/50 p-3 rounded-xl border border-gray-100/50">
                                        <div className="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center shrink-0">
                                            <box-icon name='time' size="18px" color="currentColor"></box-icon>
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="text-xs text-gray-400 font-medium uppercase tracking-wider">Waktu</span>
                                            <span className="font-semibold text-gray-700">{item.time}</span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 bg-gray-50/50 p-3 rounded-xl border border-gray-100/50">
                                        <div className="w-8 h-8 rounded-lg bg-purple-50 text-purple-500 flex items-center justify-center shrink-0">
                                            <box-icon name='user' size="18px" color="currentColor"></box-icon>
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="text-xs text-gray-400 font-medium uppercase tracking-wider">Instruktur</span>
                                            <span className="font-semibold text-gray-700">{course.instructor}</span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 bg-gray-50/50 p-3 rounded-xl border border-gray-100/50">
                                        <div className="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center shrink-0">
                                            <box-icon name='group' size="18px" color="currentColor"></box-icon>
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="text-xs text-gray-400 font-medium uppercase tracking-wider">Kuota</span>
                                            <span className="font-semibold text-gray-700">{course.quota} Peserta</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}

                        {schedules.length === 0 && (
                            <div className="text-center py-10 bg-gray-50 rounded-2xl border border-gray-100">
                                <box-icon name='calendar-x' size="48px" color="#cbd5e1" className="mb-3"></box-icon>
                                <p className="text-gray-500 font-medium">Belum ada jadwal kegiatan tersedia</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* 🔹 Section Teaser Video */}
                <div id="section-teaser" className="bg-white p-6 md:p-8 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-8 scroll-mt-24">
                    <div className="flex items-center gap-3 mb-8">
                        <div className="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-500">
                            <box-icon name="video" size="24px" color="currentColor"></box-icon>
                        </div>
                        <h2 className="text-2xl font-bold text-gray-900">Teaser Video</h2>
                    </div>
                    {course.teaser_video ? (
                        <div className="w-full aspect-video xl:h-[512px] bg-gray-900 rounded-2xl shadow-md overflow-hidden border border-gray-200">
                            <iframe src={course.teaser_video} width="100%" height="100%" title="Teaser Video" allowFullScreen className="border-0"></iframe>
                        </div>
                    ) : (
                        <div className="flex flex-col justify-center items-center py-16 bg-gray-50 rounded-2xl border border-gray-100 text-center px-4">
                            <div className="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                                <box-icon name='video-off' size="32px" color="#94a3b8"></box-icon>
                            </div>
                            <h3 className="text-lg font-bold text-gray-700 mb-1">Video Tidak Tersedia</h3>
                            <p className="text-gray-500 text-sm">Video teaser belum ditambahkan untuk kelas ini.</p>
                        </div>
                    )}
                </div>

                {/* 🔹 Section Description */}
                <div id="section-description" className="bg-white p-6 md:p-8 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-8 scroll-mt-24">
                    <div className="flex items-center gap-3 mb-6">
                        <div className="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-600">
                            <box-icon name="info-circle" size="24px" color="currentColor"></box-icon>
                        </div>
                        <h2 className="text-2xl font-bold text-gray-900">Tentang Kelas Ini</h2>
                    </div>
                    <div className="text-gray-700 leading-relaxed text-lg bg-gray-50/50 p-6 rounded-2xl border border-gray-100 prose prose-teal max-w-none">
                        {course.description}
                    </div>
                </div>

                {/* 🔹 Section Ulasan Peserta */}
                <div id="section-review" className="bg-white p-6 md:p-8 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 mb-8 scroll-mt-24">
                    <div className="flex items-center gap-3 mb-8">
                        <div className="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-500">
                            <box-icon name="message-square-detail" size="24px" color="currentColor"></box-icon>
                        </div>
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900 leading-tight">Ulasan Peserta</h2>
                            <p className="text-gray-500 text-sm mt-1">Apa kata mereka yang telah mengikuti kelas ini</p>
                        </div>
                    </div>

                    {reviews.length > 0 ? (
                        <div className="space-y-4">
                            {currentReviews.map((item, index) => (
                                <div key={index} className="bg-gray-50 hover:bg-white p-5 rounded-2xl border border-gray-100 transition-colors duration-300">
                                    <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between mb-3 border-b border-gray-200/50 pb-3">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-lg">
                                                {item.name.charAt(0)}
                                            </div>
                                            <div>
                                                <div className="font-bold text-gray-800">{item.name}</div>
                                                <div className="text-xs text-gray-400 font-medium flex items-center gap-1 mt-0.5">
                                                    <box-icon name='time' size="12px" color="currentColor"></box-icon>
                                                    {item.created_at}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="bg-white px-2 py-1 rounded-lg border border-gray-100 shadow-sm">
                                            <StarRating value={item.star} />
                                        </div>
                                    </div>
                                    <div className="text-gray-600 leading-relaxed italic text-[15px]">"{item.description}"</div>
                                </div>
                            ))}

                            {totalReviewPages > 1 && (
                                <div className="mt-8 flex justify-center gap-2">
                                    <button
                                        onClick={() => setReviewPage(p => Math.max(p - 1, 1))}
                                        disabled={reviewPage === 1}
                                        className="px-4 py-2 border border-gray-200 text-gray-600 bg-white rounded-xl disabled:opacity-50 disabled:bg-gray-50 hover:bg-gray-50 hover:text-gray-900 transition-all font-medium text-sm flex items-center"
                                    >
                                        <box-icon name='chevron-left' size="18px" color="currentColor"></box-icon>
                                    </button>

                                    <div className="flex bg-gray-50 rounded-xl border border-gray-200 p-1">
                                        {Array.from({ length: totalReviewPages }, (_, i) => {
                                            // Tampilkan maksimal 5 tombol halaman
                                            if (totalReviewPages > 5 && i > 0 && i < totalReviewPages - 1 && Math.abs(reviewPage - 1 - i) > 1) {
                                                if (i === 1 || i === totalReviewPages - 2) return <span key={i} className="px-3 py-1.5 text-gray-400">...</span>;
                                                return null;
                                            }
                                            return (
                                                <button
                                                    key={i}
                                                    onClick={() => setReviewPage(i + 1)}
                                                    className={`px-4 py-1.5 rounded-lg text-sm font-semibold transition-all ${reviewPage === i + 1 ? "bg-white text-primary shadow-sm" : "text-gray-500 hover:text-gray-700 hover:bg-gray-100/50"}`}
                                                >
                                                    {i + 1}
                                                </button>
                                            )
                                        })}
                                    </div>

                                    <button
                                        onClick={() => setReviewPage(p => Math.min(p + 1, totalReviewPages))}
                                        disabled={reviewPage === totalReviewPages}
                                        className="px-4 py-2 border border-gray-200 text-gray-600 bg-white rounded-xl disabled:opacity-50 disabled:bg-gray-50 hover:bg-gray-50 hover:text-gray-900 transition-all font-medium text-sm flex items-center"
                                    >
                                        <box-icon name='chevron-right' size="18px" color="currentColor"></box-icon>
                                    </button>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="flex flex-col justify-center items-center py-12 bg-gray-50 rounded-2xl border border-gray-100 text-center px-4">
                            <box-icon name='message-square-x' size="48px" color="#cbd5e1" className="mb-3"></box-icon>
                            <h3 className="text-lg font-bold text-gray-700 mb-1">Belum Ada Ulasan</h3>
                            <p className="text-gray-500 text-sm">Jadilah yang pertama menyelesaikan kursus ini dan memberikan ulasan!</p>
                        </div>
                    )}
                </div>
            </div>
        </App>
    );
};

export default Show;