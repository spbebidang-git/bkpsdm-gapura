import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";

const Index = ({ slug, activity, variants }) => {
    function handleSubmitDelete(e, variantId) {
        e.preventDefault();
        router.delete(
            `/instructor/course/${slug}/activity/variant/${activity.activity_id}/${variantId}`
        );
    }

    return (
        <App>
            <div className="max-w-7xl mx-auto mb-8">
                <div className="text-center mb-8">
                    <h1 className="text-2xl md:text-3xl font-extrabold text-gray-800 mb-2">
                        Daftar Variasi Soal
                    </h1>
                    <p className="text-sm font-normal max-w-2xl mx-auto text-gray-500 leading-relaxed">
                        Fitur ini digunakan untuk membuat paket-paket variasi pertanyaan. Antar varian pertanyaan diharapkan memiliki jumlah soal yang sama.
                    </p>
                </div>

                {/* INFORMASI ACTIVITY */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                    <div className="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                                <box-icon name='info-circle' color='var(--color-primary)' size='20px'></box-icon>
                            </div>
                            <h2 className="text-lg font-bold text-gray-800">Detail Kegiatan</h2>
                        </div>
                        <Link href={`/instructor/course/${slug}`} className="w-full sm:w-auto">
                            <button className="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-gray-200 text-gray-700 bg-white hover:bg-gray-50 font-semibold shadow-sm transition-all text-sm">
                                Kembali
                            </button>
                        </Link>
                    </div>

                    <div className="relative overflow-x-auto w-full">
                        <table className="w-full text-sm text-left text-gray-600">
                            <thead className="text-xs text-gray-500 uppercase bg-gray-50/80">
                                <tr>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap min-w-[200px]">Judul</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap min-w-[300px]">Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                <tr className="bg-white hover:bg-gray-50/50 transition-colors">
                                    <td className="px-6 py-4 font-medium text-gray-800 align-top">
                                        {activity.title}
                                    </td>
                                    <td className="px-6 py-4 text-gray-600 leading-relaxed">
                                        {activity.description}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* LIST VARIANT */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                                <box-icon name='layer' color='var(--color-primary)' size='20px'></box-icon>
                            </div>
                            <h2 className="text-lg font-bold text-gray-800">Daftar Variasi</h2>
                        </div>
                        <Link
                            href={`/instructor/course/${slug}/activity/variant/${activity.activity_id}/create`}
                            className="inline-flex items-center justify-center w-full sm:w-auto px-5 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl shadow-sm hover:shadow font-semibold transition-all text-sm gap-2 whitespace-nowrap"
                        >
                            <box-icon name='plus' color='#ffffff' size='18px'></box-icon>
                            Tambah Varian Baru
                        </Link>
                    </div>

                    <div className="relative overflow-x-auto w-full">
                        <table className="w-full text-sm text-left text-gray-600">
                            <thead className="text-xs text-gray-500 uppercase bg-gray-50/80">
                                <tr>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Varian</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Kode</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Jml Soal</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center">Acak Soal</th>
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap text-center min-w-[250px]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {variants.length ? (
                                    variants.map((item, index) => (
                                        <tr key={index} className="bg-white hover:bg-gray-50/50 transition-colors group">
                                            <td className="px-6 py-4 text-center font-medium text-gray-800">
                                                {item.variant}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span className="inline-block px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold font-mono">
                                                    {item.code}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-center font-medium">
                                                {item.question_count}
                                            </td>
                                            <td className="px-6 py-4 text-center">
                                                <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                                    ${item.mix ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-gray-100 text-gray-600 border border-gray-200'}`}
                                                >
                                                    {item.mix ? "Ya" : "Tidak"}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex justify-center items-center gap-2 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                                    <Link
                                                        href={`/instructor/course/${slug}/activity/variant/${activity.activity_id}/${item.id}/question-item`}
                                                    >
                                                        <button className="inline-flex items-center justify-center px-4 py-1.5 text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg transition-colors border border-indigo-200/50">
                                                            Atur Soal
                                                        </button>
                                                    </Link>

                                                    <Link
                                                        href={`/instructor/course/${slug}/activity/variant/${activity.activity_id}/${item.id}/edit`}
                                                    >
                                                        <button className="inline-flex items-center justify-center px-4 py-1.5 text-xs font-semibold bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg transition-colors border border-amber-200/50">
                                                            Edit
                                                        </button>
                                                    </Link>

                                                    <button
                                                        onClick={(e) =>
                                                            handleSubmitDelete(e, item.id)
                                                        }
                                                        className="inline-flex items-center justify-center px-4 py-1.5 text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors border border-red-200/50"
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
                                                <box-icon name='folder-open' color='#cbd5e1' size='48px'></box-icon>
                                                <p className="mt-3 text-sm font-medium">Belum ada varian yang ditambahkan.</p>
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
