import App from "@/Layouts/App";
import { Head } from "@inertiajs/react";
import "../../../css/public-course-index.css";
import { CatalogCourse, CatalogResults, CatalogSearch, employeeStatuses, useCatalogFilters } from "./catalog";

export default function LmsIndex({
    courses = [],
    categories = [],
    filters = {},
}: {
    courses?: CatalogCourse[];
    categories?: { id: number | string; title: string }[];
    filters?: Record<string, string>;
}) {
    const { form, loading, updateFilter, resetFilters, hasActiveFilters, hasAppliedFilters } = useCatalogFilters(filters, 'lms');

    return (
        <App>
            <Head title="Learning Management System (LMS)" />
            <section className="public-course-index">
                <div className="course-index-content">
                    <div className="course-index-heading">
                        <div>
                            <p className="course-index-eyebrow">MATERI PILIHAN</p>
                            <h1 className="max-w-3xl font-poppins text-2xl font-semibold leading-tight tracking-tight text-[#075D5E] sm:text-3xl">Learning Management System (LMS)</h1>
                            <p className="mt-3 max-w-xl font-inter text-sm leading-6 text-[#66778C]">Jelajahi semua kelas pembelajaran yang tersedia di seluruh OPD. Aktivasi kelas akan divalidasi sesuai OPD dan status kepegawaian Anda.</p>
                        </div>
                    </div>

                    <div className="course-index-layout">
                        <form
                            id="course-filters"
                            role="search"
                            aria-label="Filter kelas"
                            onSubmit={(event) => event.preventDefault()}
                            className="course-index-filters"
                        >
                            <div className="course-index-filter-header">
                                <div><h2>Temukan materi Anda</h2><p>Cari judul atau pilih filter yang sesuai.</p></div>
                                {hasActiveFilters && <button type="button" onClick={resetFilters} className="course-index-reset">Reset filter</button>}
                            </div>
                            <CatalogSearch value={form.search} onChange={(value) => updateFilter('search', value)} placeholder="Cari judul kelas…" />
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

                        <CatalogResults courses={courses} loading={loading} hasActiveFilters={hasActiveFilters} hasAppliedFilters={hasAppliedFilters} onReset={resetFilters} />
                    </div>
                </div>
            </section>
        </App>
    );
}
