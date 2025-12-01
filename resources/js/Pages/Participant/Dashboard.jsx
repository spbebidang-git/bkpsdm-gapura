import App from "@/Layouts/Participant/App";
import { usePage } from "@inertiajs/react";

const Dashboard = () => {
    const { stats, ongoingCourses, completedCourses } = usePage().props;

    return (
        <App>
            <div className="p-6 max-w-7xl mx-auto space-y-8">
                <h1 className="text-2xl font-bold text-gray-800">
                    Dashboard
                </h1>

                {/* Statistik singkat */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card title="Total Course" value={stats.totalCourses} color="bg-blue-500" />
                    <Card title="Selesai" value={stats.completedCourses} color="bg-green-500" />
                    <Card title="On Going" value={stats.ongoingCourses} color="bg-yellow-500" />
                    <Card title="Sertifikat" value={stats.certificates} color="bg-purple-500" />
                </div>

                {/* Progress rata-rata */}
                <div className="bg-white rounded-xl shadow p-6 border border-gray-100">
                    <h2 className="font-semibold text-gray-700 mb-2">Rata-rata Progress</h2>
                    <Progress value={stats.avgProgress} />
                    <p className="text-gray-600 mt-1 text-sm">
                        {stats.avgProgress}% dari seluruh course telah diselesaikan.
                    </p>
                </div>

                {/* Course yang sedang diikuti */}
                <div className="bg-white rounded-xl shadow p-6 border border-gray-100">
                    <h2 className="font-semibold text-gray-700 mb-4">Sedang Diikuti</h2>
                    {ongoingCourses.length > 0 ? (
                        <div className="space-y-3">
                            {ongoingCourses.map((course) => (
                                <div
                                    key={course.id}
                                    className="p-4 border rounded-lg hover:bg-gray-50 transition"
                                >
                                    <div className="flex justify-between items-center mb-1">
                                        <h3 className="font-medium text-gray-800">
                                            {course.title}
                                        </h3>
                                        <span className="text-sm text-gray-500">
                                            {course.progress}%
                                        </span>
                                    </div>
                                    <Progress value={course.progress} />
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-500 text-sm">Belum ada course berjalan.</p>
                    )}
                </div>

                {/* Course yang sudah selesai */}
                <div className="bg-white rounded-xl shadow p-6 border border-gray-100">
                    <h2 className="font-semibold text-gray-700 mb-4">Course Selesai</h2>
                    {completedCourses.length > 0 ? (
                        <table className="w-full text-sm text-left text-gray-600 border">
                            <thead className="bg-gray-100">
                                <tr>
                                    <th className="px-4 py-2">Judul</th>
                                    <th className="px-4 py-2 text-right">Tanggal Selesai</th>
                                </tr>
                            </thead>
                            <tbody>
                                {completedCourses.map((course) => (
                                    <tr key={course.id} className="border-t hover:bg-gray-50">
                                        <td className="px-4 py-2">{course.title}</td>
                                        <td className="px-4 py-2 text-right">
                                            {course.date}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <p className="text-gray-500 text-sm">Belum ada course yang selesai.</p>
                    )}
                </div>
            </div>
        </App>
    );
};

// Card Component
const Card = ({ title, value, color }) => (
    <div className="bg-white rounded-xl shadow border border-gray-100 p-4 flex flex-col items-center justify-center text-center">
        <div
            className={`w-12 h-12 flex items-center justify-center rounded-full text-white ${color}`}
        >
            <span className="text-lg font-bold">{value}</span>
        </div>
        <p className="text-sm text-gray-500 mt-2">{title}</p>
    </div>
);

// Progress Component
const Progress = ({ value }) => (
    <div className="w-full bg-gray-200 rounded-full h-2">
        <div
            className="bg-blue-500 h-2 rounded-full transition-all duration-500"
            style={{ width: `${value}%` }}
        ></div>
    </div>
);

export default Dashboard;
