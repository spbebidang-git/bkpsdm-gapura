import App from "@/Layouts/App";
import { Head } from "@inertiajs/react";
import { useState } from "react";
import "../../../css/public-course-index.css";
import { CatalogCourse, CatalogResults, CatalogSearch, EntryOption, useCatalogFilters } from "./catalog";

export default function KmsIndex({
    courses = [],
    kms = { opds: [], categories: [] },
    filters = {},
}: {
    courses?: CatalogCourse[];
    kms?: { opds?: EntryOption[]; categories?: EntryOption[] };
    filters?: Record<string, string>;
}) {
    const { form, loading, updateFilter, resetFilters, hasActiveFilters, hasAppliedFilters } = useCatalogFilters(filters, 'kms');
    const [opdSearch, setOpdSearch] = useState('');
    const kmsOpds = kms.opds ?? [];
    const kmsCategories = kms.categories ?? [];
    const selectedOpd = kmsOpds.find((opd) => String(opd.id) === String(form.opd_id));
    const visibleOpds = opdSearch.trim()
        ? kmsOpds.filter((opd) => (opd.name ?? '').toLowerCase().includes(opdSearch.trim().toLowerCase()))
        : kmsOpds;

    return (
        <App>
            <Head title="Knowledge Management System (KMS)" />
            <section className="public-course-index">
                <div className="course-index-content">
                    {!form.opd_id ? <>
                        <header className="course-index-heading">
                            <div>
                                <p className="course-index-eyebrow">MATERI PILIHAN</p>
                                <h1 className="max-w-3xl font-poppins text-2xl font-semibold leading-tight tracking-tight text-[#075D5E] sm:text-3xl">Knowledge Management System (KMS)</h1>
                                <p className="mt-3 max-w-xl font-inter text-sm leading-6 text-[#66778C]">Jelajahi pengetahuan dan temukan materi literatur serta video dari seluruh OPD.</p>
                            </div>
                        </header>

                        <div className="kms-folder-toolbar">
                            <CatalogSearch value={opdSearch} onChange={setOpdSearch} placeholder="Cari nama OPD…" />
                            <p role="status" aria-live="polite">
                                {opdSearch.trim() ? `${visibleOpds.length} dari ${kmsOpds.length} OPD` : `${kmsOpds.length} OPD`}
                            </p>
                        </div>

                        {visibleOpds.length > 0 ? <ul className="kms-folder-grid">
                            {visibleOpds.map((opd) => <li key={opd.id}>
                                <button type="button" onClick={() => updateFilter('opd_id', opd.id)} className="kms-folder-card">
                                    <span className="kms-folder-icon"><box-icon name="folder" color="#FFFFFF" size="26px" aria-hidden="true" /></span>
                                    <span className="kms-folder-name">{opd.name}</span>
                                    <span className="kms-folder-meta">
                                        <span className="kms-folder-count">{opd.courses_count} materi</span>
                                        <box-icon name="chevron-right" color="#018083" size="18px" aria-hidden="true" />
                                    </span>
                                </button>
                            </li>)}
                        </ul> : <div className="course-kms-empty">
                            <box-icon name="search-alt" color="#018083" size="32px" aria-hidden="true" />
                            <h2>Tidak ada OPD yang cocok</h2>
                            <p>Coba kata kunci lain, atau hapus pencarian untuk melihat semua folder.</p>
                            <button type="button" onClick={() => setOpdSearch('')} className="course-index-reset">Hapus pencarian</button>
                        </div>}
                    </> : <>
                        <header className="course-index-heading">
                            <div>
                                <p className="course-index-eyebrow">MATERI PILIHAN</p>
                                <h1 className="max-w-3xl font-poppins text-2xl font-semibold leading-tight tracking-tight text-[#075D5E] sm:text-3xl">{selectedOpd?.name ?? 'OPD'}</h1>
                                <p className="mt-3 max-w-xl font-inter text-sm leading-6 text-[#66778C]">Jelajahi materi pengetahuan yang dibagikan OPD ini.</p>
                            </div>
                        </header>

                        <button type="button" onClick={() => updateFilter('opd_id', '')} className="kms-opd-back">
                            <box-icon name="arrow-back" color="currentColor" size="17px" aria-hidden="true" />Ganti OPD
                        </button>

                        <div className="course-index-layout">
                            <form
                                id="kms-filters"
                                role="search"
                                aria-label="Filter materi"
                                onSubmit={(event) => event.preventDefault()}
                                className="course-index-filters"
                            >
                                <div className="course-index-filter-header">
                                    <div><h2>Temukan materi OPD ini</h2><p>Cari judul atau pilih kategori yang sesuai.</p></div>
                                    {hasActiveFilters && <button type="button" onClick={resetFilters} className="course-index-reset">Reset filter</button>}
                                </div>
                                <CatalogSearch value={form.search} onChange={(value) => updateFilter('search', value)} placeholder="Cari judul materi…" />
                                <div className="course-index-filter-group" role="group" aria-labelledby="kms-category-label">
                                    <h3 id="kms-category-label">Kategori</h3>
                                    <div className="course-index-filter-options">
                                        <button type="button" aria-pressed={!form.category} onClick={() => updateFilter('category', '')}>Semua kategori</button>
                                        {kmsCategories.map((category) => <button key={category.id} type="button" aria-pressed={String(form.category) === String(category.id)} onClick={() => updateFilter('category', category.id)}>{category.title}</button>)}
                                    </div>
                                </div>
                            </form>

                            <CatalogResults courses={courses} loading={loading} hasActiveFilters={hasActiveFilters} hasAppliedFilters={hasAppliedFilters} onReset={resetFilters} showRating={false} />
                        </div>
                    </>}
                </div>
            </section>
        </App>
    );
}
