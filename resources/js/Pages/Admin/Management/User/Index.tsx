import { CreateButton, RowActions } from '@/Components/Admin/ActionButtons';
import DataTable from '@/Components/Admin/DataTable';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import AdminLayout from '@/Layouts/Admin/App';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '@/../css/admin-course-index.css';
import '@/../css/admin-user.css';

export default function Index() {
    const [tableKey, setTableKey] = useState(0);
    const columns = useMemo(
        () => [
            {
                id: 'DT_RowIndex',
                header: 'No',
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ row, table }) => {
                    const { pageIndex, pageSize } = table.getState().pagination;
                    return (
                        <span className="tabular-nums text-[#8496B0]">
                            {pageIndex * pageSize + row.index + 1}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'name',
                header: 'Nama',
                cell: ({ getValue }) => (
                    <span className="user-admin-name">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'email',
                header: 'Email',
                cell: ({ getValue }) => (
                    <span className="user-admin-email">{getValue() || '—'}</span>
                ),
            },
            {
                accessorKey: 'auth_source',
                header: 'Sumber masuk',
                cell: ({ getValue }) => {
                    const source = getValue();
                    const label = { local: 'GAPURA lokal', sso: 'Kedaton SSO', prestige: 'Prestige' }[source] || source;
                    const isLocal = source === 'local';

                    return (
                        <span className={`management-user-source inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ${isLocal ? 'bg-surface-soft text-steel ring-hairline' : 'bg-primary-mist text-primary-pressed ring-primary/20'}`}>
                            {label}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'roles',
                header: 'Peran',
                cell: ({ getValue }) => {
                    const value = getValue();
                    const roles = Array.isArray(value) ? value : Object.values(value || {});
                    const labels = { super_admin: 'Super Admin', administrator: 'Administrator', trainer: 'Pemateri', participant: 'Participant' };
                    const badgeStyles = {
                        super_admin: 'admin-crud-status is-inactive',
                        administrator: 'admin-crud-status is-inactive',
                        trainer: 'admin-crud-status is-inactive',
                        participant: 'admin-crud-status is-inactive',
                    };

                    if (!roles.length) {
                        return <span className="text-[#8496B0]">Belum ditetapkan</span>;
                    }

                    return (
                        <div className="management-user-roles flex flex-wrap gap-1.5">
                            {roles.map((role) => (
                                <span
                                    key={role}
                                    className={badgeStyles[role] || 'admin-crud-status is-inactive'}
                                >
                                    {labels[role] || role}
                                </span>
                            ))}
                        </div>
                    );
                },
            },
            {
                id: 'aksi',
                accessorKey: 'id',
                header: 'Aksi',
                meta: { align: 'right' },
                enableSorting: false,
                enableGlobalFilter: false,
                cell: ({ row, getValue }) => row.original.roles?.includes('super_admin')
                    ? <span className="text-xs font-medium text-[#8496B0]">Akses penuh</span>
                    : <RowActions
                        editHref={route('admin.management.user.edit', getValue())}
                        deleteUrl={route('admin.management.user.destroy', getValue())}
                        deleteMessage={`Hapus pengguna ${row.original.name}? Pengguna tidak akan bisa masuk lagi ke panel admin.`}
                        onDeleted={() => setTableKey((key) => key + 1)}
                        iconOnly
                        variant="course"
                    />,
            },
        ],
        []
    );

    return (
        <AdminLayout title="Pengguna" hideTitle>
            <Head title="Pengguna" />
            <div className="user-admin course-index">
                <CrudPageHeader title="Pengguna" description="Kelola akun administrator dan akses pengelolaan GAPURA." actions={
                    <CreateButton href={route('admin.management.user.create')} label="Tambah admin" icon="bx-plus" className="course-index-create" />
                } />
                <DataTable
                    refreshKey={tableKey}
                    url={route('admin.management.user.index')}
                    columns={columns}
                    className="course-index-table admin-crud-table user-admin-table"
                    searchPlaceholder="Cari nama atau email..."
                    emptyTitle="Belum ada akun admin"
                    emptyDescription="Tambahkan akun admin untuk mengelola GAPURA."
                    dataLabel="admin"
                />
            </div>
        </AdminLayout>
    );
}
