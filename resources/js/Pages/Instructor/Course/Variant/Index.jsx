import { confirmDelete } from '@/Components/Admin/ConfirmDelete';
import {
    badgeInfo,
    badgeOk,
    badgePending,
    btnDanger,
    btnEdit,
    btnPrimarySm,
    btnSecondarySm,
    btnXs,
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

export default function Index({ slug, activity, variants = [], variantWarning }) {
    return (
        <App title="Variasi Soal">
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Kelas · Pertanyaan</p>
                        <h1 className={pageTitle}>Variasi soal</h1>
                        <p className={pageDescription}>Susun beberapa paket soal dengan jumlah pertanyaan yang seimbang.</p>
                    </div>
                    <Link href={`/admin/course/${slug}`} className={btnSecondarySm}>Kembali</Link>
                </div>

                <section className={`${card} overflow-hidden`} aria-labelledby="activity-detail-title">
                    <header className="border-b border-hairline px-5 py-4">
                        <h2 id="activity-detail-title" className="font-semibold text-ink">Kegiatan</h2>
                    </header>
                    <dl className="grid gap-4 px-5 py-4 md:grid-cols-[minmax(180px,0.7fr)_minmax(0,1.3fr)]">
                        <div>
                            <dt className="text-xs font-semibold uppercase tracking-[0.08em] text-stone">Judul</dt>
                            <dd className="mt-1 text-sm font-semibold text-ink">{activity.title || '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-xs font-semibold uppercase tracking-[0.08em] text-stone">Deskripsi</dt>
                            <dd className="mt-1 text-sm leading-6 text-charcoal">{activity.description || '—'}</dd>
                        </div>
                    </dl>
                </section>

                {variantWarning?.hasEmpty && (
                    <div className="flex items-start gap-3 rounded-lg border border-[#E9BEB8] bg-[#FFF4F1] p-4 text-sm text-[#8E342A]" role="alert">
                        <i className="bx bx-error-circle mt-0.5 text-lg" aria-hidden />
                        <div>
                            <p className="font-semibold">Ada variasi tanpa soal</p>
                            <p className="mt-1 leading-5">{variantWarning.emptyVariants.join(', ')} akan dilewati saat tes.</p>
                        </div>
                    </div>
                )}
                {variantWarning?.hasMismatch && (
                    <div className="flex items-start gap-3 rounded-lg border border-[#F0CAA1] bg-[#FFF4E8] p-4 text-sm text-[#7A3C05]" role="alert">
                        <i className="bx bx-error mt-0.5 text-lg" aria-hidden />
                        <div>
                            <p className="font-semibold">Jumlah soal belum sama</p>
                            <p className="mt-1 leading-5">Samakan jumlah pertanyaan pada setiap variasi.</p>
                        </div>
                    </div>
                )}

                <section className={`${card} overflow-hidden`} aria-labelledby="variant-list-title">
                    <header className="flex flex-col gap-3 border-b border-hairline px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 id="variant-list-title" className="font-semibold text-ink">Daftar variasi</h2>
                            <p className="mt-1 text-sm text-steel">{variants.length.toLocaleString('id-ID')} variasi tersimpan.</p>
                        </div>
                        <Link href={`/admin/course/${slug}/activity/variant/${activity.activity_id}/create`} className={btnPrimarySm}>
                            <i className="bx bx-plus text-base" aria-hidden />
                            Variasi baru
                        </Link>
                    </header>
                    <div className={tableScroller}>
                        <table className={`${tableClass} min-w-[780px]`}>
                            <thead>
                                <tr>
                                    <th className="admin-sticky-cell text-left">Variasi</th>
                                    <th className="text-left">Kode</th>
                                    <th className="text-right">Jumlah soal</th>
                                    <th className="text-left">Acak soal</th>
                                    <th className="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {variants.length > 0 ? variants.map((item, index) => (
                                    <tr key={item.id ?? index}>
                                        <td className="admin-sticky-cell font-semibold text-ink">{item.variant || '—'}</td>
                                        <td><span className={badgeInfo}>{item.code || '—'}</span></td>
                                        <td className="text-right tabular-nums">{item.question_count ?? 0}</td>
                                        <td><span className={item.mix ? badgeOk : badgePending}>{item.mix ? 'Ya' : 'Tidak'}</span></td>
                                        <td>
                                            <div className="flex flex-wrap justify-end gap-2">
                                                <Link href={`/admin/course/${slug}/activity/variant/${activity.activity_id}/${item.id}/question-item`} className={btnXs}>Atur soal</Link>
                                                <Link href={`/admin/course/${slug}/activity/variant/${activity.activity_id}/${item.id}/edit`} className={btnEdit}>Edit</Link>
                                                <button
                                                    type="button"
                                                    onClick={() => confirmDelete(`/admin/course/${slug}/activity/variant/${activity.activity_id}/${item.id}`)}
                                                    className={btnDanger}
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan={5} className="admin-table-state">
                                            <i className="bx bx-layer text-2xl text-stone" aria-hidden />
                                            <p className="font-semibold text-ink">Belum ada variasi</p>
                                            <p className="text-sm text-steel">Tambahkan paket soal pertama untuk kegiatan ini.</p>
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
