import App from "@/Layouts/Admin/App";
import { router } from "@inertiajs/react";
import { useState } from "react";

const Show = ({ course, results, names, activities, filters }) => {
    const [filterNama, setFilterNama] = useState(filters?.nama || "");
    const [filterKegiatan, setFilterKegiatan] = useState(filters?.kegiatan || "");

    const handleFilter = () => {
        router.get(
            route("admin.result.show", course.slug),
            {
                nama: filterNama,
                kegiatan: filterKegiatan,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    return (
        <App>
            <div className="max-w-7xl mx-auto">
                <div className="bg-white rounded-xl shadow mb-6">
                    <div className="flex justify-between items-center px-6 py-4 border-b">
                        <h2 className="text-xl font-bold text-gray-700">Result Overview</h2>
                        <a
                            href={route("admin.result.index")}
                            className="button-secondary"
                        >
                            Back
                        </a>
                    </div>

                    {/* Filters */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 px-6 py-4 border-b">
                        <div>
                            <select
                                className="form-select w-full"
                                value={filterNama}
                                onChange={(e) => setFilterNama(e.target.value)}
                            >
                                <option value="">-- Semua Nama --</option>
                                {names.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <select
                                className="form-select w-full"
                                value={filterKegiatan}
                                onChange={(e) => setFilterKegiatan(e.target.value)}
                            >
                                <option value="">-- Semua Kegiatan --</option>
                                {activities.map((activity) => (
                                    <option key={activity.id} value={activity.id}>
                                        {activity.title}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-span-2 flex justify-end">
                            <button onClick={handleFilter} className="button-primary">
                                Filter
                            </button>
                        </div>
                    </div>

                    {/* Table */}
                    <div className="overflow-x-auto px-6 py-4">
                        <table className="w-full text-sm text-center text-gray-500 border">
                            <thead className="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                <tr>
                                    <th className="px-4 py-2">#</th>
                                    <th className="px-4 py-2">NIP</th>
                                    <th className="px-4 py-2">Nama</th>
                                    <th className="px-4 py-2">Jabatan</th>
                                    <th className="px-4 py-2">Kegiatan</th>
                                    <th className="px-4 py-2">Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                {results.length > 0 ? (
                                    results.map((row, index) => (
                                        <tr key={index} className="bg-white border-b">
                                            <td className="px-4 py-2">{index + 1}</td>
                                            <td className="px-4 py-2">{row.nip}</td>
                                            <td className="px-4 py-2">{row.name}</td>
                                            <td className="px-4 py-2">{row.jabatan}</td>
                                            <td className="px-4 py-2">{row.kegiatan}</td>
                                            <td className="px-4 py-2">{row.nilai}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-4 text-center">
                                            Tidak ada data.
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
