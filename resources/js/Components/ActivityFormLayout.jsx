import {
    badgePending,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
} from '@/Components/Admin/ui';

const TYPE_CONFIG = {
    'pre-test': { label: 'Pre-test / Kuis', icon: 'clipboard' },
    'post-test': { label: 'Post-test', icon: 'check-shield' },
    task: { label: 'Tugas', icon: 'task' },
    meeting: { label: 'Pertemuan daring', icon: 'video' },
    video: { label: 'Presentasi video', icon: 'play-circle' },
    literature: { label: 'Literatur', icon: 'book-open' },
    variant: { label: 'Variasi soal', icon: 'layer' },
    'question-item': { label: 'Butir soal', icon: 'list-ol' },
};

export default function ActivityFormLayout({ slug, type, title, description, isEdit = false, fullWidth = false, showIcon = true }) {
    const config = TYPE_CONFIG[type] || { label: type, icon: 'category' };

    return (
        <div className={fullWidth ? 'w-full' : 'mx-auto max-w-3xl'}>
            <div className={pageHeader}>
                <div className="flex min-w-0 items-start gap-3">
                    {showIcon && (
                        <span className="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-mist text-primary">
                            <box-icon name={config.icon} color="var(--color-primary)" size="21px" />
                        </span>
                    )}
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <p className={pageEyebrow}>{config.label}</p>
                            {isEdit && <span className={badgePending}>Edit</span>}
                        </div>
                        <h1 className={pageTitle}>{title}</h1>
                        {description && <p className={pageDescription}>{description}</p>}
                    </div>
                </div>
            </div>
        </div>
    );
}
