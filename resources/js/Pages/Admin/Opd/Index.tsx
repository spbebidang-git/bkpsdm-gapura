import DataTable from '@/Components/Admin/DataTable';
import { CrudPageHeader, CrudStatusBadge } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, router } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';
import '@/../css/admin-opd.css';
import '@/../css/admin-course-index.css';

export default function Index({ lastSyncedAt, configured }: { lastSyncedAt: string | null; configured: boolean }) {
    const busy = useRef(false);
    const [syncing, setSyncing] = useState(false);
    const [refreshKey, setRefreshKey] = useState(0);
    const formattedSync = lastSyncedAt && !Number.isNaN(new Date(lastSyncedAt).getTime())
        ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(lastSyncedAt))
        : lastSyncedAt;
    const columns = useMemo(() => [
        { id: 'DT_RowIndex', header: 'No', enableSorting: false, enableGlobalFilter: false, cell: ({ row, table }) => <span>{table.getState().pagination.pageIndex * table.getState().pagination.pageSize + row.index + 1}</span> },
        { accessorKey: 'code', header: 'Kode', cell: ({ getValue }) => <span className="opd-code">{getValue()}</span> },
        { accessorKey: 'name', header: 'Nama OPD', cell: ({ getValue }) => <span className="opd-name">{getValue()}</span> },
        { accessorKey: 'is_active', header: 'Status', enableGlobalFilter: false, cell: ({ getValue }) => <CrudStatusBadge active={Boolean(getValue())} /> },
        { accessorKey: 'synced_at', header: 'Sumber', cell: ({ getValue }) => getValue() ? <span className="opd-source is-sso"><i className="bx bx-cloud-download" aria-hidden />Kedaton SSO</span> : <span className="opd-source"><i className="bx bx-edit" aria-hidden />Lokal</span> },
    ], []);

    const sync = () => {
        if (busy.current || !configured) return;
        busy.current = true;
        setSyncing(true);
        router.post(route('admin.opd.sync'), {}, {
            preserveScroll: true,
            onSuccess: ({ props }) => {
                if (!props.flash?.danger) setRefreshKey((key) => key + 1);
            },
            onFinish: () => { busy.current = false; setSyncing(false); },
        });
    };

    return <AdminLayout title="OPD" hideTitle>
        <Head title="OPD" />
        <div className="opd-admin course-index">
            <CrudPageHeader title="OPD" description={<>Data organisasi perangkat daerah dikelola melalui sinkronisasi Kedaton SSO. Sinkronisasi terakhir: <strong>{formattedSync || 'Belum pernah'}</strong>{!configured && <span className="opd-config-warning"><i className="bx bx-error-circle" aria-hidden /> Endpoint atau API key master OPD belum dikonfigurasi.</span>}</>} actions={<>
                <button type="button" className="course-index-create" onClick={sync} disabled={!configured || syncing} aria-live="polite">
                    <i className={`bx ${syncing ? 'bx-loader-alt bx-spin' : 'bx-refresh'}`} aria-hidden />
                    {syncing ? 'Menyinkronkan…' : 'Sinkronkan OPD'}
                </button>
            </>} />
            <DataTable
                url={route('admin.opd.index')}
                columns={columns}
                refreshKey={refreshKey}
                className="course-index-table admin-crud-table opd-admin-table"
                searchPlaceholder="Cari kode atau nama OPD..."
                emptyTitle="Belum ada OPD"
                emptyDescription="Jalankan sinkronisasi Kedaton untuk mengambil data OPD."
                dataLabel="OPD"
            />
        </div>
    </AdminLayout>;
}
