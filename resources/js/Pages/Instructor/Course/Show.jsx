import BreadcrumbLearn from "@/Components/BreadcrumbLearn";
import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";
import { useEffect, useState } from "react";
import Swal from "sweetalert2";

const Show = ({ course, activities }) => {
    const [dataActivities, setDataActivities] = useState([]);

    useEffect(() => {
        setDataActivities(activities);
    }, []);

    const handleEndMeeting = (e, activityId) => {
        e.preventDefault();

        Swal.fire({
            title: "Apakah Anda yakin?",
            text: "Pertemuan ini akan diakhiri secara permanen!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, akhiri!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                router.post(
                    route("instructor.course.activity.meeting.end-meeting", [
                        course.slug,
                        activityId,
                    ])
                );

                setDataActivities((prevData) =>
                    prevData.map((activity) =>
                        activity.id === activityId
                            ? { ...activity, end_meeting: 1 }
                            : activity
                    )
                );
            }
        });
    };

    const handleSubmitDelete = (e, activityId) => {
        e.preventDefault();

        Swal.fire({
            title: "Apakah Anda yakin?",
            text: "Aktivitas ini akan dihapus secara permanen!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(
                    `/instructor/course/${course.slug}/activity/${activityId}`,
                    {
                        onSuccess: () => {
                            router.get(`/instructor/course/${course.slug}`);
                            Swal.fire(
                                "Berhasil!",
                                "Aktivitas berhasil dihapus.",
                                "success"
                            );
                        },
                    }
                );
            }
        });
    };

    const handleLock = () => {
        Swal.fire({
            title: "Kunci Kegiatan?",
            text: "Dengan melakukan kunci kegiatan, berarti Anda menyetujui bahwa semua detil Pelatihan dan Kegiatan yang berada di dalamnya TIDAK dapat dilakukan pemutahiran lagi. Pilih 'Batal' jika ingin memeriksa kembali detil Pelatihan dan Kegiatan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Ya, Kunci Kegiatan",
            cancelButtonText: "Batal",
            customClass: {
                confirmButton: 'px-6 py-2.5 rounded-xl font-bold text-white transition-all shadow-sm',
                cancelButton: 'px-6 py-2.5 rounded-xl font-bold text-white transition-all shadow-sm'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                router.post(route("instructor.course.lock", course.slug), {}, {
                    preserveScroll: true
                });
            }
        });
    };

    return (
        <App>
            <div className="max-w-7xl mx-auto mb-8">
                {/* Breadcrumb */}
                <div className="flex justify-center mb-8 w-full relative z-10">
                    <div className="inline-flex flex-wrap items-center gap-2 md:gap-4 bg-white/90 backdrop-blur-md py-3 px-8 rounded-full shadow-[0_4px_25px_-4px_rgba(0,0,0,0.06)] border border-gray-100/80 transition-all hover:shadow-[0_4px_30px_-4px_rgba(0,0,0,0.1)]">
                        <Link href="/instructor" className="transition-transform hover:-translate-y-0.5 opacity-80 hover:opacity-100">
                            <BreadcrumbLearn title="Dashboard" activeTab={false} icon="grid-alt" size="lg" />
                        </Link>
                        <box-icon name="chevron-right" color="#cbd5e1" size="22px"></box-icon>
                        <BreadcrumbLearn title="Detail Course" activeTab={true} icon="book-bookmark" size="lg" />
                    </div>
                </div>

                {/* Header Course */}
                <div className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                        <div className="col-span-1">
                            <div className="w-full aspect-video md:h-full md:aspect-auto rounded-2xl overflow-hidden shadow-sm relative group">
                                <img
                                    src={course.thumbnail}
                                    alt={course.title}
                                    className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </div>
                        </div>

                        <div className="col-span-1 md:col-span-2 flex flex-col justify-center">
                            <div className="mb-4">
                                <h1 className="text-2xl md:text-3xl font-extrabold text-gray-800 leading-tight mb-2">
                                    {course.title}
                                </h1>
                                <p className="text-sm text-gray-500 flex items-center gap-2">
                                    <box-icon name='user-circle' color='#9ca3af' size='18px'></box-icon>
                                    Instruktur: <span className="font-semibold text-gray-700">{course.instructor}</span>
                                </p>
                            </div>

                            <div className="text-gray-600 leading-relaxed mb-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                {course.short_description}
                            </div>

                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-auto">
                                <div className="flex flex-wrap gap-4 sm:gap-6">
                                    <div className="flex items-center gap-3 bg-blue-50/50 px-4 py-2.5 rounded-xl border border-blue-100/50">
                                        <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                            <box-icon name='group' color='currentColor'></box-icon>
                                        </div>
                                        <div>
                                            <p className="text-xs text-blue-600/80 font-semibold uppercase tracking-wider mb-0.5">Peserta</p>
                                            <p className="text-lg font-bold text-blue-900 leading-none">{course.total_participant}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 bg-green-50/50 px-4 py-2.5 rounded-xl border border-green-100/50">
                                        <div className="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                            <box-icon name='check-circle' color='currentColor'></box-icon>
                                        </div>
                                        <div>
                                            <p className="text-xs text-green-600/80 font-semibold uppercase tracking-wider mb-0.5">Lulus</p>
                                            <p className="text-lg font-bold text-green-900 leading-none">{course.total_passed}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="text-right bg-gradient-to-br from-primary/10 to-primary/5 px-6 py-4 rounded-2xl border border-primary/10 w-full sm:w-auto flex flex-row items-center justify-between sm:flex-col sm:items-end shadow-sm">
                                    <h4 className="text-xs font-bold text-primary/80 uppercase tracking-wider mb-1">
                                        Efektivitas
                                    </h4>
                                    <div className="flex items-baseline gap-1 text-primary">
                                        <span className="text-4xl font-extrabold tracking-tight">{course.efektifitas}</span>
                                        <span className="text-xl font-bold">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Activities */}
                <div className="bg-white border border-gray-100 overflow-hidden rounded-2xl shadow-sm">
                    <div className="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-3">
                                <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <box-icon name='list-ul' color='var(--color-primary)' size='20px'></box-icon>
                                </div>
                                <h2 className="text-xl font-bold text-gray-800">Daftar Aktivitas</h2>
                            </div>

                            {!course.is_locked ? (
                                <button
                                    onClick={handleLock}
                                    className={`inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg font-bold transition-all shadow-sm text-xs sm:text-sm bg-red-50 text-red-700 hover:bg-red-100 border border-red-200`}
                                >
                                    <box-icon name='lock-alt' color='#b91c1c' size='16px'></box-icon>
                                    Kunci Kegiatan
                                </button>
                            ) : (
                                <button
                                    disabled
                                    className={`inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg font-bold transition-all shadow-sm text-xs sm:text-sm bg-gray-100 text-gray-500 border border-gray-200 cursor-not-allowed`}
                                >
                                    <box-icon name='lock' color='#6b7280' size='16px'></box-icon>
                                    Sudah Terkunci
                                </button>
                            )}
                        </div>

                        {!course.is_locked && (
                            <Link
                                href={`/instructor/course/${course.slug}/activity`}
                                className="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl shadow-sm hover:shadow text-sm font-semibold transition-all w-full sm:w-auto whitespace-nowrap"
                            >
                                <box-icon name='plus' color='#ffffff' size='18px'></box-icon>
                                Tambah Aktivitas
                            </Link>
                        )}
                    </div>

                    <div className="relative overflow-x-auto w-full">
                        <table className="w-full text-sm text-left text-gray-600">
                            <thead className="text-xs text-gray-500 uppercase bg-gray-50/80">
                                <tr>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">#</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap min-w-[150px]">Tipe</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap min-w-[250px]">Judul</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Nilai</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center min-w-[300px]">Aksi</th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-gray-100">
                                {dataActivities.length > 0 ? (
                                    dataActivities.map((item, index) => (
                                        <tr key={index} className="bg-white hover:bg-gray-50/50 transition-colors group">
                                            <td className="px-6 py-4 text-center text-gray-500 font-medium">{index + 1}</td>

                                            <td className="px-6 py-4 capitalize whitespace-nowrap">
                                                <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                                    ${item.type === 'pre-test' || item.type === 'post-test' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' :
                                                        item.type === 'meeting' ? 'bg-orange-50 text-orange-700 border border-orange-100' :
                                                            item.type === 'task' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' :
                                                                'bg-gray-100 text-gray-700 border border-gray-200'}
                                                `}>
                                                    {item.type === 'pre-test'
                                                        ? 'Pre-Test / Kuis'
                                                        : item.type === 'post-test'
                                                            ? 'Post-Test'
                                                            : item.type === 'h5p'
                                                                ? 'H5P Interaktif'
                                                                : item.type === 'scorm'
                                                                    ? 'SCORM'
                                                                    : item.type}
                                                </span>
                                            </td>

                                            <td className="px-6 py-4 font-medium text-gray-800">
                                                <div className="line-clamp-2 leading-relaxed">
                                                    {item.title}
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 text-center">
                                                <span className="inline-block px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold font-mono">
                                                    {item.value || '-'}
                                                </span>
                                            </td>

                                            <td className="px-6 py-4">
                                                <div className="flex justify-center items-center gap-2 flex-wrap sm:flex-nowrap opacity-100 transition-opacity">

                                                    {/* Pre-test / Post-test */}
                                                    {(item.type === "pre-test" || item.type === "post-test") && (
                                                        <div className="flex gap-2">
                                                            {!course.is_locked && !item.has_imported && (
                                                                <Link
                                                                    href={route(
                                                                        "instructor.course.activity.import-question.index",
                                                                        {
                                                                            course: course.slug,
                                                                            activity: item.id,
                                                                        }
                                                                    )}
                                                                >
                                                                    <button className="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg transition-colors border border-emerald-200/50">
                                                                        Import
                                                                    </button>
                                                                </Link>
                                                            )}

                                                            {!course.is_locked && (
                                                                <Link
                                                                    href={`/instructor/course/${course.slug}/activity/variant/${item.id}`}
                                                                >
                                                                    <button className="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg transition-colors border border-indigo-200/50">
                                                                        Variasi
                                                                    </button>
                                                                </Link>
                                                            )}
                                                        </div>
                                                    )}

                                                    {/* Meeting */}
                                                    {!course.is_locked && item.type === "meeting" && item.end_meeting === 0 && (
                                                        <button
                                                            onClick={(e) => handleEndMeeting(e, item.id)}
                                                            className="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold bg-orange-50 hover:bg-orange-100 text-orange-600 rounded-lg transition-colors border border-orange-200/50"
                                                        >
                                                            Akhiri Sesi
                                                        </button>
                                                    )}

                                                    {/* Task Result */}
                                                    {item.type === "task" && (
                                                        <Link
                                                            href={route("instructor.course.activity.result", {
                                                                course: course.slug,
                                                                activityId: item.id,
                                                            })}
                                                        >
                                                            <button className="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-colors border border-blue-200/50">
                                                                Hasil Tugas
                                                            </button>
                                                        </Link>
                                                    )}

                                                    {/* Edit */}
                                                    {!course.is_locked && (
                                                        <Link
                                                            href={`/instructor/course/${course.slug}/activity/${item.type}/${item.id}/edit`}
                                                        >
                                                            <button
                                                                className={`inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors border ${
                                                                    item.type === "h5p" && !item.has_package
                                                                        ? "bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200/50"
                                                                        : "bg-amber-50 hover:bg-amber-100 text-amber-600 border-amber-200/50"
                                                                }`}
                                                            >
                                                                {item.type === "h5p" && !item.has_package
                                                                    ? "Upload H5P"
                                                                    : "Edit"}
                                                            </button>
                                                        </Link>
                                                    )}

                                                    {/* Delete */}
                                                    {!course.is_locked && (
                                                        <button
                                                            onClick={(e) => handleSubmitDelete(e, item.id)}
                                                            className="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors border border-red-200/50"
                                                        >
                                                            Hapus
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="100%" className="p-8 text-center text-gray-500">
                                            <div className="flex flex-col items-center justify-center">
                                                <box-icon name='task' color='#cbd5e1' size='48px'></box-icon>
                                                <p className="mt-3 text-sm font-medium">Belum ada aktivitas yang ditambahkan.</p>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </App>
    );
};

export default Show;
