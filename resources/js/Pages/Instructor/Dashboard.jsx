import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";

const Dashboard = ({ courses }) => {
    const { auth } = usePage().props;

    const handleSubmitDelete = (e, slug) => {
        e.preventDefault();
        router.delete(`/instructor/course/${slug}`);
    };

    return (
        <App>
            <div className="text-2xl max-w-7xl mx-auto font-bold text-gray-700 mb-5">
                Hai, {auth.user.name}
            </div>

            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow">
                <div className="p-5 text-lg font-bold flex justify-between items-center">
                    Daftar Kursus
                    <Link
                        href="/instructor/course/create"
                        className="px-4 py-2 bg-primary text-white rounded-xl shadow text-sm"
                    >
                        Tambah Kursus
                    </Link>
                </div>

                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr className="sticky top-0 z-10">
                                <th className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">#</th>
                                <th className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">Tipe</th>
                                <th className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">Judul</th>
                                <th className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">Kuota</th>
                                <th className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Total Peserta
                                </th>
                                <th className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">Status</th>
                                <th className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            {courses.length > 0 ? (
                                courses.map((item, index) => (
                                    <tr
                                        key={index}
                                        className="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                                    >
                                        <td className="px-6 py-4 text-center">{index + 1}</td>
                                        <td className="px-6 py-4 text-center">{item.type}</td>

                                        <th
                                            scope="row"
                                            className="px-6 py-4 text-center font-medium text-gray-900 dark:text-white"
                                        >
                                            {item.title}
                                        </th>

                                        <td className="px-6 py-4 text-center">{item.quota}</td>
                                        <td className="px-6 py-4 text-center">{item.total_participant}</td>
                                        <td className="px-6 py-4 text-center">{item.status}</td>

                                        <td className="px-6 py-4 text-center">
                                            <div className="flex justify-center items-center gap-2">
                                                <Link href={`/instructor/course/${item.slug}`}>
                                                    <button className="w-24 h-7 text-sm font-medium bg-primary text-white rounded-full shadow">
                                                        Detail
                                                    </button>
                                                </Link>

                                                <Link href={`/instructor/course/${item.slug}/edit`}>
                                                    <button className="button-warning">Edit</button>
                                                </Link>

                                                <button
                                                    onClick={(e) => handleSubmitDelete(e, item.slug)}
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
                                    <td className="p-3 text-center" colSpan="100%">
                                        Data tidak tersedia.
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

export default Dashboard;
