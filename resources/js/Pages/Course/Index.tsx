import PublicCourseCard from "@/Components/PublicCourseCard";
import App from "@/Layouts/App";
import { Head, router } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import "../../../css/public-course-index.css";

const employeeStatuses = ["CPNS", "PNS", "PPPK", "PPPK Paruh Waktu"];
const emptyFilters = { search: "", employee_status: "", category: "", visibility: "" };

export default function CourseIndex({
    courses = [],
    categories = [],
    filters = {},
}) {
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
            "/course",
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
    const pageTitle = form.visibility === 'public'
        ? 'Knowledge Management System (KMS)'
        : form.visibility === 'private'
            ? 'Learning Management System (LMS)'
            : 'Materi Pilihan';
    const hasActiveFilters = Boolean(form.search || form.category || form.employee_status);
    const hasAppliedFilters = Boolean((filters as typeof emptyFilters).search || (filters as typeof emptyFilters).category || (filters as typeof emptyFilters).employee_status);

    return (
        <App>
            <Head title={pageTitle} />
            <section className="public-course-index">
                <div className="course-index-content">
                    <div className="course-index-heading">
                        <div>
                            <p className="course-index-eyebrow">MATERI PILIHAN</p>
                            <h1 className="max-w-3xl font-poppins text-2xl font-semibold leading-tight tracking-tight text-[#075D5E] sm:text-3xl">{pageTitle}</h1>
                            <p className="mt-3 max-w-xl font-inter text-sm leading-6 text-[#66778C]">{form.visibility === 'public' ? 'Jelajahi pengetahuan dan temukan materi untuk mengembangkan kompetensi Anda.' : form.visibility === 'private' ? 'Temukan kelas pembelajaran yang tersedia sesuai OPD dan status kepegawaian Anda.' : 'Temukan pengetahuan dan kelas pembelajaran untuk mengembangkan kompetensi Anda.'}</p>
                        </div>
                    </div>
                    <div className="course-index-tabs" aria-label="Jenis materi">
                        <button type="button" aria-pressed={form.visibility === 'public'} onClick={() => updateFilter('visibility', 'public')} className={form.visibility === 'public' ? 'is-active' : ''}>KMS <span>Pengetahuan</span></button>
                        <button type="button" aria-pressed={form.visibility === 'private'} onClick={() => updateFilter('visibility', 'private')} className={form.visibility === 'private' ? 'is-active' : ''}>LMS <span>Pembelajaran</span></button>
                    </div>
                    <div className="course-index-layout">
                    <form
                        id="course-filters"
                        role="search"
                        aria-label="Filter kelas"
                        onSubmit={(event) => {
                            event.preventDefault();
                            applyFilters(form);
                        }}
                        className="course-index-filters"
                    >
                        <div className="course-index-filter-header">
                            <div><h2>Temukan materi Anda</h2><p>Cari judul atau pilih filter yang sesuai.</p></div>
                            {hasActiveFilters && <button type="button" onClick={resetFilters} className="course-index-reset">Reset filter</button>}
                        </div>
                        <div className="course-index-search flex w-full gap-2">
                            <svg className="course-index-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 4.5 4.5" strokeLinecap="round" /></svg>
                            <label htmlFor="course-search" className="sr-only">Cari kelas</label>
                            <input
                                id="course-search"
                                type="search"
                                maxLength={100}
                                value={form.search}
                                onChange={(event) => updateFilter('search', event.target.value)}
                                placeholder="Cari judul kelas…"
                                className="h-12 min-w-0 flex-1 rounded-xl border border-[#DCE7E2] bg-[#F6FAF9] pl-11 pr-4 font-inter text-sm text-[#1A2B3F] placeholder:text-[#66778C] focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10"
                            />
                        </div>
                        <div className="course-index-filter-group" role="group" aria-labelledby="course-category-label">
                            <h3 id="course-category-label">Kategori</h3>
                            <div className="course-index-filter-options">
                                <button type="button" aria-pressed={!form.category} onClick={() => updateFilter('category', '')}>Semua kategori</button>
                                {categories.map((category) => <button key={category.id} type="button" aria-pressed={String(form.category) === String(category.id)} onClick={() => updateFilter('category', category.id)}>{category.title}</button>)}
                            </div>
                        </div>
                        <div className="course-index-filter-group" role="group" aria-labelledby="course-status-label">
                            <h3 id="course-status-label">Status pegawai</h3>
                            <div className="course-index-filter-options">
                                <button type="button" aria-pressed={!form.employee_status} onClick={() => updateFilter('employee_status', '')}>Semua status pegawai</button>
                                {employeeStatuses.map((status) => <button key={status} type="button" aria-pressed={form.employee_status === status} onClick={() => updateFilter('employee_status', status)}>{status}</button>)}
                            </div>
                        </div>

                    </form>

                    <div className="course-index-results">
                        <div className="mb-5 flex min-h-9 items-center justify-between gap-4">
                            <p
                                role="status"
                                aria-live="polite"
                                className="font-inter text-sm text-[#66778C]"
                            >
                                {loading ? 'Mencari kelas…' : hasAppliedFilters ? 'Materi sesuai pilihan Anda' : 'Pilihan materi untuk Anda'}
                            </p>

                        </div>

                        <div
                            aria-busy={loading}
                            className={
                                loading
                                    ? "opacity-60 transition-opacity"
                                    : "transition-opacity"
                            }
                        >
                            {courses.length > 0 ? (
                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:gap-6">
                                    {courses.map((course) => (
                                        <PublicCourseCard
                                            key={course.id}
                                            course={course}
                                            variant="home"
                                            fontFamily="poppins"
                                            showDescription
                                        />
                                    ))}
                                </div>
                            ) : (
                                <div className="rounded-[15px] border border-dashed border-[#BDE8E2] bg-white px-6 py-16 text-center">
                                    <span className="mx-auto grid h-14 w-14 place-items-center rounded-[15px] bg-white shadow-[0_2px_16px_rgba(1,128,131,0.08)]">
                                        <box-icon
                                            name="search-alt"
                                            color="#018083"
                                            size="28px"
                                        />
                                    </span>
                                    <h2 className="mt-4 font-poppins text-xl font-semibold text-[#075D5E]">
                                        {hasAppliedFilters
                                            ? "Belum ada kelas yang cocok"
                                            : "Kelas belum tersedia"}
                                    </h2>
                                    <p className="mt-2 font-poly text-sm leading-6 text-[#66778C]">
                                        {hasAppliedFilters
                                            ? "Coba kata kunci lain atau perluas pilihan status dan kategori."
                                            : "Silakan kembali lagi untuk melihat pelatihan terbaru."}
                                    </p>
                                    {hasActiveFilters && (
                                        <button
                                            type="button"
                                            onClick={resetFilters}
                                            className="mt-6 rounded-[10px] bg-[linear-gradient(90deg,#019B91_0%,#018083_100%)] px-5 py-3 font-poppins text-sm font-semibold text-white transition duration-200 hover:opacity-90 active:scale-[0.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                        >
                                            Reset pencarian
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                    </div>
                </div>
            </section>
        </App>
    );
}
