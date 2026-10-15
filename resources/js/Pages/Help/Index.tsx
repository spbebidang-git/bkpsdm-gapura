import App from '@/Layouts/App';
import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import '../../../css/public-help.css';

export default function HelpIndex() {
    const { helps = [] } = usePage().props;
    const [query, setQuery] = useState('');

    const normalizedQuery = query.trim().toLocaleLowerCase('id-ID');
    const filteredHelps = useMemo(() => {
        if (!normalizedQuery) return helps;

        return helps.filter((item) =>
            `${item.question || ''} ${item.answer || ''}`
                .toLocaleLowerCase('id-ID')
                .includes(normalizedQuery)
        );
    }, [helps, normalizedQuery]);

    const resultLabel = normalizedQuery
        ? `${filteredHelps.length} hasil ditemukan`
        : `${helps.length} pertanyaan tersedia`;

    return (
        <App>
            <Head title="Bantuan" />

            <section className="mx-auto min-h-[70vh] w-full max-w-[1320px]">
                <div className="overflow-hidden bg-white">
                    <header className="public-help-hero">
                        <div className="public-help-hero-inner">
                            <nav aria-label="Breadcrumb" className="mb-6 flex items-center gap-2 font-poppins text-xs text-[#BDE8E2]">
                                <Link href="/" className="hover:text-white">Beranda</Link>
                                <i className="bx bx-chevron-right" aria-hidden="true" />
                                <span aria-current="page" className="text-white">Pusat bantuan</span>
                            </nav>
                            <h1 className="font-poppins text-3xl font-semibold leading-tight text-white sm:text-4xl lg:text-[40px]">Pusat bantuan GAPURA</h1>
                            <p className="mt-4 max-w-xl font-poly text-base leading-7 text-[#DFF7F3]">Cari jawaban seputar akun, kelas, materi pembelajaran, dan sertifikat di GAPURA.</p>
                        </div>
                    </header>

                    {/* Pencarian — surface tint, focus ring primary */}
                    <div className="border-y border-[#e6efee] bg-[#f8fbfa] px-6 py-6 sm:px-9 lg:px-12">
                        <label className="block" htmlFor="help-search">
                            <span className="mb-2 block font-inter text-[11px] font-semibold uppercase tracking-[0.06em] text-[#66716c]">
                                Cari di pusat bantuan
                            </span>
                            <span className="relative block">
                                <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <box-icon name="search" color="#94a3a0" size="21px" aria-hidden="true" />
                                </span>
                                <input
                                    id="help-search"
                                    type="search"
                                    value={query}
                                    onChange={(event) => setQuery(event.target.value)}
                                    placeholder="Contoh: cara mengikuti kelas"
                                    autoComplete="off"
                                    className="min-h-12 w-full rounded-[10px] border border-[#d5e4e0] bg-white py-3 pl-12 pr-12 font-inter text-sm text-[#173b3a] shadow-[0_1px_3px_rgba(1,128,131,0.05)] transition duration-200 placeholder:text-[#94a3a0] hover:border-primary/40 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10"
                                />
                                {query && (
                                    <button
                                        type="button"
                                        onClick={() => setQuery('')}
                                        className="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-[10px] text-[#94a3a0] transition hover:text-[#173b3a] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary"
                                        aria-label="Hapus pencarian"
                                    >
                                        <box-icon name="x" color="currentColor" size="22px" aria-hidden="true" />
                                    </button>
                                )}
                            </span>
                        </label>
                    </div>

                    <div className="px-6 py-8 sm:px-9 lg:px-12">
                        <div className="mb-5 flex min-h-9 items-end justify-between gap-4">
                            <div>
                                <div className="mb-2 flex items-center gap-3">
                                    <span
                                        className="h-5 w-1 rounded-full bg-gradient-to-b from-primary to-[#2d83b6]"
                                        aria-hidden="true"
                                    />
                                    <h2 id="faq-heading" className="font-poppins text-xl font-bold tracking-tight text-[#173b3a] sm:text-2xl">
                                        Pertanyaan umum
                                    </h2>
                                </div>
                                <p role="status" aria-live="polite" className="font-inter text-sm text-[#66716c]">
                                    {resultLabel}
                                </p>
                            </div>
                            {normalizedQuery && (
                                <button
                                    type="button"
                                    onClick={() => setQuery('')}
                                    className="shrink-0 rounded-[10px] border border-[#d5e4e0] bg-white px-3.5 py-2 font-inter text-sm font-semibold text-primary transition duration-200 hover:border-primary/40 hover:bg-primary-mist/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
                                >
                                    Reset pencarian
                                </button>
                            )}
                        </div>

                        {filteredHelps.length > 0 ? (
                            <div className="overflow-hidden rounded-[15px] border border-[#e6efee] bg-white shadow-[0_2px_16px_rgba(1,128,131,0.06)]">
                                {filteredHelps.map((item) => (
                                    <details key={item.id} className="group border-b border-[#e6efee] last:border-b-0">
                                        <summary className="flex min-h-16 cursor-pointer list-none items-center gap-4 px-5 py-4 text-left outline-none marker:hidden transition duration-200 hover:bg-[#f8fbfa] focus-visible:bg-primary-mist focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary sm:px-6">
                                            <span className="flex-1 font-poppins text-[15px] font-semibold leading-6 text-[#173b3a] sm:text-base">
                                                {item.question}
                                            </span>
                                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-primary-mist transition duration-300 group-open:rotate-180 group-open:bg-primary">
                                                <box-icon name="chevron-down" color="#018083" size="20px" class="group-open:hidden" aria-hidden="true" />
                                                <box-icon name="chevron-down" color="#FFFFFF" size="20px" class="hidden group-open:block" aria-hidden="true" />
                                            </span>
                                        </summary>
                                        <div className="border-t border-[#e6efee] bg-[#f8fbfa] px-5 py-5 font-inter text-sm leading-7 text-[#66716c] whitespace-pre-line sm:px-6">
                                            {item.answer}
                                        </div>
                                    </details>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-[15px] border border-dashed border-[#cfe0dd] bg-[#f8fbfa] px-6 py-14 text-center">
                                <span className="mx-auto grid h-14 w-14 place-items-center rounded-[15px] bg-white shadow-[0_2px_16px_rgba(1,128,131,0.08)]">
                                    <box-icon
                                        name={normalizedQuery ? 'search-alt-2' : 'message-rounded-dots'}
                                        color="#018083"
                                        size="25px"
                                        aria-hidden="true"
                                    />
                                </span>
                                <h3 className="mt-4 font-poppins text-lg font-bold text-[#173b3a]">
                                    {normalizedQuery ? 'Jawaban belum ditemukan' : 'Pertanyaan bantuan belum tersedia'}
                                </h3>
                                <p className="mx-auto mt-2 max-w-md font-poly text-sm leading-6 text-[#66716c]">
                                    {normalizedQuery
                                        ? `Kami tidak menemukan hasil untuk “${query.trim()}”. Coba gunakan kata kunci yang lebih singkat.`
                                        : 'Daftar pertanyaan dan jawaban akan tampil di sini setelah tersedia.'}
                                </p>
                                {normalizedQuery && (
                                    <button
                                        type="button"
                                        onClick={() => setQuery('')}
                                        className="mt-6 rounded-[10px] bg-gradient-to-r from-primary to-[#015f61] px-5 py-3 text-sm font-semibold text-white shadow-[0_4px_16px_rgba(1,128,131,0.25)] transition duration-300 hover:opacity-90 active:scale-[0.97]"
                                    >
                                        Lihat semua pertanyaan
                                    </button>
                                )}
                            </div>
                        )}

                        <aside
                            className="mt-8 flex flex-col gap-4 rounded-[15px] border border-primary/15 bg-primary-mist/70 p-5 shadow-[0_2px_16px_rgba(1,128,131,0.06)] sm:flex-row sm:items-center sm:justify-between sm:p-6"
                            aria-labelledby="contact-heading"
                        >
                            <div className="flex items-start gap-4">
                                <span className="grid h-10 w-10 shrink-0 place-items-center rounded-[10px] bg-white shadow-[0_2px_16px_rgba(1,128,131,0.10)]">
                                    <box-icon name="support" color="#018083" size="23px" aria-hidden="true" />
                                </span>
                                <div>
                                    <h2 id="contact-heading" className="font-poppins font-bold text-[#173b3a]">
                                        Masih membutuhkan bantuan?
                                    </h2>
                                    <p className="mt-1 font-inter text-sm leading-6 text-[#66716c]">
                                        Hubungi BKPSDM Gresik melalui kontak di bagian bawah halaman.
                                    </p>
                                </div>
                            </div>
                            <a
                                href="#site-contact"
                                className="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 self-start rounded-[10px] border border-primary/25 bg-white px-4 text-sm font-semibold text-primary shadow-[0_1px_3px_rgba(1,128,131,0.05)] transition duration-200 hover:border-primary/40 hover:bg-white/70 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/20 sm:self-auto"
                            >
                                Lihat kontak
                                <box-icon name="down-arrow-alt" color="#018083" size="19px" aria-hidden="true" />
                            </a>
                        </aside>
                    </div>
                </div>
            </section>
        </App>
    );
}
