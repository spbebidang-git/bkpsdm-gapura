import StarRating from "@/Components/StarRating";
import { Link, usePage } from "@inertiajs/react";
import { useState } from "react";
import { toast, ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import App from "../../Layouts/App";
import axios from "axios";

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

    const handleActivation = (e, scheduleId) => {
        e.preventDefault();
        setLoadingScheduleId(scheduleId);

        axios
            .post(`/course/${course.slug}/${scheduleId}`)
            .then((res) => {
                setActiveSchedules((prev) => [...prev, scheduleId]);
                toast.success("Berhasil!");
            })
            .catch((error) => {
                toast.error(error.response?.data?.message || "Aktivasi gagal.");
            })
            .finally(() => {
                setLoadingScheduleId(null);
            });
    };

    return (
        <App>
            <div className="max-w-7xl mx-auto p-6">
                <ToastContainer />

                {/* Breadcrumb */}
                <nav className="flex mb-4" aria-label="Breadcrumb">
                    <ol className="inline-flex items-center space-x-1 md:space-x-2">
                        <li className="inline-flex items-center">
                            <Link href="/" className="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                Beranda
                            </Link>
                        </li>
                        <li>
                            <div className="flex items-center">
                                <svg className="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="m1 9 4-4-4-4" /></svg>
                                <span className="ms-1 text-sm font-medium text-gray-700">Materi Pilihan</span>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div className="flex items-center">
                                <svg className="w-3 h-3 text-gray-400 mx-1" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="m1 9 4-4-4-4" /></svg>
                                <span className="ms-1 text-sm font-medium text-gray-500 capitalize">{course.title}</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                {/* Course Header */}
                <div className="grid grid-cols-3 gap-5 mb-5">
                    <div className="col-span-3 xl:col-span-1">
                        <div className="h-48 w-full rounded-lg overflow-hidden shadow relative">
                            <div className="absolute top-2.5 right-2.5 bg-secondary text-white text-[10px] font-bold px-2 rounded">{course.employee_status}</div>
                            <img src={course.thumbnail} alt={course.title} className="w-full h-full object-cover" />
                        </div>
                    </div>
                    <div className="col-span-3 xl:col-span-2">
                        <div className="text-xl font-bold text-gray-800 mb-0.5 capitalize">{course.title}</div>
                        <div className="text-sm italic mb-1 text-gray-700">oleh <span className="not-italic font-bold">{course.instructor}</span></div>
                        <div className="text-base flex gap-1 items-center mb-1">
                            <StarRating value={rating.star} />
                            <div className="font-extrabold">{rating.value}</div>
                            <div className="font-medium">({rating.total} Rating)</div>
                        </div>
                        <div className="mb-2.5 text-gray-700 text-sm">{course.short_description}</div>

                        {/* 🔹 TOMBOL NAVIGASI DENGAN LOGIKA HIDE/SHOW */}
                        <div className="flex flex-col xl:flex-row max-w-full w-full xl:max-w-xl gap-3">
                            {/* Tombol Schedule: Hide jika unschedule */}
                            {course.type === 'schedule' && (
                                <button onClick={() => scrollToSection('section-schedule')} className="button-primary">Schedule</button>
                            )}

                            {/* Tombol Teaser: Hide jika kosong */}
                            {course.teaser_video && (
                                <button onClick={() => scrollToSection('section-teaser')} className="button-primary">Teaser Video</button>
                            )}

                            <button onClick={() => scrollToSection('section-description')} className="button-primary">Description</button>
                            <button onClick={() => scrollToSection('section-review')} className="button-primary">Review</button>
                        </div>
                    </div>
                </div>

                {/* 🔹 Section Jadwal Kegiatan (Logika Tombol di Dalam Tetap Seperti Semula) */}
                <div id="section-schedule" className="bg-white p-6 rounded-xl shadow mb-5 scroll-mt-20">
                    <div className="text-lg font-bold mb-5">Jadwal Kegiatan</div>
                    <div className="ring-8 ring-teal-200 rounded-xl overflow-hidden shadow">
                        {schedules.map((item, index) => (
                            <div key={index} className="shadow overflow-hidden bg-white">
                                <div className="px-5 border-b-2 pb-4 font-medium xl:text-lg flex flex-col xl:flex-row gap-3 xl:justify-between items-center my-4">
                                    {course.title}
                                    <div>
                                        {activeSchedules.includes(item.id) || item.is_active ? (
                                            <Link href={`/my-course`} className="button-primary inline-block">Lihat Kelas</Link>
                                        ) : (
                                            <form onSubmit={(e) => handleActivation(e, item.id)}>
                                                <button className="button-primary flex items-center gap-2 disabled:opacity-50" type="submit" disabled={loadingScheduleId === item.id}>
                                                    {loadingScheduleId === item.id ? "Memproses..." : "Aktivasi Kelas"}
                                                </button>
                                            </form>
                                        )}
                                    </div>
                                </div>
                                <div className="mb-4 px-5 grid grid-cols-4 gap-5 text-gray-600 text-sm">
                                    <div className="col-span-4 xl:col-span-1 flex items-center gap-1.5 font-medium">📅 {item.date}</div>
                                    <div className="col-span-4 xl:col-span-1 flex items-center gap-1.5 font-medium">🕒 {item.time}</div>
                                    <div className="col-span-4 xl:col-span-1 flex items-center gap-1.5 font-medium">👤 {course.instructor}</div>
                                    <div className="col-span-4 xl:col-span-1 flex items-center gap-1.5 font-medium">✅ {course.quota}</div>
                                </div>
                                <div className="bg-gray-200 p-2"></div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* 🔹 Section Teaser Video (Hanya render konten jika ada, tapi ID tetap ada agar scroll tidak error) */}
                <div id="section-teaser" className="bg-white p-6 rounded-xl shadow mb-5 scroll-mt-20">
                    <div className="text-lg font-bold mb-3">Teaser Video</div>
                    {course.teaser_video ? (
                        <div className="flex justify-center">
                            <div className="w-full aspect-video xl:h-[512px] rounded-xl shadow overflow-hidden">
                                <iframe src={course.teaser_video} width="100%" height="100%" title="Teaser Video" allowFullScreen></iframe>
                            </div>
                        </div>
                    ) : (
                        <div className="text-center py-6 text-gray-400 italic">Video teaser tidak tersedia untuk kelas ini.</div>
                    )}
                </div>

                {/* 🔹 Section Description */}
                <div id="section-description" className="bg-white p-6 rounded-xl shadow mb-5 scroll-mt-20">
                    <div className="text-lg font-bold">Tentang Kelas Ini</div>
                    <div className="text-gray-700 py-2.5">{course.description}</div>
                </div>

                {/* 🔹 Section Ulasan Peserta */}
                <div id="section-review" className="bg-white p-6 rounded-xl shadow mb-5 scroll-mt-20">
                    <div className="text-lg font-bold mb-5">Ulasan Peserta</div>
                    {reviews.length > 0 ? (
                        <>
                            {currentReviews.map((item, index) => (
                                <div key={index} className="text-gray-700 pb-4 mb-4 border-b last:border-none">
                                    <div className="flex flex-col xl:flex-row gap-1.5 items-start xl:items-center mb-1">
                                        <StarRating value={item.star} />
                                        <div className="text-gray-700 font-bold">{item.name} <span className="font-normal text-gray-500 text-sm italic ml-2">{item.created_at}</span></div>
                                    </div>
                                    <div className="text-gray-600">{item.description}</div>
                                </div>
                            ))}
                            {totalReviewPages > 1 && (
                                <div className="mt-4 flex justify-center gap-2">
                                    <button onClick={() => setReviewPage(p => Math.max(p - 1, 1))} disabled={reviewPage === 1} className="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
                                    {Array.from({ length: totalReviewPages }, (_, i) => (
                                        <button key={i} onClick={() => setReviewPage(i + 1)} className={`px-3 py-1 border rounded ${reviewPage === i + 1 ? "bg-teal-500 text-white" : ""}`}>{i + 1}</button>
                                    ))}
                                    <button onClick={() => setReviewPage(p => Math.min(p + 1, totalReviewPages))} disabled={reviewPage === totalReviewPages} className="px-3 py-1 border rounded disabled:opacity-50">Next</button>
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="text-center border-2 p-5 text-gray-600 rounded-xl">Ulasan belum tersedia.</div>
                    )}
                </div>
            </div>
        </App>
    );
};

export default Show;