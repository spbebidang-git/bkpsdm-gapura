import BreadcrumbLearn from "@/Components/BreadcrumbLearn";
import { Link } from "@inertiajs/react";

const TYPE_CONFIG = {
    'pre-test': { label: 'Pre Test / Kuis', icon: 'clipboard', bg: 'bg-violet-50', border: 'border-violet-100', color: '#8b5cf6', text: 'text-violet-700' },
    'post-test': { label: 'Post Test', icon: 'check-shield', bg: 'bg-rose-50', border: 'border-rose-100', color: '#f43f5e', text: 'text-rose-700' },
    'task': { label: 'Tugas', icon: 'task', bg: 'bg-emerald-50', border: 'border-emerald-100', color: '#10b981', text: 'text-emerald-700' },
    'meeting': { label: 'Pertemuan Daring', icon: 'video', bg: 'bg-blue-50', border: 'border-blue-100', color: '#3b82f6', text: 'text-blue-700' },
    'video': { label: 'Presentasi Video', icon: 'play-circle', bg: 'bg-amber-50', border: 'border-amber-100', color: '#f59e0b', text: 'text-amber-700' },
    'literature': { label: 'Literatur', icon: 'book-open', bg: 'bg-sky-50', border: 'border-sky-100', color: '#0ea5e9', text: 'text-sky-700' },
    'variant': { label: 'Variasi Soal', icon: 'layer', bg: 'bg-fuchsia-50', border: 'border-fuchsia-100', color: '#d946ef', text: 'text-fuchsia-700' },
    'question-item': { label: 'Butir Soal', icon: 'list-ol', bg: 'bg-teal-50', border: 'border-teal-100', color: '#14b8a6', text: 'text-teal-700' },
};

/**
 * @param {string}  slug        - course slug
 * @param {string}  type        - activity type key (pre-test | post-test | task | meeting | video | literature)
 * @param {string}  title       - page title (e.g. "Tambah Pre Test / Kuis")
 * @param {string}  description - short description
 * @param {boolean} isEdit      - true when editing existing activity
 */
const ActivityFormLayout = ({ slug, type, title, description, isEdit = false }) => {
    const cfg = TYPE_CONFIG[type] || { label: type, icon: 'category', bg: 'bg-primary/5', border: 'border-primary/10', color: 'var(--color-primary)', text: 'text-primary' };

    return (
        <div className="max-w-3xl mx-auto mb-8">
            {/* Breadcrumb */}
            <div className="flex justify-center mb-8">
                <div className="inline-flex flex-wrap items-center gap-2 md:gap-3 bg-white/90 backdrop-blur-md py-2.5 px-5 rounded-full shadow-[0_4px_25px_-4px_rgba(0,0,0,0.06)] border border-gray-100/80 text-sm">
                    <Link href="/instructor" className="opacity-70 hover:opacity-100 transition-all hover:-translate-y-0.5">
                        <BreadcrumbLearn title="Dashboard" activeTab={false} icon="grid-alt" size="lg" />
                    </Link>
                    <box-icon name="chevron-right" color="#cbd5e1" size="18px"></box-icon>
                    <Link href={`/instructor/course/${slug}`} className="opacity-70 hover:opacity-100 transition-all hover:-translate-y-0.5">
                        <BreadcrumbLearn title="Detail Course" activeTab={false} icon="book-bookmark" size="lg" />
                    </Link>
                    <box-icon name="chevron-right" color="#cbd5e1" size="18px"></box-icon>
                    <Link href={`/instructor/course/${slug}/activity`} className="opacity-70 hover:opacity-100 transition-all hover:-translate-y-0.5">
                        <BreadcrumbLearn title="Aktivitas" activeTab={false} icon="list-ul" size="lg" />
                    </Link>
                    <box-icon name="chevron-right" color="#cbd5e1" size="18px"></box-icon>
                    <BreadcrumbLearn title={cfg.label} activeTab={true} icon={cfg.icon} size="lg" />
                </div>
            </div>

            {/* Page Header */}
            <div className={`flex items-center gap-4 p-5 rounded-2xl border ${cfg.border} ${cfg.bg} mb-2`}>
                <div className={`w-12 h-12 rounded-xl bg-white flex items-center justify-center flex-shrink-0 shadow-sm`}>
                    <box-icon name={cfg.icon} color={cfg.color} size="26px"></box-icon>
                </div>
                <div>
                    <div className="flex items-center gap-2 mb-0.5">
                        <span className={`text-xs font-bold uppercase tracking-wider ${cfg.text}`}>{cfg.label}</span>
                        {isEdit && (
                            <span className="text-[10px] font-semibold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Edit</span>
                        )}
                    </div>
                    <h1 className="text-xl font-extrabold text-gray-800 tracking-tight leading-tight">{title}</h1>
                    {description && <p className="text-sm text-gray-500 mt-0.5">{description}</p>}
                </div>
            </div>
        </div>
    );
};

export default ActivityFormLayout;
