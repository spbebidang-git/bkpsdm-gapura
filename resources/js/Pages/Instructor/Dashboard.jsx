import App from "@/Layouts/Instructor/App";
import { Link, router, usePage } from "@inertiajs/react";

const Dashboard = ({ courses }) => {
    const { auth } = usePage().props;

    const handleSubmitDelete = (e, slug) => {
        e.preventDefault();
        if (confirm("Apakah anda yakin ingin menghapus kelas ini?")) {
            router.delete(`/instructor/course/${slug}`);
        }
    };

    return (
        <App>
            <div className="max-w-7xl mx-auto mb-8">
                <h1 className="text-3xl font-extrabold text-gray-800 tracking-tight">
                    Hai, {auth.user.name} 👋
                </h1>
                <p className="text-gray-500 mt-2">Selamat datang kembali di dashboard instruktur Anda.</p>
            </div>

            <div className="max-w-7xl mx-auto bg-white border border-gray-100 overflow-hidden rounded-2xl shadow-sm">
                <div className="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                    <h2 className="text-xl font-bold text-gray-800">Daftar Kelas</h2>
                    <Link
                        href="/instructor/course/create"
                        className="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl shadow-sm hover:shadow text-sm font-semibold transition-all whitespace-nowrap"
                    >
                        <box-icon name='plus' color='#ffffff' size='18px'></box-icon>
                        Tambah Kelas
                    </Link>
                </div>

                <div className="relative overflow-x-auto max-h-[600px] w-full">
                    <table className="w-full text-sm text-left text-gray-600">
                        <thead className="text-xs text-gray-500 uppercase bg-gray-50/80 sticky top-0 z-10 backdrop-blur-sm">
                            <tr>
                                <th className="px-6 py-4 font-semibold whitespace-nowrap">#</th>
                                <th className="px-6 py-4 font-semibold whitespace-nowrap">Tipe</th>
                                <th className="px-6 py-4 font-semibold whitespace-nowrap min-w-[200px]">Judul</th>
                                <th className="px-6 py-4 font-semibold text-center whitespace-nowrap">Kuota</th>
                                <th className="px-6 py-4 font-semibold text-center whitespace-nowrap">Total Peserta</th>
                                <th className="px-6 py-4 font-semibold text-center whitespace-nowrap">Status</th>
                                <th className="px-6 py-4 font-semibold text-center whitespace-nowrap min-w-[200px]">Aksi</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-gray-100">
                            {courses.length > 0 ? (
                                courses.map((item, index) => (
                                    <tr
                                        key={index}
                                        className="bg-white hover:bg-gray-50/50 transition-colors group"
                                    >
                                        <td className="px-6 py-4 whitespace-nowrap">{index + 1}</td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-block px-3 py-1 text-xs font-semibold rounded-full ${item.type === 'schedule' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'}`}>
                                                {item.type}
                                            </span>
                                        </td>

                                        <th
                                            scope="row"
                                            className="px-6 py-4 font-semibold text-gray-800"
                                        >
                                            <div className="line-clamp-2 leading-relaxed">
                                                {item.title}
                                            </div>
                                        </th>

                                        <td className="px-6 py-4 text-center text-gray-600 whitespace-nowrap">{item.quota || '-'}</td>
                                        <td className="px-6 py-4 text-center font-medium text-gray-700 whitespace-nowrap">{item.total_participant}</td>
                                        <td className="px-6 py-4 text-center whitespace-nowrap">
                                            <span className={`inline-block px-3 py-1 text-xs font-semibold rounded-full ${item.status === 'publish' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'}`}>
                                                {item.status}
                                            </span>
                                        </td>

                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex justify-center items-center gap-2 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                                <Link href={`/instructor/course/${item.slug}`}>
                                                    <button className="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                                        Detail
                                                    </button>
                                                </Link>

                                                <Link href={`/instructor/course/${item.slug}/edit`}>
                                                    <button className="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg transition-colors">
                                                        Edit
                                                    </button>
                                                </Link>

                                                <button
                                                    onClick={(e) => handleSubmitDelete(e, item.slug)}
                                                    className="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td className="p-8 text-center text-gray-500" colSpan="100%">
                                        <div className="flex flex-col items-center justify-center">
                                            <box-icon name='folder-open' color='#cbd5e1' size='48px'></box-icon>
                                            <p className="mt-3 text-sm font-medium">Belum ada kelas yang ditambahkan.</p>
                                        </div>
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
