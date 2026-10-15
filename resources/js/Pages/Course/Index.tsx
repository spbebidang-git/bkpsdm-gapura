import PublicCourseCard from "@/Components/PublicCourseCard";
import App from "@/Layouts/App";
import { Head, router } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import "../../../css/public-course-index.css";

const employeeStatuses = ["CPNS", "PNS", "PPPK", "PPPK Paruh Waktu"];
const emptyFilters = { search: "", employee_status: "", category: "" };

const filterChip = (active: boolean) => `inline-flex min-h-9 items-center justify-center rounded-[10px] border px-3 py-1.5 font-poppins text-[11px] font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 ${active ? 'border-primary bg-primary text-white' : 'border-primary/30 bg-white text-primary hover:border-primary hover:bg-[#E6F3F3]'}`;

export default function CourseIndex({
    courses = [],
    categories = [],
    filters = {},
    bannerUrl = null,
}) {
    const [form, setForm] = useState({ ...emptyFilters, ...filters });
    const [loading, setLoading] = useState(false);
    const pendingSearch = useRef(null);
    const cancelRequest = useRef(null);

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
        setForm(emptyFilters);
        applyFilters(emptyFilters);
    };
    const hasActiveFilters = Object.values(form).some(Boolean);
    const hasAppliedFilters = Object.values(filters).some(Boolean);

    return (
        <App>
            <Head title="Materi Pilihan" />
            <section className="public-course-index">
                <div className="course-index-content">
                    <div className="course-index-banner" role={bannerUrl ? undefined : 'img'} aria-label={bannerUrl ? undefined : 'Banner kelas GAPURA'}>
                        {bannerUrl && <img src={bannerUrl} alt="Pelatihan GAPURA" className="h-full w-full object-cover" />}
                    </div>
                    <h1 className="mb-6 mt-10 text-center font-poppins text-2xl font-semibold text-[#075D5E] sm:mt-12 sm:text-3xl">Daftar kelas</h1>
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
                        <fieldset>
                            <legend className="sr-only">Kategori kelas</legend>
                            <div className="flex flex-wrap justify-center gap-2">
                                <button type="button" aria-pressed={!form.category} onClick={() => updateFilter('category', '')} className={filterChip(!form.category)}>Semua kategori</button>
                                {categories.map((category) => <button key={category.id} type="button" aria-pressed={String(form.category) === String(category.id)} onClick={() => updateFilter('category', category.id)} className={filterChip(String(form.category) === String(category.id))}>{category.title}</button>)}
                            </div>
                        </fieldset>
                        <fieldset className="mt-3">
                            <legend className="sr-only">Status pegawai</legend>
                            <div className="flex flex-wrap justify-center gap-2">
                                <button type="button" aria-pressed={!form.employee_status} onClick={() => updateFilter('employee_status', '')} className={filterChip(!form.employee_status)}>Semua status pegawai</button>
                                {employeeStatuses.map((status) => <button key={status} type="button" aria-pressed={form.employee_status === status} onClick={() => updateFilter('employee_status', status)} className={filterChip(form.employee_status === status)}>{status}</button>)}
                            </div>
                        </fieldset>
                        <div className="mx-auto mt-5 flex max-w-[720px] gap-2">
                            <label htmlFor="course-search" className="sr-only">Cari kelas</label>
                            <input
                                id="course-search"
                                type="search"
                                maxLength={100}
                                value={form.search}
                                onChange={(event) => updateFilter('search', event.target.value)}
                                placeholder="Cari judul kelas…"
                                className="h-11 min-w-0 flex-1 rounded-[10px] border border-transparent bg-[#E6F3F3] px-4 font-inter text-sm text-[#1A2B3F] placeholder:text-[#66778C] focus:border-primary focus:ring-2 focus:ring-primary/10"
                            />
                            <button type="submit" className="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-[10px] bg-primary px-4 font-poppins text-sm font-semibold text-white transition hover:bg-[#075D5E] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"><i className="bx bx-search text-lg" aria-hidden="true" />Cari</button>
                        </div>
                    </form>

                    <div className="mt-8">
                        <div className="mb-5 flex min-h-9 items-center justify-between gap-4">
                            <p
                                role="status"
                                aria-live="polite"
                                className="font-inter text-sm text-[#66778C]"
                            >
                                {loading ? (
                                    "Mencari kelas…"
                                ) : (
                                    <>
                                        <strong className="font-poppins font-semibold text-[#075D5E]">
                                            {courses.length} kelas
                                        </strong>{" "}
                                        {hasAppliedFilters
                                            ? "sesuai pencarian Anda"
                                            : "tersedia untuk Anda"}
                                    </>
                                )}
                            </p>
                            <button
                                type="button"
                                disabled={!hasActiveFilters}
                                onClick={resetFilters}
                                className="shrink-0 rounded-[10px] border border-[#D0D9E6] bg-white px-3.5 py-2 font-poppins text-sm font-semibold text-primary transition duration-200 hover:border-primary/40 hover:bg-primary-mist/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:invisible"
                            >
                                Reset filter
                            </button>
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
                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
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
                                            Lihat semua kelas
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </section>
        </App>
    );
}
