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
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';

function fmtSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
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

export default function Index({ logFiles, backups, selected, content, retention }) {
    const selectFile = (name) => {
        router.get(route('admin.management.log.index'), { file: name }, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout title="Log & Backup" hideTitle>
            <Head title="Log & Backup" />
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Sistem</p>
                        <h1 className={pageTitle}>Log dan backup</h1>
                        <p className={pageDescription}>
                            Log aktif disimpan {retention.log_days} hari; arsip {retention.log_backup_keep} hari. Backup basis data: {retention.db_daily_keep} harian dan {retention.db_weekly_keep} mingguan.
                        </p>
                    </div>
                </div>

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
                                Unduh file terpilih
                            </a>
                        )}
                    </div>

                    <div className={`${cardPad} lg:col-span-2`}>
                        <div className="flex items-center justify-between gap-2">
                            <h3 className="text-sm font-semibold text-[#003C52]">
                                Isi log · {selected || 'Belum dipilih'}
                            </h3>
                            <span className="text-xs text-[#8496B0]">200 baris terakhir, data sensitif disamarkan</span>
                        </div>
                        <pre className="mt-3 max-h-[28rem] overflow-auto rounded-md bg-[#0f172a] p-4 text-[11px] leading-5 text-[#e2e8f0]">
                            {content ?? 'File tidak ditemukan / kosong.'}
                        </pre>
                    </div>
                </div>

                <div className="grid gap-5 lg:grid-cols-3">
                    <BackupTable title="Arsip log (zip)" rows={backups.logs} />
                    <BackupTable title="Backup DB harian" rows={backups.daily} />
                    <BackupTable title="Backup DB mingguan" rows={backups.weekly} />
                </div>
            </div>
        </AdminLayout>
    );
}
