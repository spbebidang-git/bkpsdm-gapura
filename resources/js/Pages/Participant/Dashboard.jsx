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
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <Card title="Total Course" value={stats.totalCourses} color="bg-primary" />
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
                                    className="p-5 border border-gray-100 rounded-xl hover:shadow-md hover:border-primary/30 transition-all duration-300 bg-gray-50/50 hover:bg-white group"
                                >
                                    <div className="flex justify-between items-center mb-2">
                                        <h3 className="font-semibold text-gray-800 group-hover:text-primary transition-colors">
                                            {course.title}
                                        </h3>
                                        <span className="text-sm font-medium text-primary bg-primary/10 px-2.5 py-1 rounded-full">
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
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left text-gray-600 border border-gray-100 rounded-lg overflow-hidden">
                                <thead className="bg-gray-50 text-gray-700 font-semibold border-b border-gray-100">
                                    <tr>
                                        <th className="px-5 py-3">Judul</th>
                                        <th className="px-5 py-3 text-right">Tanggal Selesai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {completedCourses.map((course) => (
                                        <tr key={course.id} className="border-b border-gray-50 last:border-0 hover:bg-primary/5 transition-colors">
                                            <td className="px-5 py-4 font-medium text-gray-800">{course.title}</td>
                                            <td className="px-5 py-4 text-right text-gray-500 whitespace-nowrap">
                                                {course.date}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
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
    <div className="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1.5 border border-gray-100 p-4 sm:p-6 flex flex-col items-center justify-center text-center group cursor-default">
        <div
            className={`w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center rounded-2xl text-white ${color} shadow-sm group-hover:scale-110 transition-transform duration-300 ring-4 ring-white`}
        >
            <span className="text-xl sm:text-2xl font-bold">{value}</span>
        </div>
        <p className="font-medium text-sm sm:text-base text-gray-600 mt-3 sm:mt-4 group-hover:text-gray-900 transition-colors">{title}</p>
    </div>
);

// Progress Component
const Progress = ({ value }) => (
    <div className="w-full bg-gray-200 rounded-full h-2">
        <div
            className="bg-primary h-2 rounded-full transition-all duration-500"
            style={{ width: `${value}%` }}
        ></div>
    </div>
);

export default Dashboard;
