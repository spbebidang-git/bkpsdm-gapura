import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import {
    badgeInfo,
    badgeOk,
    badgePending,
    btnDanger,
    btnEdit,
    btnPrimarySm,
    btnSecondarySm,
    card,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
    tableClass,
    tableScroller,
} from '@/Components/Admin/ui';
import App from '@/Layouts/Instructor/App';
import { Link } from '@inertiajs/react';

function Answer({ value, correct }) {
    return (
        <span className={`inline-flex min-w-28 rounded-md border px-2.5 py-1.5 text-xs ${correct ? 'border-[#B9DDC5] bg-[#EAF7EE] font-semibold text-[#246A3C]' : 'border-hairline bg-surface-soft text-charcoal'}`}>
            {value || '—'}
        </span>
    );
}

export default function Index({ slug, activityId, questionId, questionItems = [], activity }) {
    const backUrl = route('admin.course.activity.variant.index', [slug, activityId]);

    return (
        <App title="Daftar Soal">
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Kelas · Variasi soal</p>
                        <h1 className={pageTitle}>Daftar soal</h1>
                        <p className={pageDescription}>Tinjau pertanyaan, pilihan jawaban, dan skor dalam variasi ini.</p>
                    </div>
                    <Link href={backUrl} className={btnSecondarySm}>Kembali</Link>
                </div>

                <section className={`${card} grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4`} aria-label="Ringkasan variasi soal">
                    <div><p className="text-xs font-semibold uppercase tracking-[0.08em] text-stone">Kegiatan</p><p className="mt-1 text-sm font-semibold text-ink">{activity.title || '—'}</p></div>
                    <div><p className="text-xs font-semibold uppercase tracking-[0.08em] text-stone">Tipe</p><p className="mt-1"><span className={badgeInfo}>{activity.type || '—'}</span></p></div>
                    <div><p className="text-xs font-semibold uppercase tracking-[0.08em] text-stone">Variasi</p><p className="mt-1 text-sm font-semibold text-ink">{activity.variant || '—'}</p></div>
                    <div className="flex gap-2 sm:block"><p className="text-xs font-semibold uppercase tracking-[0.08em] text-stone">Pengaturan</p><div className="mt-1 flex gap-2"><span className={activity.is_loop ? badgeOk : badgePending}>Ulangi: {activity.is_loop ? 'Ya' : 'Tidak'}</span><span className={activity.mix ? badgeOk : badgePending}>Acak: {activity.mix ? 'Ya' : 'Tidak'}</span></div></div>
                </section>

                <section className={`${card} overflow-hidden`} aria-labelledby="question-list-title">
                    <header className="flex flex-col gap-3 border-b border-hairline px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 id="question-list-title" className="font-semibold text-ink">Pertanyaan</h2>
                            <p className="mt-1 text-sm text-steel">{questionItems.length.toLocaleString('id-ID')} soal tersimpan.</p>
                        </div>
                        <Link href={`/admin/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/create`} className={btnPrimarySm}>
                            <i className="bx bx-plus text-base" aria-hidden />
                            Soal baru
                        </Link>
                    </header>
                    <div className={tableScroller}>
                        <table className={`${tableClass} min-w-[1180px]`}>
                            <thead>
                                <tr>
                                    <th className="w-14 text-left">No.</th>
                                    <th className="admin-sticky-cell min-w-[300px] text-left">Pertanyaan</th>
                                    <th className="text-left">Opsi A</th>
                                    <th className="text-left">Opsi B</th>
                                    <th className="text-left">Opsi C</th>
                                    <th className="text-left">Opsi D</th>
                                    <th className="text-right">Skor</th>
                                    <th className="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {questionItems.length > 0 ? questionItems.map((item, index) => (
                                    <tr key={item.id ?? index}>
                                        <td className="tabular-nums text-stone">{index + 1}</td>
                                        <td className="admin-sticky-cell max-w-sm font-semibold text-ink"><span className="line-clamp-3">{item.question || '—'}</span></td>
                                        <td><Answer value={item.option1} correct={item.is_correct === item.option1} /></td>
                                        <td><Answer value={item.option2} correct={item.is_correct === item.option2} /></td>
                                        <td><Answer value={item.option3} correct={item.is_correct === item.option3} /></td>
                                        <td><Answer value={item.option4} correct={item.is_correct === item.option4} /></td>
                                        <td className="text-right font-semibold tabular-nums text-ink">{item.score ?? '—'}</td>
                                        <td>
                                            <div className="flex justify-end gap-2">
                                                <Link href={`/admin/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/${item.id}/edit`} className={btnEdit}>Edit</Link>
                                                <button type="button" onClick={() => confirmDelete(`/admin/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/${item.id}`)} className={btnDanger}>Hapus</button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan={8} className="admin-table-state">
                                            <i className="bx bx-file-blank text-2xl text-stone" aria-hidden />
                                            <p className="font-semibold text-ink">Belum ada soal</p>
                                            <p className="text-sm text-steel">Tambahkan pertanyaan pertama untuk variasi ini.</p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </App>
    );
}
