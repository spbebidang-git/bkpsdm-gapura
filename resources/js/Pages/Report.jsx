import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';

export default function Report() {
    const { data } = usePage().props;

    return (
        <AdminLayout title="Laporan">
            <Head title="Laporan Peserta" />
            <div className="p-6">
                <h1 className="text-2xl font-bold mb-6">Laporan Peserta</h1>
                <table className="min-w-full border-collapse border border-gray-300">
                    <thead>
                        <tr className="bg-gray-100">
                            <th className="border border-gray-300 px-4 py-2">Nama</th>
                            <th className="border border-gray-300 px-4 py-2">Pre Total</th>
                            <th className="border border-gray-300 px-4 py-2">Pre Benar</th>
                            <th className="border border-gray-300 px-4 py-2">Pre Salah</th>
                            <th className="border border-gray-300 px-4 py-2">Pre Score</th>
                            <th className="border border-gray-300 px-4 py-2">Post Total</th>
                            <th className="border border-gray-300 px-4 py-2">Post Benar</th>
                            <th className="border border-gray-300 px-4 py-2">Post Salah</th>
                            <th className="border border-gray-300 px-4 py-2">Post Score</th>
                            <th className="border border-gray-300 px-4 py-2">Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.map((item, index) => (
                            <tr key={index}>
                                <td className="border border-gray-300 px-4 py-2">{item.name}</td>
                                <td className="border border-gray-300 px-4 py-2">{item.pre_score_total}</td>
                                <td className="border border-gray-300 px-4 py-2">{item.pre_correct}</td>
                                <td className="border border-gray-300 px-4 py-2">{item.pre_wrong}</td>
                                <td className="border border-gray-300 px-4 py-2">{item.pre_score}</td>
                                <td className="border border-gray-300 px-4 py-2">{item.post_score_total}</td>
                                <td className="border border-gray-300 px-4 py-2">{item.post_correct}</td>
                                <td className="border border-gray-300 px-4 py-2">{item.post_wrong}</td>
                                <td className="border border-gray-300 px-4 py-2">{item.post_score}</td>
                                <td className="border border-gray-300 px-4 py-2">{item.finished_at}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
