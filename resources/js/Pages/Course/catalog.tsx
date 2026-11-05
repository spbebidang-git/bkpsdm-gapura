import PublicCourseCard from "@/Components/PublicCourseCard";
import { router } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";

export const employeeStatuses = ["CPNS", "PNS", "PPPK", "PPPK Paruh Waktu"];
export const emptyFilters = { search: "", employee_status: "", category: "", visibility: "", modality: "kms", opd_id: "" };

export type CatalogFilters = typeof emptyFilters;

export type CatalogCourse = {
    id: string;
    modality?: 'kms' | 'lms';
    type: string;
    title: string;
    slug: string;
    thumbnail: string | null;
    category: string | null;
    short_description: string | null;
    employee_status?: string;
    visibility?: 'public' | 'private';
    opd_name?: string | null;
    owner_opd_id?: string | null;
    owner_opd_name?: string | null;
    total_participant?: number | string;
    total_passed?: number | string;
    star?: number | string;
};

export type EntryOption = { id: string; title?: string; name?: string; courses_count: number };

/** Filter katalog bersama halaman KMS dan LMS. */
export function useCatalogFilters(filters: Partial<CatalogFilters>, modality: 'kms' | 'lms') {
    const [form, setForm] = useState<CatalogFilters>({ ...emptyFilters, modality, ...filters });
    const [loading, setLoading] = useState(false);
    const pendingSearch = useRef(null);
    const cancelRequest = useRef(null);

    useEffect(() => {
        clearTimeout(pendingSearch.current);
        setForm({ ...emptyFilters, modality, ...filters });
    }, [filters, modality]);

    useEffect(
        () => () => {
            clearTimeout(pendingSearch.current);
            cancelRequest.current?.cancel();
        },
        [],
    );

    const applyFilters = (values: CatalogFilters) => {
        clearTimeout(pendingSearch.current);
        cancelRequest.current?.cancel();
        router.get(
            window.location.pathname,
            Object.fromEntries(Object.entries(values).filter(([, value]) => value)) as any,
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

    const updateFilter = (field: keyof CatalogFilters, value: string | number) => {
        const values = { ...form, [field]: value } as CatalogFilters;
        setForm(values);
        clearTimeout(pendingSearch.current);
        if (field === "search") {
            pendingSearch.current = setTimeout(() => applyFilters(values), 350);
        } else {
            applyFilters(values);
        }
    };

    const resetFilters = () => {
        const values = { ...emptyFilters, modality };
        setForm(values);
        applyFilters(values);
    };

    const hasActiveFilters = Boolean(form.search || form.category || form.employee_status || form.opd_id);
    const hasAppliedFilters = Boolean(filters.search || filters.category || filters.employee_status || filters.opd_id);

    return { form, loading, updateFilter, resetFilters, hasActiveFilters, hasAppliedFilters };
}

export function CatalogResults({
    courses,
    loading,
    hasActiveFilters,
    hasAppliedFilters,
    onReset,
    showRating = true,
}: {
    courses: CatalogCourse[];
    loading: boolean;
    hasActiveFilters: boolean;
    hasAppliedFilters: boolean;
    onReset: () => void;
    showRating?: boolean;
}) {
    return (
        <div className="course-index-results">
            <div className="mb-5 flex min-h-9 items-center justify-between gap-4">
                <p role="status" aria-live="polite" className="font-inter text-sm text-[#66778C]">
                    {loading ? 'Mencari kelas…' : hasAppliedFilters ? 'Materi sesuai pilihan Anda' : 'Pilihan materi untuk Anda'}
                </p>
            </div>

            <div aria-busy={loading} className={loading ? "opacity-60 transition-opacity" : "transition-opacity"}>
                {courses.length > 0 ? (
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:gap-6">
                        {courses.map((course) => (
                            <PublicCourseCard
                                key={course.id}
                                course={course as any}
                                variant="home"
                                fontFamily="poppins"
                                showDescription
                                showRating={showRating}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="rounded-[15px] border border-dashed border-[#BDE8E2] bg-white px-6 py-16 text-center">
                        <span className="mx-auto grid h-14 w-14 place-items-center rounded-[15px] bg-white shadow-[0_2px_16px_rgba(1,128,131,0.08)]">
                            <box-icon name="search-alt" color="#018083" size="28px" />
                        </span>
                        <h2 className="mt-4 font-poppins text-xl font-semibold text-[#075D5E]">
                            {hasAppliedFilters ? "Belum ada kelas yang cocok" : "Kelas belum tersedia"}
                        </h2>
                        <p className="mt-2 font-poly text-sm leading-6 text-[#66778C]">
                            {hasAppliedFilters
                                ? "Coba kata kunci lain atau perluas pilihan status dan kategori."
                                : "Silakan kembali lagi untuk melihat pelatihan terbaru."}
                        </p>
                        {hasActiveFilters && (
                            <button
                                type="button"
                                onClick={onReset}
                                className="mt-6 rounded-[10px] bg-[linear-gradient(90deg,#019B91_0%,#018083_100%)] px-5 py-3 font-poppins text-sm font-semibold text-white transition duration-200 hover:opacity-90 active:scale-[0.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            >
                                Reset pencarian
                            </button>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}

export function CatalogSearch({ value, onChange, placeholder }: { value: string; onChange: (value: string) => void; placeholder: string }) {
    return (
        <div className="course-index-search flex w-full gap-2">
            <svg className="course-index-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 4.5 4.5" strokeLinecap="round" /></svg>
            <label htmlFor="course-search" className="sr-only">{placeholder}</label>
            <input
                id="course-search"
                type="search"
                maxLength={100}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className="h-12 min-w-0 flex-1 rounded-xl border border-[#DCE7E2] bg-[#F6FAF9] pl-11 pr-4 font-inter text-sm text-[#1A2B3F] placeholder:text-[#66778C] focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/10"
            />
        </div>
    );
}
