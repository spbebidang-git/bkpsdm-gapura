const ICON_MAP = {
    Pertanyaan: 'edit-alt',
    Tugas: 'task',
    Interaktif: 'devices',
    Literatur: 'book-open',
    'Pre Test/Quiz': 'clipboard',
    'Post Test': 'check-shield',
    'Pertemuan Daring': 'video',
    'Presentasi Video': 'play-circle',
};

export default function CardActivity({ title, description, onClick }: { title: keyof typeof ICON_MAP | string; description: string; onClick?: () => void }) {
    const interactive = typeof onClick === 'function';

    const handleKeyDown = (event) => {
        if (!interactive || !['Enter', ' '].includes(event.key)) return;
        event.preventDefault();
        onClick();
    };

    return (
        <div
            onClick={onClick}
            onKeyDown={handleKeyDown}
            role={interactive ? 'button' : undefined}
            tabIndex={interactive ? 0 : undefined}
            className="group flex h-full cursor-pointer flex-col gap-4 rounded-xl border border-hairline bg-canvas p-5 text-left transition-colors hover:border-primary/40 hover:bg-primary-mist/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
        >
            <div className="flex items-start justify-between gap-4">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-mist text-primary">
                    <box-icon name={ICON_MAP[title] || 'category'} color="var(--color-primary)" size="22px" />
                </span>
                <i className="bx bx-right-arrow-alt text-xl text-stone transition-transform group-hover:translate-x-0.5 group-hover:text-primary" aria-hidden />
            </div>
            <div>
                <h3 className="text-base font-semibold text-ink">{title}</h3>
                <p className="mt-1.5 text-sm leading-6 text-steel">{description}</p>
            </div>
        </div>
    );
}
