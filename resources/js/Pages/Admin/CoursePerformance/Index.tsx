import DataTable from '@/Components/Admin/DataTable';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import { reportNumber } from '@/Components/Admin/ReportSummary';
import { badgeOk, badgePending, badgeWarn } from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/Admin/App';
import { Head } from '@inertiajs/react';
import { useMemo } from 'react';
import '@/../css/admin-report.css';

type PerformanceRow = {
    course_id: number | string;
    course_title: string;
    avg_pre_test: number | null;
    avg_post_test: number | null;
    avg_margin: number | null;
    pre_test_note?: string;
    post_test_note?: string;
};

function Margin({ value }: { value: number | null }) {
    if (value === null || value === undefined || !Number.isFinite(Number(value))) return <span className="text-stone">—</span>;
    const margin = Number(value);
    return <span className={margin > 0 ? badgeOk : margin < 0 ? badgeWarn : badgePending}>
        <i className={`bx ${margin > 0 ? 'bx-trending-up' : margin < 0 ? 'bx-trending-down' : 'bx-minus'}`} aria-hidden />
        {margin > 0 ? '+' : ''}{reportNumber(margin)}
    </span>;
}

export default function Index({ result = [] }: { result?: PerformanceRow[] }) {
    const rows = Array.isArray(result) ? result : [];
    const cards = [
        { label: 'Kelas dievaluasi', value: rows.length, icon: 'bx-book-open', note: 'Memiliki hasil evaluasi' },
        { label: 'Nilai meningkat', value: rows.filter(row => row.avg_margin != null && row.avg_margin > 0).length, icon: 'bx-trending-up', note: 'Post-test lebih tinggi' },
        { label: 'Nilai tetap', value: rows.filter(row => row.avg_margin != null && Number(row.avg_margin) === 0).length, icon: 'bx-minus', note: 'Pre-test dan post-test sama' },
        { label: 'Nilai menurun', value: rows.filter(row => row.avg_margin != null && row.avg_margin < 0).length, icon: 'bx-trending-down', note: 'Post-test lebih rendah' },
    ];
    const columns = useMemo(() => [
        { id: 'DT_RowIndex', header: 'No', enableSorting: false, enableGlobalFilter: false, cell: ({ row, table }) => <span className="tabular-nums text-stone">{table.getState().pagination.pageIndex * table.getState().pagination.pageSize + row.index + 1}</span> },
        { accessorKey: 'course_title', header: 'Kelas', cell: ({ getValue }) => <div className="report-identity"><span className="report-row-icon"><i className="bx bx-book-open" aria-hidden /></span><span className="font-semibold text-ink">{getValue()}</span></div> },
        { accessorKey: 'avg_pre_test', header: 'Rata-rata pre-test', enableGlobalFilter: false, meta: { align: 'right' }, cell: ({ getValue }) => <span className="tabular-nums">{reportNumber(getValue())}</span> },
        { accessorKey: 'avg_post_test', header: 'Rata-rata post-test', enableGlobalFilter: false, meta: { align: 'right' }, cell: ({ getValue }) => <strong className="tabular-nums text-ink">{reportNumber(getValue())}</strong> },
        { accessorKey: 'avg_margin', header: 'Selisih nilai', enableGlobalFilter: false, meta: { align: 'right' }, cell: ({ getValue }) => <Margin value={getValue()} /> },
        { id: 'notes', header: 'Catatan evaluasi', enableSorting: false, enableGlobalFilter: false, cell: ({ row }) => <div className="min-w-[240px] max-w-md space-y-3 whitespace-normal text-sm leading-relaxed"><div><span className="block text-xs font-semibold text-ink">Pre-test</span><p>{row.original.pre_test_note || '—'}</p></div><div><span className="block text-xs font-semibold text-ink">Post-test</span><p>{row.original.post_test_note || '—'}</p></div></div> },
    ], []);

    return <AdminLayout title="Performa kelas" hideTitle>
        <Head title="Performa kelas" />
        <div className="report-page">
            <CrudPageHeader title="Performa kelas" description="Bandingkan hasil pre-test dan post-test untuk melihat perkembangan belajar pengguna di setiap kelas." />
            <section className="report-summary" aria-label="Ringkasan performa kelas">{cards.map(card => <article key={card.label} className="report-summary-card"><span className="report-summary-icon"><i className={`bx ${card.icon}`} aria-hidden /></span><div><p>{card.label}</p><strong>{reportNumber(card.value)}</strong><small>{card.note}</small></div></article>)}</section>
            <section className="report-collection" aria-labelledby="performance-list-title">
                <header className="report-collection-header"><div><h2 id="performance-list-title">Evaluasi per kelas</h2><p>Selisih nilai dihitung dari rata-rata post-test dikurangi pre-test.</p></div></header>
                <DataTable url={route('admin.course-performance.index')} columns={columns} showUpdatedAt={false} className="admin-crud-table report-table" dataLabel="kelas" searchPlaceholder="Cari nama kelas…" emptyTitle="Belum ada data performa" emptyDescription="Data tersedia setelah pengguna menyelesaikan kelas dan hasil evaluasi tersedia." />
            </section>
        </div>
    </AdminLayout>;
}
