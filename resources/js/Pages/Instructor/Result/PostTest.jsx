import App from "@/Layouts/Instructor/App";

const PostTest = ({ results }) => {
    return (
        <App>
            <div className="max-w-7xl mx-auto">
                <div className="bg-white rounded-xl shadow mb-6">
                    <div className="flex justify-between items-center px-6 py-4 border-b">
                        <h2 className="text-xl font-bold text-gray-700">
                            Post Test Results
                        </h2>
                        <a
                            href={route("admin.result.index")}
                            className="button-secondary"
                        >
                            Back
                        </a>
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
                                    <th className="px-4 py-2">Rata-rata Nilai</th>
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
                                            <td className="px-4 py-2">
                                                {row.avg_score}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="5" className="px-4 py-4 text-center">
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

export default PostTest;
