import {
    badgeInfo,
    btnLink,
    btnSecondarySm,
    cardPad,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
    tableClass,
    tableScroller,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

function fmtSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function fmtTime(value: string): string {
    if (!value) return 'Waktu tidak tersedia';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime()) || !/[TZ]|[+-]\d{2}:\d{2}$/.test(value)) return value;
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'medium', timeZone: 'Asia/Jakarta' }).format(parsed) + ' WIB';
}

function BackupTable({ title, rows }) {
    if (!rows?.length) {
        return (
            <div className={cardPad}>
                <h3 className="text-sm font-semibold text-[#003C52]">{title}</h3>
                <p className="mt-2 text-sm text-[#8496B0]">Belum ada backup.</p>
            </div>
        );
    }

    return (
        <div className={cardPad}>
            <h3 className="text-sm font-semibold text-[#003C52]">{title}</h3>
            <div className={`mt-3 ${tableScroller}`}>
                <table className={`${tableClass} min-w-[520px] text-left`}>
                    <thead>
                        <tr className="border-b border-hairline text-[#8496B0]">
                            <th className="py-2 pr-3 font-medium">File</th>
                            <th className="py-2 pr-3 font-medium">Ukuran</th>
                            <th className="py-2 pr-3 font-medium">Waktu</th>
                            <th className="py-2 font-medium" />
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={`${row.type}-${row.name}`} className="border-b border-hairline/60">
                                <td className="py-2 pr-3 font-mono text-xs text-[#003C52]">{row.name}</td>
                                <td className="py-2 pr-3 tabular-nums text-[#526176]">{fmtSize(row.size)}</td>
                                <td className="py-2 pr-3 tabular-nums text-[#526176]">{row.modified}</td>
                                <td className="py-2 text-right">
                                    <a
                                        className={btnLink}
                                        href={route('admin.management.log.download', {
                                            type: row.type,
                                            file: row.name,
                                        })}
                                    >
                                        Unduh
                                    </a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

type Entry = {
    timestamp: string;
    level: string;
    message: string;
    request_id: string;
    process_id: string;
    context: Record<string, unknown>;
    legacy: boolean;
};

function Correlation({ label, value }: { label: string; value: string }) {
    const [copied, setCopied] = useState(false);
    const [failed, setFailed] = useState(false);
    if (!value) return null;
    return <button type="button" className="max-w-full break-all text-left font-mono text-xs text-sky-700 underline" title={`Salin ${label}`}
        onClick={async () => {
            try { await navigator.clipboard.writeText(value); setCopied(true); setFailed(false); }
            catch { setFailed(true); }
        }}>{label}: {value}{copied ? ' · Tersalin' : failed ? ' · Pilih teks untuk menyalin' : ''}</button>;
}

export default function Index({ logFiles, backups, selected, entries, reading, filters, retention, backupStatus }) {
    const [search, setSearch] = useState(filters.q);
    const [requestId, setRequestId] = useState(filters.request_id);
    const [level, setLevel] = useState(filters.level);
    const [filterError, setFilterError] = useState('');
    const load = (file = selected) => {
        if (requestId && !/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(requestId)) {
            setFilterError('ID permintaan harus berupa UUID yang valid.');
            return;
        }
        setFilterError('');
        router.get(route('admin.management.log.index'), { file, level, q: search, request_id: requestId }, { preserveState: true, preserveScroll: true, replace: true });
    };
    const selectFile = (name: string) => load(name);

    return (
        <AdminLayout title="Log & Backup" hideTitle>
            <Head title="Log & Backup" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Sistem</p>
                        <h1 className={pageTitle}>Log dan backup</h1>
                        <p className={pageDescription}>
                            Log aktif disimpan {retention.log_days} hari; maksimum {retention.log_backup_keep} arsip terenkripsi. Backup basis data: {retention.db_daily_keep} harian dan {retention.db_weekly_keep} mingguan.
                        </p>
                    </div>
                </div>

                {(!backupStatus?.encryptionConfigured) && (
                    <div role="status" className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        Kunci enkripsi backup belum dikonfigurasi. Lengkapi kunci enkripsi, lalu verifikasi hasil backup dan pemulihannya.
                    </div>
                )}

                {!backupStatus?.offsiteConfigured && (
                    <p className="text-sm text-slate-600">Backup disimpan di server aplikasi pada storage/app/backups.</p>
                )}

                <div className="flex flex-wrap gap-2">
                    <span className={badgeInfo}>logs:backup 01:30</span>
                    <span className={badgeInfo}>db:backup 02:00</span>
                    <span className={badgeInfo}>db:backup --weekly Senin 03:00</span>
                </div>

                <div className="grid gap-5 lg:grid-cols-3">
                    <div className={`${cardPad} lg:col-span-1`}>
                        <h3 className="text-sm font-semibold text-[#003C52]">File log</h3>
                        <ul className="mt-3 max-h-[28rem] space-y-1 overflow-y-auto">
                            {logFiles.length === 0 && (
                                <li className="text-sm text-[#8496B0]">Belum ada file log.</li>
                            )}
                            {logFiles.map((f) => (
                                <li key={f.name}>
                                    <button
                                        type="button"
                                        onClick={() => selectFile(f.name)}
                                        title={f.name}
                                        className={`flex w-full items-center justify-between rounded-md px-2.5 py-2 text-left text-sm ${
                                            selected === f.name
                                ? 'bg-primary-mist text-primary-pressed'
                                                : 'text-[#526176] hover:bg-surface'
                                        }`}
                                    >
                                        <span className="truncate font-mono text-xs">{f.name}</span>
                                        <span className="ml-2 shrink-0 tabular-nums text-xs text-[#8496B0]">
                                            {fmtSize(f.size)}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                        {selected && (
                            <a
                                className={`${btnSecondarySm} mt-3 w-full`}
                                href={route('admin.management.log.download', {
                                    type: 'log',
                                    file: selected,
                                })}
                            >
                                Unduh log tersaring
                            </a>
                        )}
                    </div>

                    <div className={`${cardPad} lg:col-span-2`}>
                        <div className="flex items-center justify-between gap-2">
                            <h3 className="text-sm font-semibold text-[#003C52]">
                                Isi log · {selected || 'Belum dipilih'}
                            </h3>
                            <span className="text-xs text-[#8496B0]">Data sensitif disamarkan</span>
                        </div>
                        <form className="mt-4 grid gap-2 sm:grid-cols-2" onSubmit={(event) => { event.preventDefault(); load(); }}>
                            <label className="text-xs text-slate-600">Tingkat log
                                <select className="mt-1 block w-full rounded-md border-slate-300 text-sm" value={level} onChange={(event) => setLevel(event.target.value)}>
                                    <option value="">Semua tingkat</option>
                                    {['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'legacy', 'unknown'].map((item) => <option key={item} value={item}>{item.toUpperCase()}</option>)}
                                </select>
                            </label>
                            <label className="text-xs text-slate-600">Cari pesan atau konteks
                                <input className="mt-1 block w-full rounded-md border-slate-300 text-sm" maxLength={200} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Misalnya certificate.push.completed" />
                            </label>
                            <label className="text-xs text-slate-600">ID permintaan
                                <input className="mt-1 block w-full rounded-md border-slate-300 text-sm" maxLength={36} value={requestId} onChange={(event) => setRequestId(event.target.value)} placeholder="UUID permintaan" />
                            </label>
                            <button type="submit" className={`${btnSecondarySm} self-end`}>Terapkan / Muat ulang</button>
                            {filterError && <p role="alert" className="text-xs text-red-700 sm:col-span-2">{filterError}</p>}
                        </form>
                        <p className="mt-3 text-xs text-slate-500" role="status">
                            Pencarian terbatas pada {fmtSize(reading.max_bytes)} terakhir; maksimum {reading.max_entries} kejadian, terbaru di atas.
                            {reading.truncated && ' Sebagian log tidak ditampilkan karena batas pembacaan.'}
                        </p>
                        <div className="mt-3 max-h-[36rem] space-y-2 overflow-auto" aria-label="Kejadian log">
                            {!reading.available ? <p className="p-4 text-sm text-slate-500">File tidak tersedia.</p>
                                : entries.length === 0 ? <p className="p-4 text-sm text-slate-500">Tidak ada kejadian yang cocok dalam batas pembacaan.</p>
                                : entries.map((entry: Entry, index: number) => <article key={index} className="rounded-md border border-slate-200 p-3">
                                    <div className="flex flex-wrap items-center gap-2 text-xs">
                                        <time className="text-slate-500" title={entry.timestamp}>{fmtTime(entry.timestamp)}</time>
                                        <span className={`rounded px-2 py-0.5 font-semibold ${['error', 'critical', 'alert', 'emergency'].includes(entry.level) ? 'bg-red-100 text-red-800' : entry.level === 'warning' ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-700'}`}>{entry.level.toUpperCase()}</span>
                                        {entry.legacy && <span className="text-slate-500">Format lama</span>}
                                    </div>
                                    <p className="mt-2 whitespace-pre-wrap break-words text-sm text-slate-800">{entry.message}</p>
                                    <div className="mt-2 flex flex-col items-start gap-1">
                                        <Correlation label="Request" value={entry.request_id} />
                                        <Correlation label="Proses" value={entry.process_id} />
                                    </div>
                                    {Object.keys(entry.context).length > 0 && <details className="mt-2 text-xs">
                                        <summary className="cursor-pointer text-slate-600">Konteks tersaring</summary>
                                        <pre className="mt-2 overflow-auto whitespace-pre-wrap break-all rounded bg-slate-950 p-3 text-slate-100">{JSON.stringify(entry.context, null, 2)}</pre>
                                    </details>}
                                </article>)}
                        </div>
                    </div>
                </div>

                <div className="grid gap-5 lg:grid-cols-3">
                    <BackupTable title="Arsip log terenkripsi (.age)" rows={backups.logs} />
                    <BackupTable title="Backup DB harian" rows={backups.daily} />
                    <BackupTable title="Backup DB mingguan" rows={backups.weekly} />
                    <BackupTable title="Backup berkas terenkripsi" rows={backups.files} />
                </div>
            </div>
        </AdminLayout>
    );
}
