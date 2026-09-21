import { tableFrame } from '@/Components/Admin/ui';
import DataTable from 'datatables.net-react';
import DT from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import { useMemo } from 'react';

DataTable.use(DT);

/**
 * Server-side DataTables (Yajra-compatible).
 * @param {string|object} ajax - URL string or DataTables ajax config
 * @param {array} columns - DT columns (data keys)
 * @param {object} options - extra DT options
 * @param {object} slots - React column renderers { '1': (data) => ... }
 * @param {node} children - optional <thead>
 */
export default function ServerDataTable({
    ajax,
    columns,
    options = {},
    slots,
    children,
    className = 'display w-full text-sm',
}) {
    const ajaxConfig = useMemo(() => {
        if (typeof ajax === 'string') {
            return {
                url: ajax,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            };
        }
        return {
            ...ajax,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                ...(ajax?.headers || {}),
            },
        };
    }, [ajax]);

    return (
        <div className={`${tableFrame} overflow-x-auto`}>
            <DataTable
                className={className}
                ajax={ajaxConfig}
                columns={columns}
                slots={slots}
                options={{
                    serverSide: true,
                    processing: true,
                    pageLength: 15,
                    ...options,
                }}
            >
                {children}
            </DataTable>
        </div>
    );
}
