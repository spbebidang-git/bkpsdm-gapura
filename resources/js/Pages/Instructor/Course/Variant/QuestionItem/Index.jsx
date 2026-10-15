import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";
import Swal from "sweetalert2";

const Index = ({ slug, activityId, questionId, questionItems, activity }) => {
    const handleSubmitDelete = (e, questionItemId) => {
        e.preventDefault();

        Swal.fire({
            title: "Apakah Anda yakin?",
            text: "Data soal ini akan dihapus secara permanen!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(
                    `/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/${questionItemId}`,
                    {
                        onSuccess: () => {
                            Swal.fire(
                                "Berhasil!",
                                "Soal berhasil dihapus.",
                                "success"
                            );
                        }
                    }
                );
            }
        });
    };

    return (
        <App>
            <div className="max-w-7xl mx-auto mb-8">
                {/* JUDUL HALAMAN */}
                <div className="text-center mb-8">
                    <h1 className="text-2xl md:text-3xl font-extrabold text-gray-800 mb-2">
                        Daftar Soal
                    </h1>
                </div>

                {/* DETAIL AKTIVITAS */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                    <div className="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                                <box-icon name='info-circle' color='var(--color-primary)' size='20px'></box-icon>
                            </div>
                            <h2 className="text-lg font-bold text-gray-800">Detail Kegiatan</h2>
                        </div>
                        <Link
                            href={route("instructor.course.activity.variant.index", [
                                slug,
                                activityId,
                            ])}
                            className="w-full sm:w-auto"
                        >
                            <button className="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-gray-200 text-gray-700 bg-white hover:bg-gray-50 font-semibold shadow-sm transition-all text-sm">
                                Kembali
                            </button>
                        </Link>
                    </div>

                    <div className="relative overflow-x-auto w-full">
                        <table className="w-full text-sm text-left text-gray-600">
                            <thead className="text-xs text-gray-500 uppercase bg-gray-50/80">
                                <tr>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Tipe</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Kegiatan</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Ulangi</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Judul Variasi</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Acak</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                <tr className="bg-white hover:bg-gray-50/50 transition-colors">
                                    <td className="px-6 py-4 text-center font-medium uppercase text-gray-700">
                                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                            {activity.type}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-center font-medium text-gray-800">
                                        {activity.title}
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                            ${activity.is_loop ? 'bg-amber-50 text-amber-700 border border-amber-200/60' : 'bg-gray-100 text-gray-600 border border-gray-200'}`}
                                        >
                                            {activity.is_loop ? "Ya" : "Tidak"}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        <span className="inline-block px-3 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg text-xs font-bold font-mono">
                                            {activity.variant}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                            ${activity.mix ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-gray-100 text-gray-600 border border-gray-200'}`}
                                        >
                                            {activity.mix ? "Ya" : "Tidak"}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* LIST PERTANYAAN */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                                <box-icon name='list-ol' color='var(--color-primary)' size='20px'></box-icon>
                            </div>
                            <h2 className="text-lg font-bold text-gray-800">Daftar Pertanyaan</h2>
                        </div>

                        <Link
                            href={`/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/create`}
                            className="inline-flex items-center justify-center w-full sm:w-auto px-5 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl shadow-sm hover:shadow font-semibold transition-all text-sm gap-2 whitespace-nowrap"
                        >
                            <box-icon name='plus' color='#ffffff' size='18px'></box-icon>
                            Tambah Pertanyaan
                        </Link>
                    </div>

                    <div className="relative overflow-x-auto w-full">
                        <table className="w-full text-sm text-left text-gray-600">
                            <thead className="text-xs text-gray-500 uppercase bg-gray-50/80">
                                <tr>
                                    <th className="px-4 py-4 font-semibold whitespace-nowrap text-center">#</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap min-w-[300px]">Pertanyaan</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap min-w-[320px]">Pilihan Jawaban</th>
                                    <th className="px-4 py-4 font-semibold whitespace-nowrap text-center">Skor</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center min-w-[150px]">Aksi</th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-gray-100">
                                {questionItems.length ? (
                                    questionItems.map((item, index) => (
                                        <tr className="bg-white hover:bg-gray-50/50 transition-colors group" key={index}>
                                            <td className="px-4 py-4 text-center text-gray-500 font-medium">
                                                {index + 1}
                                            </td>

                                            <td className="px-6 py-4 font-medium text-gray-800">
                                                <div
                                                    className="line-clamp-3 leading-relaxed text-sm prose prose-sm prose-p:my-0 prose-img:rounded-md max-w-none"
                                                >
                                                    {item.question}
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 align-middle">
                                                <div className="grid gap-2">
                                                    {item.options.length ? item.options.map((option, optionIndex) => (
                                                        <div
                                                            key={option.id}
                                                            className={`flex items-start gap-2 p-2 rounded-lg text-xs font-semibold ${
                                                                option.is_correct
                                                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                                    : 'text-gray-600 bg-gray-50 border border-gray-100'
                                                            }`}
                                                        >
                                                            <span className="shrink-0 uppercase">
                                                                {String.fromCharCode(65 + optionIndex)}.
                                                            </span>
                                                            <span>{option.text}</span>
                                                        </div>
                                                    )) : (
                                                        <span className="text-xs text-red-600">Belum ada pilihan jawaban.</span>
                                                    )}
                                                </div>
                                            </td>

                                            <td className="px-4 py-4 text-center font-bold text-gray-700">
                                                {item.score}
                                            </td>

                                            <td className="px-6 py-4">
                                                <div className="flex gap-2 justify-center">
                                                    <Link
                                                        href={`/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/${item.id}/edit`}
                                                    >
                                                        <button className="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg transition-colors border border-amber-200/50">
                                                            Edit
                                                        </button>
                                                    </Link>

                                                    <button
                                                        onClick={(e) => handleSubmitDelete(e, item.id)}
                                                        className="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors border border-red-200/50"
                                                    >
                                                        Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="100%" className="p-8 text-center text-gray-500">
                                            <div className="flex flex-col items-center justify-center">
                                                <box-icon name='file-blank' color='#cbd5e1' size='48px'></box-icon>
                                                <p className="mt-3 text-sm font-medium">Tidak ada soal dalam varian ini.</p>
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

export default Index;
