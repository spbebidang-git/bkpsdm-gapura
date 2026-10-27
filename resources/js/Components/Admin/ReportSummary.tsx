export type ReportSummaryData = {
    total_participants?: number; total_courses?: number; total_enrollments?: number;
    completed?: number; in_progress?: number; not_started?: number;
    avg_pre_test?: number | null; avg_post_test?: number | null;
};

export function reportNumber(value: number | string | null | undefined) {
    return value === null || value === undefined ? '—' : Number(value).toLocaleString('id-ID', { maximumFractionDigits: 1 });
}

export function ReportProgress({ value = 0 }: { value?: number }) {
    return <div className="report-progress"><div><span style={{ width: `${Math.min(100, Math.max(0, value))}%` }} /></div><span>{reportNumber(value)}%</span></div>;
}

export default function ReportSummary({ summary, detail = false, course = false }: { summary: ReportSummaryData; detail?: boolean; course?: boolean }) {
    const cards = detail ? [
        { label: course ? 'Pengguna' : 'Kelas diikuti', value: course ? summary.total_participants : summary.total_courses, icon: 'bx-book-reader', note: 'Keikutsertaan terpilih' },
        { label: course ? 'Pengguna selesai' : 'Kelas selesai', value: summary.completed, icon: 'bx-check-circle', note: 'Pembelajaran dituntaskan' },
        { label: 'Rata-rata pre-test', value: summary.avg_pre_test, icon: 'bx-bar-chart-alt-2', note: 'Dari nilai yang tersedia' },
        { label: 'Rata-rata post-test', value: summary.avg_post_test, icon: 'bx-line-chart', note: 'Dari nilai yang tersedia' },
    ] : [
        { label: 'Pengguna', value: summary.total_participants, icon: 'bx-group', note: 'Pengguna terdaftar' },
        { label: 'Kelas', value: summary.total_courses, icon: 'bx-book-open', note: 'Kelas dengan pengguna' },
        { label: 'Keikutsertaan selesai', value: summary.completed, icon: 'bx-check-circle', note: 'Pembelajaran dituntaskan' },
        { label: 'Rata-rata post-test', value: summary.avg_post_test, icon: 'bx-line-chart', note: 'Dari nilai yang tersedia' },
    ];
    return <section className="report-summary" aria-label="Ringkasan laporan">{cards.map(card => <article key={card.label} className="report-summary-card"><span className="report-summary-icon"><i className={`bx ${card.icon}`} aria-hidden /></span><div><p>{card.label}</p><strong>{reportNumber(card.value)}</strong><small>{card.note}</small></div></article>)}</section>;
}
