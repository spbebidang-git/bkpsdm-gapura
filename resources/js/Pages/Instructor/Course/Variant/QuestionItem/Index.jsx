import App from "@/Layouts/Instructor/App";
import { Link, router } from "@inertiajs/react";

const Index = ({ slug, activityId, questionId, questionItems, activity }) => {
    const handleSubmitDelete = (e, questionItemId) => {
        e.preventDefault();
        router.delete(
            `/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/${questionItemId}`
        );
    };

    return (
        <App>
            {/* JUDUL HALAMAN */}
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">
                Daftar Soal
            </div>

            {/* DETAIL AKTIVITAS */}
            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow mb-8">
                <div className="p-5 text-lg font-bold flex justify-between items-center">
                    Detail Kegiatan

                    <Link
                        href={route("instructor.course.activity.variant.index", [
                            slug,
                            activityId,
                        ])}
                    >
                        <button className="button-secondary">Kembali</button>
                    </Link>
                </div>

                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left text-gray-500">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr className="sticky top-0 z-10">
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Tipe
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Kegiatan
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Ulangi
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Variasi
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Acak
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr className="bg-white border-b">
                                <td className="px-6 py-4 text-center">
                                    {activity.type}
                                </td>
                                <td className="px-6 py-4 text-center">
                                    {activity.title}
                                </td>
                                <td className="px-6 py-4 text-center">
                                    {activity.is_loop}
                                </td>
                                <td className="px-6 py-4 text-center">
                                    {activity.variant}
                                </td>
                                <td className="px-6 py-4 text-center">
                                    {activity.mix}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {/* LIST PERTANYAAN */}
            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow">
                <div className="p-5 text-lg font-bold flex justify-between items-center">
                    List Pertanyaan

                    <Link
                        href={`/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/create`}
                        className="px-4 py-2 bg-purple-500 text-white rounded-xl shadow text-sm"
                    >
                        Tambah Pertanyaan
                    </Link>
                </div>

                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left text-gray-500">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr className="sticky top-0 z-10">
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    #
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Pertanyaan
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Opsi A
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Opsi B
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Opsi C
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Opsi D
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Skor
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            {questionItems.length ? (
                                questionItems.map((item, index) => (
                                    <tr className="bg-white border-b" key={index}>
                                        <td className="px-6 py-4 text-center">
                                            {index + 1}
                                        </td>

                                        <td className="px-6 py-4 text-center">
                                            {item.question}
                                        </td>

                                        {/* Highlight jawaban benar */}
                                        <td
                                            className={`px-6 py-4 text-center ${item.is_correct === item.option1
                                                ? "text-green-600 font-bold"
                                                : ""
                                                }`}
                                        >
                                            {item.option1}
                                        </td>
                                        <td
                                            className={`px-6 py-4 text-center ${item.is_correct === item.option2
                                                ? "text-green-600 font-bold"
                                                : ""
                                                }`}
                                        >
                                            {item.option2}
                                        </td>
                                        <td
                                            className={`px-6 py-4 text-center ${item.is_correct === item.option3
                                                ? "text-green-600 font-bold"
                                                : ""
                                                }`}
                                        >
                                            {item.option3}
                                        </td>
                                        <td
                                            className={`px-6 py-4 text-center ${item.is_correct === item.option4
                                                ? "text-green-600 font-bold"
                                                : ""
                                                }`}
                                        >
                                            {item.option4}
                                        </td>

                                        <td className="px-6 py-4 text-center">
                                            {item.score}
                                        </td>

                                        <td className="px-6 py-4 text-center">
                                            <div className="flex gap-2 justify-center">
                                                <Link
                                                    href={`/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/${item.id}/edit`}
                                                >
                                                    <button className="button-warning">
                                                        Edit
                                                    </button>
                                                </Link>

                                                <button
                                                    onClick={(e) =>
                                                        handleSubmitDelete(
                                                            e,
                                                            item.id
                                                        )
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
                                    <td
                                        className="p-3 text-center"
                                        colSpan="100%"
                                    >
                                        Tidak ada pertanyaan.
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
