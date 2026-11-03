import ParticipantApp from "@/Layouts/Participant/App";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import "../../../css/participant-course-browse.css";

const emptyFilters = { search: "", employee_status: "", category: "", visibility: "" };

type BrowseCourse = {
    id: string;
    type: string;
    title: string;
    slug: string;
    thumbnail: string | null;
    category: string | null;
    short_description: string | null;
    employee_status?: string;
    visibility?: 'public' | 'private';
    opd_name?: string | null;
    total_participant?: number | string;
    total_passed?: number | string;
    star?: number | string;
};

export default function CourseBrowse({
    courses = [],
    categories = [],
    filters = {},
}: {
    courses?: BrowseCourse[];
    categories?: { id: number | string; title: string }[];
    filters?: Partial<typeof emptyFilters>;
}) {
    const { auth } = usePage().props as { auth?: { user?: { opd?: { name?: string } | null } | null } };
    const participantOpd = auth?.user?.opd?.name ?? null;

    const [form, setForm] = useState({ ...emptyFilters, ...filters });
    const [loading, setLoading] = useState(false);
    const pendingSearch = useRef(null);
    const cancelRequest = useRef(null);

    useEffect(() => {
        clearTimeout(pendingSearch.current);
        setForm({ ...emptyFilters, ...filters });
    }, [filters]);

    useEffect(
        () => () => {
            clearTimeout(pendingSearch.current);
            cancelRequest.current?.cancel();
        },
        [],
    );

    const applyFilters = (values) => {
        clearTimeout(pendingSearch.current);
        cancelRequest.current?.cancel();
        router.get(
            "/browse",
            Object.fromEntries(
                Object.entries(values).filter(([, value]) => value),
            ) as any,
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onCancelToken: (token) => {
                    cancelRequest.current = token;
                },
                onStart: () => setLoading(true),
                onFinish: () => setLoading(false),
            },
        );
    };

    const updateFilter = (field, value) => {
        const values = { ...form, [field]: value };
        setForm(values);
        clearTimeout(pendingSearch.current);
        if (field === "search") {
            pendingSearch.current = setTimeout(() => applyFilters(values), 350);
        } else {
            applyFilters(values);
        }
    };

    const resetFilters = () => {
        const values = { ...emptyFilters, visibility: form.visibility };
        setForm(values);
        applyFilters(values);
    };

    return (
        <ParticipantApp>
            <Head title="Jelajah Kelas" />
            <BrowseCatalog
                courses={courses}
                categories={categories}
                form={form}
                loading={loading}
                participantOpd={participantOpd}
                onUpdateFilter={updateFilter}
                onReset={resetFilters}
                hasAppliedFilters={Boolean(filters.search || filters.category || filters.employee_status)}
            />
        </ParticipantApp>
    );
}

function BrowseCatalog({
    courses,
    categories,
    form,
    loading,
    participantOpd,
    onUpdateFilter,
    onReset,
    hasAppliedFilters,
}: {
    courses: BrowseCourse[];
    categories: { id: number | string; title: string }[];
    form: typeof emptyFilters;
    loading: boolean;
    participantOpd: string | null;
    onUpdateFilter: (field: string, value: string | number) => void;
    onReset: () => void;
    hasAppliedFilters: boolean;
}) {
    const hasActiveFilters = Boolean(form.search || form.category);

    return (
        <div className="browse-courses">
            <header className="browse-courses-header">
                <div>
                    <p className="participant-eyebrow">Jelajah Kelas</p>
                    <h1>Jelajah Kelas</h1>
                    <p>
                        {participantOpd
                            ? `Temukan kelas pembelajaran yang tersedia untuk OPD ${participantOpd}.`
                            : 'Temukan kelas pembelajaran yang tersedia untuk Anda.'}
                    </p>
                </div>
            </header>

            <section className="browse-courses-collection" aria-label="Daftar kelas yang tersedia">
                <div className="browse-courses-toolbar">
                    <div role="group" aria-label="Filter kategori" className="browse-courses-categories">
                        <button type="button" aria-pressed={!form.category} onClick={() => onUpdateFilter('category', '')} className={!form.category ? 'is-active' : ''}>Semua kategori</button>
                        {categories.map((category) => (
                            <button key={category.id} type="button" aria-pressed={String(form.category) === String(category.id)} onClick={() => onUpdateFilter('category', category.id)} className={String(form.category) === String(category.id) ? 'is-active' : ''}>{category.title}</button>
                        ))}
                    </div>
                    <div className="browse-courses-search">
                        <label htmlFor="browse-course-search" className="sr-only">Cari judul kelas</label>
                        <box-icon name="search" color="currentColor" size="18px" aria-hidden="true" />
                        <input
                            id="browse-course-search"
                            type="search"
                            value={form.search}
                            onChange={(event) => onUpdateFilter('search', event.target.value)}
                            placeholder="Cari judul kelas…"
                        />
                    </div>
                </div>

                <div className="browse-courses-results">
                    <p aria-live="polite">
                        {loading ? 'Mencari kelas…' : `Menampilkan ${courses.length} kelas tersedia`}
                    </p>
                    {hasActiveFilters && <button type="button" onClick={onReset}>Reset filter<box-icon name="reset" size="15px" aria-hidden="true" /></button>}
                </div>

                <div aria-busy={loading} className={loading ? 'opacity-60 transition-opacity' : 'transition-opacity'}>
                    {courses.length > 0 ? (
                        <div className="browse-courses-grid">
                            {courses.map((course) => (
                                <BrowseCard key={course.id} course={course} />
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-[15px] border border-dashed border-[#D0D9E6] bg-[#F5FBFA] px-5 py-10 text-center">
                            <span className="mx-auto grid h-12 w-12 place-items-center rounded-[10px] bg-[linear-gradient(135deg,#F0FAF8_0%,#E6F3F3_100%)]">
                                <box-icon name="book-open" color="#018083" size="24px" aria-hidden="true" />
                            </span>
                            <h3 className="mt-4 font-poppins text-base font-semibold text-[#075D5E]">
                                {hasAppliedFilters ? 'Belum ada kelas yang cocok' : 'Belum ada kelas tersedia'}
                            </h3>
                            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-[#526176]">
                                {hasAppliedFilters
                                    ? 'Coba kata kunci lain atau perluas pilihan kategori.'
                                    : 'Silakan kembali lagi untuk melihat kelas terbaru sesuai OPD Anda.'}
                            </p>
                            {hasActiveFilters && (
                                <button type="button" onClick={onReset} className="mt-5 inline-flex min-h-11 items-center justify-center gap-2 rounded-[10px] bg-[linear-gradient(90deg,#019B91_0%,#018083_100%)] px-5 font-poppins text-sm font-semibold text-white transition hover:opacity-90">
                                    Reset pencarian
                                </button>
                            )}
                        </div>
                    )}
                </div>
            </section>
        </div>
    );
}

function BrowseCard({ course }: { course: BrowseCourse }) {
    const scheduled = course.type === 'schedule';
    const rating = Number(course.star || 0).toFixed(1);
    const totalParticipant = Number(course.total_participant || 0).toLocaleString('id-ID');
    const detailUrl = `/course/${course.slug}`;

    return (
        <article className="group flex flex-col overflow-hidden rounded-[12px] border border-[#E8ECF1] bg-white transition hover:border-[#BDE8E2] hover:shadow-[0_6px_20px_rgba(7,93,94,0.08)]">
            <Link href={detailUrl} tabIndex={-1} aria-hidden="true" className="block">
                <div className="relative mx-[10px] mt-[10px] overflow-hidden rounded-[8px] bg-[#E6F3F3]">
                    <img
                        src={course.thumbnail || '/assets/media/images/static/default-class.jpg'}
                        alt=""
                        loading="lazy"
                        onError={(event) => { event.currentTarget.onerror = null; event.currentTarget.src = '/assets/media/images/static/default-class.jpg'; }}
                        className="aspect-[16/9] w-full object-cover"
                    />
                    <span className="absolute bottom-2.5 left-2.5 inline-flex items-center gap-1.5 rounded-[6px] border border-white/40 bg-[#075D5E]/90 px-2.5 py-1.5 text-[10px] font-medium text-white">
                        <box-icon name={scheduled ? 'calendar' : 'book-open'} color="white" size="15px" aria-hidden="true" />
                        {scheduled ? 'Terjadwal' : 'Belajar mandiri'}
                    </span>
                </div>
            </Link>

            <div className="flex flex-1 flex-col p-[18px]">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="rounded-[6px] bg-[#FFF3E5] px-2 py-1 text-[10px] text-[#AD5700]">{course.category || 'Pelatihan ASN'}</span>
                    <span className="ml-auto text-[11px] text-primary">
                        {course.visibility === 'public' ? 'Publik · Semua OPD' : `Privat · ${course.opd_name || 'OPD tertentu'}`}
                    </span>
                </div>

                <h3 className="mt-3 font-poppins text-base font-semibold leading-6 text-[#075D5E]">
                    <Link href={detailUrl} className="transition hover:text-[#018083]">{course.title}</Link>
                </h3>

                <p className="mt-2 line-clamp-2 text-xs leading-[1.8] text-[#66778C]">
                    {course.short_description?.trim() || 'Kembangkan kompetensi Anda melalui rangkaian pembelajaran kelas ini.'}
                </p>

                <div className="mt-auto flex items-center justify-between gap-2 border-t border-[#E8ECF1] pt-4 text-xs text-[#66778C]">
                    <span className="inline-flex items-center gap-1.5">
                        <box-icon name="group" color="#018083" size="15px" aria-hidden="true" />
                        <strong className="font-semibold text-[#075D5E]">{totalParticipant}</strong>
                        pengguna
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <box-icon type="solid" name="star" color="#F68104" size="14px" aria-hidden="true" />
                        <strong className="font-semibold text-[#075D5E]">{rating}</strong>
                    </span>
                </div>

                <div className="mt-4">
                    <Link href={detailUrl} className="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-[8px] border border-[#39A9A1] px-4 font-poppins text-xs font-semibold text-[#018083] transition group-hover:bg-[#018083] group-hover:text-white">
                        Lihat kelas
                        <box-icon name="right-arrow-alt" color="currentColor" size="16px" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </article>
    );
}
