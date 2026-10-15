import { flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import axios from 'axios';
import { useCallback, useEffect, useMemo, useState } from 'react';

const alignClass = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
};

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

function UpdatedAtCell({ getValue }) {
    const value = getValue();
    if (!value) return <span className="text-stone">—</span>;

    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
        return <span className="tabular-nums text-steel">{String(value)}</span>;
    }

    return (
        <time dateTime={date.toISOString()} className="whitespace-nowrap tabular-nums text-steel">
            {dateTimeFormatter.format(date)}
        </time>
    );
}

export default function DataTable({
    url,
    columns,
    pageSize = 10,
    params: extraParams = {},
    searchPlaceholder = 'Cari data…',
    emptyTitle = 'Belum ada data',
    emptyDescription = 'Data akan muncul di sini setelah tersedia.',
    toolbarFilters = null,
    className = '',
    dataLabel = 'data',
    refreshKey = 0,
}) {
    const [data, setData] = useState([]);
    const [total, setTotal] = useState(0);
    const [filtered, setFiltered] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [pagination, setPagination] = useState({ pageIndex: 0, pageSize });
    const [sorting, setSorting] = useState([]);
    const [globalFilter, setGlobalFilter] = useState('');
    const [searchInput, setSearchInput] = useState('');
    const extraKey = JSON.stringify(extraParams);
    useEffect(() => {
        setPagination((current) => current.pageIndex === 0 ? current : { ...current, pageIndex: 0 });
    }, [extraKey]);
    const displayColumns = useMemo(() => {
        const updatedAtColumn = {
            accessorKey: 'updated_at',
            header: 'Terakhir diperbarui',
            enableGlobalFilter: false,
            cell: UpdatedAtCell,
        };
        const normalized = columns.map((column) =>
            column.accessorKey === 'updated_at'
                ? { ...column, ...updatedAtColumn }
                : column,
        );

        if (normalized.some((column) => column.accessorKey === 'updated_at')) {
            return normalized;
        }

        const actionIndex = normalized.findIndex(
            (column) => (column.id ?? column.accessorKey) === 'aksi',
        );
        const insertAt = actionIndex < 0 ? normalized.length : actionIndex;

        return [
            ...normalized.slice(0, insertAt),
            updatedAtColumn,
            ...normalized.slice(insertAt),
        ];
    }, [columns]);

    useEffect(() => {
        const timer = setTimeout(() => {
            setGlobalFilter(searchInput.trim());
            setPagination((current) =>
                current.pageIndex === 0 ? current : { ...current, pageIndex: 0 },
            );
        }, 300);
        return () => clearTimeout(timer);
    }, [searchInput]);

    const fetchData = useCallback(async (signal?: AbortSignal) => {
        setLoading(true);
        setError(null);

        const params = new URLSearchParams();
        params.set('draw', String(Date.now()));
        params.set('start', String(pagination.pageIndex * pagination.pageSize));
        params.set('length', String(pagination.pageSize));
        params.set('search[value]', globalFilter);
        params.set('search[regex]', 'false');

        displayColumns.forEach((column, index) => {
            const key = column.accessorKey ?? column.id ?? String(index);
            params.set(`columns[${index}][data]`, key);
            params.set(`columns[${index}][name]`, key);
            params.set(`columns[${index}][searchable]`, column.enableGlobalFilter === false ? 'false' : 'true');
            params.set(`columns[${index}][orderable]`, column.enableSorting === false ? 'false' : 'true');
            params.set(`columns[${index}][search][value]`, '');
            params.set(`columns[${index}][search][regex]`, 'false');
        });

        if (sorting[0]) {
            const columnIndex = displayColumns.findIndex(
                (column) => (column.id ?? column.accessorKey) === sorting[0].id,
            );
            if (columnIndex >= 0) {
                params.set('order[0][column]', String(columnIndex));
                params.set('order[0][dir]', sorting[0].desc ? 'desc' : 'asc');
            }
        }

        Object.entries(extraParams).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                params.set(key, String(value));
            }
        });

        try {
            const { data: response } = await axios.get(url, {
                signal,
                params,
                headers: { Accept: 'application/json' },
            });
            setData(response.data ?? []);
            setTotal(response.recordsTotal ?? 0);
            setFiltered(response.recordsFiltered ?? response.recordsTotal ?? 0);
        } catch (requestError) {
            if (axios.isCancel(requestError)) return;
            setError(
                requestError?.response?.data?.message ||
                    requestError.message ||
                    'Data gagal dimuat.',
            );
            setData([]);
        } finally {
            if (!signal?.aborted) setLoading(false);
        }
    }, [url, displayColumns, pagination, sorting, globalFilter, extraKey, refreshKey]);

    useEffect(() => {
        const controller = new AbortController();
        fetchData(controller.signal);
        return () => controller.abort();
    }, [fetchData]);

    const pageCount = Math.max(1, Math.ceil(filtered / pagination.pageSize) || 1);
    const table = useReactTable({
        data,
        columns: displayColumns,
        pageCount,
        state: { pagination, sorting, globalFilter },
        onPaginationChange: setPagination,
        onSortingChange: setSorting,
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
        getCoreRowModel: getCoreRowModel(),
    });

    const from = filtered === 0 ? 0 : pagination.pageIndex * pagination.pageSize + 1;
    const to = Math.min((pagination.pageIndex + 1) * pagination.pageSize, filtered);

    const pageButtons = useMemo(() => {
        const current = pagination.pageIndex;
        const last = pageCount - 1;
        const set = new Set(
            [0, last, current - 1, current, current + 1].filter(
                (page) => page >= 0 && page <= last,
            ),
        );
        return [...set].sort((a, b) => a - b);
    }, [pagination.pageIndex, pageCount]);

    const stickyColumnId = useMemo(() => {
        const column = displayColumns.find((candidate) => {
            const id = candidate.id ?? candidate.accessorKey;
            return !['DT_RowIndex', 'aksi'].includes(id) && candidate.meta?.sticky !== false;
        });
        return column?.id ?? column?.accessorKey ?? null;
    }, [displayColumns]);

    const sortIcon = (columnId) => {
        const sort = sorting.find((item) => item.id === columnId);
        if (!sort) return 'bx-sort-alt-2';
        return sort.desc ? 'bx-sort-down' : 'bx-sort-up';
    };

    const paginationButton = (active, disabled = false) =>
        [
            'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-2 text-[13px] font-semibold tabular-nums transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-1',
            active
                ? 'border-primary bg-primary text-white'
                : 'border-hairline bg-canvas text-charcoal hover:border-primary/35 hover:bg-primary-mist/50 hover:text-primary-pressed',
            disabled ? 'pointer-events-none opacity-40' : '',
        ].join(' ');

    const noRows = !error && !loading && data.length === 0;

    return (
        <section className={`admin-table-frame ${className}`} aria-busy={loading}>
            <div className="admin-table-toolbar">
                <label className="relative min-w-0 flex-1 sm:max-w-sm">
                    <span className="sr-only">Cari dalam tabel</span>
                    <i className="bx bx-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-stone" aria-hidden />
                    <input
                        type="search"
                        value={searchInput}
                        onChange={(event) => setSearchInput(event.target.value)}
                        placeholder={searchPlaceholder}
                        className="admin-search-input"
                    />
                    {searchInput && (
                        <button
                            type="button"
                            onClick={() => setSearchInput('')}
                            className="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-steel hover:bg-surface hover:text-ink"
                            aria-label="Hapus pencarian"
                        >
                            <i className="bx bx-x text-lg" aria-hidden />
                        </button>
                    )}
                </label>

                {toolbarFilters}
                <div className="admin-table-count" aria-live="polite">
                    <span className={`h-1.5 w-1.5 rounded-full ${loading ? 'animate-pulse bg-secondary' : 'bg-primary'}`} />
                    <span>{loading ? 'Memuat' : `${filtered.toLocaleString('id-ID')} ${dataLabel}`}</span>
                </div>
            </div>

            <div className="admin-table-scroll relative">
                {loading && data.length > 0 && (
                    <div className="pointer-events-none absolute inset-0 z-20 bg-[#FCFBF7]/55 backdrop-blur-[1px]" />
                )}

                <table className="admin-table w-full text-sm">
                    <thead>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <tr key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    const canSort = header.column.getCanSort();
                                    const alignment = (header.column.columnDef.meta as any)?.align ?? 'left';
                                    const sticky = header.column.id === stickyColumnId;
                                    return (
                                        <th
                                            key={header.id}
                                            scope="col"
                                            aria-sort={header.column.getIsSorted() === 'asc' ? 'ascending' : header.column.getIsSorted() === 'desc' ? 'descending' : undefined}
                                            className={`${alignClass[alignment]} ${sticky ? 'admin-sticky-cell' : ''}`}
                                        >
                                            {canSort ? (
                                                <button
                                                    type="button"
                                                    onClick={header.column.getToggleSortingHandler()}
                                                    className={`inline-flex items-center gap-1.5 rounded-sm text-inherit hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary ${alignment === 'right' ? 'justify-end' : alignment === 'center' ? 'justify-center' : 'justify-start'}`}
                                                >
                                                    {header.isPlaceholder
                                                        ? null
                                                        : flexRender(header.column.columnDef.header, header.getContext())}
                                                    <i
                                                        className={`bx ${sortIcon(header.column.id)} text-base ${sorting.some((item) => item.id === header.column.id) ? 'text-primary opacity-100' : 'opacity-35'}`}
                                                        aria-hidden
                                                    />
                                                </button>
                                            ) : header.isPlaceholder ? null : (
                                                flexRender(header.column.columnDef.header, header.getContext())
                                            )}
                                        </th>
                                    );
                                })}
                            </tr>
                        ))}
                    </thead>
                    <tbody>
                        {error && (
                            <tr>
                                <td colSpan={displayColumns.length} className="admin-table-state">
                                    <i className="bx bx-error-circle text-2xl text-[#A43B2F]" aria-hidden />
                                    <p className="font-semibold text-ink">Data gagal dimuat</p>
                                    <p className="text-sm text-steel">{error}</p>
                                    <button type="button" onClick={() => fetchData()} className="mt-2 text-sm font-semibold text-primary hover:underline">
                                        Coba lagi
                                    </button>
                                </td>
                            </tr>
                        )}
                        {!error && loading && data.length === 0 && (
                            <tr>
                                <td colSpan={displayColumns.length} className="admin-table-state">
                                    <i className="bx bx-loader-alt animate-spin text-2xl text-primary" aria-hidden />
                                    <p className="font-semibold text-ink">Memuat data</p>
                                </td>
                            </tr>
                        )}
                        {noRows && (
                            <tr>
                                <td colSpan={displayColumns.length} className="admin-table-state">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary-mist text-primary">
                                        <i className={`bx ${globalFilter ? 'bx-search' : 'bx-data'} text-xl`} aria-hidden />
                                    </span>
                                    <p className="font-semibold text-ink">{globalFilter ? 'Tidak ada hasil' : emptyTitle}</p>
                                    <p className="max-w-sm text-sm text-steel">
                                        {globalFilter ? `Tidak ada data yang cocok dengan “${globalFilter}”.` : emptyDescription}
                                    </p>
                                </td>
                            </tr>
                        )}
                        {table.getRowModel().rows.map((row) => (
                            <tr key={row.id}>
                                {row.getVisibleCells().map((cell) => {
                                    const alignment = (cell.column.columnDef.meta as any)?.align ?? 'left';
                                    const sticky = cell.column.id === stickyColumnId;
                                    return (
                                        <td
                                            key={cell.id}
                                            className={`${alignClass[alignment]} ${sticky ? 'admin-sticky-cell' : ''}`}
                                        >
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <footer className="admin-table-footer">
                <p className="text-[13px] text-steel">
                    {filtered === 0 ? (
                        '0 entri'
                    ) : (
                        <>
                            <span className="font-semibold text-charcoal">{from}–{to}</span> dari{' '}
                            <span className="font-semibold text-charcoal">{filtered.toLocaleString('id-ID')}</span>
                        </>
                    )}
                    {total !== filtered && (
                        <span className="text-stone"> · {total.toLocaleString('id-ID')} total</span>
                    )}
                </p>

                <div className="flex w-full flex-wrap items-center justify-between gap-3 sm:w-auto sm:justify-end">
                    <label className="flex items-center gap-2 text-xs font-medium text-steel">
                        Per halaman
                        <select
                            value={pagination.pageSize}
                            onChange={(event) =>
                                setPagination({ pageIndex: 0, pageSize: Number(event.target.value) })
                            }
                            className="h-9 rounded-md border-hairline bg-canvas py-0 pl-2.5 pr-8 text-[13px] font-semibold text-charcoal focus:border-primary focus:ring-primary"
                        >
                            {[10, 25, 50, 100].map((size) => (
                                <option key={size} value={size}>{size}</option>
                            ))}
                        </select>
                    </label>

                    <nav className="flex items-center gap-1" aria-label="Paginasi tabel">
                        <button
                            type="button"
                            className={paginationButton(false, !table.getCanPreviousPage() || loading)}
                            onClick={() => table.previousPage()}
                            disabled={!table.getCanPreviousPage() || loading}
                            aria-label="Halaman sebelumnya"
                        >
                            <i className="bx bx-chevron-left text-lg" aria-hidden />
                        </button>
                        {pageButtons.map((page, index) => {
                            const previous = pageButtons[index - 1];
                            const showGap = previous !== undefined && page - previous > 1;
                            const active = page === pagination.pageIndex;
                            return (
                                <span key={page} className="contents">
                                    {showGap && <span className="px-1 text-stone">…</span>}
                                    <button
                                        type="button"
                                        className={paginationButton(active, loading)}
                                        onClick={() => setPagination((current) => ({ ...current, pageIndex: page }))}
                                        disabled={loading}
                                        aria-current={active ? 'page' : undefined}
                                        aria-label={`Halaman ${page + 1}`}
                                    >
                                        {page + 1}
                                    </button>
                                </span>
                            );
                        })}
                        <button
                            type="button"
                            className={paginationButton(false, !table.getCanNextPage() || loading)}
                            onClick={() => table.nextPage()}
                            disabled={!table.getCanNextPage() || loading}
                            aria-label="Halaman berikutnya"
                        >
                            <i className="bx bx-chevron-right text-lg" aria-hidden />
                        </button>
                    </nav>
                </div>
            </footer>
        </section>
    );
}
