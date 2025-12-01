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
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">
                Daftar Variasi Soal
                <p className="text-sm font-normal max-w-lg mx-auto text-gray-500 mt-2">
                    Fitur ini digunakan untuk membuat paket - paket variasi pertanyaan. Antar varian pertanyaan diharapkan memiliki jumlah soal yang sama."
                </p>
            </div>

            {/* INFORMASI ACTIVITY */}
            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow mb-8">
                <div className="p-5 text-lg font-bold flex justify-between items-center">
                    Kegiatan
                    <Link href={`/instructor/course/${slug}`}>
                        <button className="button-secondary">Kembali</button>
                    </Link>
                </div>

                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left text-gray-500">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr className="sticky top-0 z-10">
                                <th className="px-6 py-3 text-center whitespace-nowrap">
                                    Judul
                                </th>
                                <th className="px-6 py-3 text-center whitespace-nowrap">
                                    Deskripsi
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr className="bg-white border-b">
                                <td className="px-6 py-4 text-center">
                                    {activity.title}
                                </td>
                                <td className="px-6 py-4 text-center">
                                    {activity.description}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {/* LIST VARIANT */}
            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow">
                <div className="p-5 text-lg font-bold flex justify-between items-center">
                    Daftar Variasi
                    <Link
                        href={`/instructor/course/${slug}/activity/variant/${activity.activity_id}/create`}
                        className="px-4 py-2 bg-purple-500 text-white rounded-xl shadow text-sm"
                    >
                        Tambah Variant Baru
                    </Link>
                </div>

                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left text-gray-500">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr className="sticky top-0 z-10">
                                <th className="px-6 py-3 text-center whitespace-nowrap">
                                    Variant
                                </th>
                                <th className="px-6 py-3 text-center whitespace-nowrap">
                                    Kode
                                </th>
                                <th className="px-6 py-3 text-center whitespace-nowrap">
                                    Jumlah Soal
                                </th>
                                <th className="px-6 py-3 text-center whitespace-nowrap">
                                    Acak Soal
                                </th>
                                <th className="px-6 py-3 text-center whitespace-nowrap">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            {variants.length ? (
                                variants.map((item, index) => (
                                    <tr key={index} className="bg-white border-b">
                                        <td className="px-6 py-4 text-center">
                                            {item.variant}
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            {item.code}
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            {item.question_count}
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            {item.mix ? "Ya" : "Tidak"}
                                        </td>
                                        <td>
                                            <div className="flex justify-center items-center gap-2">
                                                <Link
                                                    href={`/instructor/course/${slug}/activity/variant/${activity.activity_id}/${item.id}/question-item`}
                                                >
                                                    <button className="w-32 h-7 text-sm font-medium bg-primary text-white rounded-full shadow">
                                                        Atur Soal
                                                    </button>
                                                </Link>

                                                <Link
                                                    href={`/instructor/course/${slug}/activity/variant/${activity.activity_id}/${item.id}/edit`}
                                                >
                                                    <button className="button-warning">
                                                        Edit
                                                    </button>
                                                </Link>

                                                <button
                                                    onClick={(e) =>
                                                        handleSubmitDelete(e, item.id)
                                                    }
                                                    className="button-danger"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr className="bg-white border-b">
                                    <td colSpan="100%" className="p-3 text-center">
                                        Variant belum tersedia.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </App>
    );
};

export default Index;
