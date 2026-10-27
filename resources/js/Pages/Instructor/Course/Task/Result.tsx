import {
    btnLink,
    btnSecondarySm,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
    tableClass,
    tableFrame,
    tableScroller,
} from '@/Components/Admin/ui';
import App from '@/Layouts/Admin/App';
import { Link } from '@inertiajs/react';

export default function Result({ slug, activity, tasks = [] }) {
    return (
        <App title="Hasil Tugas">
            <div className="space-y-5">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Kelas · Tugas</p>
                        <h1 className={pageTitle}>Hasil tugas</h1>
                        <p className={pageDescription}>
                            Berkas yang dikumpulkan pengguna{activity?.title ? ` untuk ${activity.title}` : ''}.
                        </p>
                    </div>
                    <Link href={route('admin.course.activities.index', { course: slug })} className={btnSecondarySm}>Kembali</Link>
                </div>

                <div className={tableFrame}>
                    <div className={tableScroller}>
                        <table className={`${tableClass} min-w-[620px]`}>
                            <thead>
                                <tr>
                                    <th className="admin-sticky-cell text-left">Nama pengguna</th>
                                    <th className="text-left">NIP</th>
                                    <th className="text-left">Berkas</th>
                                </tr>
                            </thead>
                            <tbody>
                                {tasks.length > 0 ? tasks.map((item, index) => (
                                    <tr key={item.id ?? `${item.nip}-${index}`}>
                                        <td className="admin-sticky-cell font-semibold text-ink">{item.name || '—'}</td>
                                        <td className="tabular-nums">{item.nip || '—'}</td>
                                        <td>
                                            {item.file ? (
                                                <a href={item.file} download className={btnLink}>Unduh berkas</a>
                                            ) : <span className="text-stone">Belum ada</span>}
                                        </td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan={3} className="admin-table-state">
                                            <i className="bx bx-file-blank text-2xl text-stone" aria-hidden />
                                            <p className="font-semibold text-ink">Belum ada hasil tugas</p>
                                            <p className="text-sm text-steel">Berkas pengguna akan tampil setelah dikumpulkan.</p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </App>
    );
}
