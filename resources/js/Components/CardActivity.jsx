const ICON_MAP = {
    'Pertanyaan': 'edit-alt',
    'Tugas': 'task',
    'Interaktif': 'devices',
    'Literatur': 'book-open',
    'Pre Test/Quiz': 'clipboard',
    'Post Test': 'check-shield',
    'Pertemuan Daring': 'video',
    'Presentasi Video': 'play-circle',
};

const COLOR_MAP = {
    'Pertanyaan': { bg: 'bg-indigo-50', border: 'border-indigo-100', icon: '#6366f1', hover: 'hover:border-indigo-300 hover:bg-indigo-50/80', title: 'text-indigo-700' },
    'Tugas': { bg: 'bg-emerald-50', border: 'border-emerald-100', icon: '#10b981', hover: 'hover:border-emerald-300 hover:bg-emerald-50/80', title: 'text-emerald-700' },
    'Interaktif': { bg: 'bg-orange-50', border: 'border-orange-100', icon: '#f97316', hover: 'hover:border-orange-300 hover:bg-orange-50/80', title: 'text-orange-700' },
    'Literatur': { bg: 'bg-sky-50', border: 'border-sky-100', icon: '#0ea5e9', hover: 'hover:border-sky-300 hover:bg-sky-50/80', title: 'text-sky-700' },
    'Pre Test/Quiz': { bg: 'bg-violet-50', border: 'border-violet-100', icon: '#8b5cf6', hover: 'hover:border-violet-300 hover:bg-violet-50/80', title: 'text-violet-700' },
    'Post Test': { bg: 'bg-rose-50', border: 'border-rose-100', icon: '#f43f5e', hover: 'hover:border-rose-300 hover:bg-rose-50/80', title: 'text-rose-700' },
    'Pertemuan Daring': { bg: 'bg-blue-50', border: 'border-blue-100', icon: '#3b82f6', hover: 'hover:border-blue-300 hover:bg-blue-50/80', title: 'text-blue-700' },
    'Presentasi Video': { bg: 'bg-amber-50', border: 'border-amber-100', icon: '#f59e0b', hover: 'hover:border-amber-300 hover:bg-amber-50/80', title: 'text-amber-700' },
};

const DEFAULT = { bg: 'bg-primary/5', border: 'border-primary/10', icon: 'var(--color-primary)', hover: 'hover:border-primary/30 hover:bg-primary/10', title: 'text-primary' };

const CardActivity = ({ title, description, onClick }) => {
    const icon = ICON_MAP[title] || 'category';
    const color = COLOR_MAP[title] || DEFAULT;

    return (
        <div
            onClick={onClick}
            className={`group h-full flex flex-col items-center text-center gap-4 p-6 bg-white rounded-2xl border ${color.border} shadow-sm ${color.hover} transition-all duration-200 cursor-pointer hover:shadow-md hover:-translate-y-0.5`}
        >
            <div className={`w-14 h-14 rounded-2xl ${color.bg} flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110 duration-200`}>
                <box-icon name={icon} color={color.icon} size="28px"></box-icon>
            </div>
            <div>
                <h4 className={`text-base font-bold ${color.title} mb-1.5`}>{title}</h4>
                <p className="text-sm text-gray-500 leading-relaxed line-clamp-4">{description}</p>
            </div>
            <div className={`mt-auto pt-2 w-full flex items-center justify-center gap-1.5 text-xs font-semibold ${color.title} opacity-0 group-hover:opacity-100 transition-opacity`}>
                Pilih
                <box-icon name="right-arrow-alt" color={color.icon} size="16px"></box-icon>
            </div>
        </div>
    );
};

export default CardActivity;