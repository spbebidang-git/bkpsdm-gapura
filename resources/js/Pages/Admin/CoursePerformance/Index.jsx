import {
    badgeOk,
    badgePending,
    badgeWarn,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
    tableClass,
    tableFrame,
    tableScroller,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

function Margin({ value }) {
    const margin = Number(value);
    if (Number.isNaN(margin)) return <span className="text-stone">—</span>;
    if (margin > 0) return <span className={badgeOk}>+{margin}</span>;
    if (margin < 0) return <span className={badgeWarn}>{margin}</span>;
    return <span className={badgePending}>0</span>;
}

export default function Index({ result = [] }) {
    const rows = Array.isArray(result) ? result : [];

    return (
        <AdminLayout title="Performa Kelas" hideTitle>
            <Head title="Performa Kelas" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Pembelajaran</p>
                        <h1 className={pageTitle}>Performa kelas</h1>
                        <p className={pageDescription}>Perbandingan rata-rata nilai pre-test dan post-test per kelas.</p>
                    </div>
                </div>

                <div className={tableFrame}>
                    <div className={tableScroller}>
                        <table className={`${tableClass} min-w-[760px]`}>
                            <thead>
                                <tr>
                                    <th className="w-14 text-left">No.</th>
                                    <th className="text-left">Kelas</th>
                                    <th className="text-right">Rata-rata pre-test</th>
                                    <th className="text-right">Rata-rata post-test</th>
                                    <th className="text-right">Selisih</th>
                                    <th className="text-left">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="admin-table-state">
                                            <i className="bx bx-line-chart text-2xl text-stone" aria-hidden />
                                            <p className="font-semibold text-ink">Belum ada data performa</p>
                                            <p className="text-sm text-steel">Data tersedia setelah pre-test dan post-test dinilai.</p>
                                        </td>
                                    </tr>
                                )}
                                {rows.map((row, index) => (
                                    <tr key={row.course_id ?? `${row.title}-${index}`}>
                                        <td className="tabular-nums text-stone">{index + 1}</td>
                                        <td className="font-semibold text-ink">{row.course_title ?? row.title ?? row.name ?? '—'}</td>
                                        <td className="text-right tabular-nums">{row.avg_pre_test ?? row.pre_avg ?? row.pre ?? '—'}</td>
                                        <td className="text-right tabular-nums">{row.avg_post_test ?? row.post_avg ?? row.post ?? '—'}</td>
                                        <td className="text-right"><Margin value={row.avg_margin ?? row.margin ?? row.delta} /></td>
                                        <td className="text-steel">{row.note ?? row.pre_note ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="admin-table-footer">
                        <p className="text-xs font-medium text-steel">{rows.length.toLocaleString('id-ID')} kelas</p>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
