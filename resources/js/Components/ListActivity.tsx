import { Link } from '@inertiajs/react';
import type { LearnActivityType, LearnId, LearnStatus } from '@/types/learn';

const activityTypes: Record<LearnActivityType, { label: string; description: string; icon: string }> = {
    'pre-test': { label: 'Test', description: 'Mengerjakan Pre Test', icon: 'pencil' },
    'post-test': { label: 'Test', description: 'Mengerjakan Post Test', icon: 'pencil' },
    task: { label: 'Tugas', description: 'Mengerjakan Tugas', icon: 'task' },
    meeting: { label: 'Pertemuan online', description: 'Mengikuti Pertemuan', icon: 'video' },
    video: { label: 'Video pembelajaran', description: 'Menonton Video', icon: 'play-circle' },
    literature: { label: 'Bahan bacaan', description: 'Membaca Materi', icon: 'book' },
};

interface ListActivityProps {
    type?: LearnActivityType;
    activity?: string;
    status?: LearnStatus;
    isActive?: number | boolean;
    slug?: string;
    schedule?: { id: LearnId };
    activityId?: LearnId;
    value?: string | number | null;
    index?: number;
    onNavigate?: () => void;
    disabled?: boolean;
    locked?: boolean;
}

export default function ListActivity({ type, activity, status, isActive, slug, schedule, activityId, value, index, onNavigate, disabled = false, locked = false }: ListActivityProps) {
    const config = activityTypes[type] ?? { label: 'Kegiatan', description: 'Mengikuti Kegiatan', icon: 'book-open' };
    const state = status === 'done' ? 'done' : status === 'in_progress' ? 'in-progress' : 'pending';
    const statusLabel = state === 'done' ? 'Selesai' : state === 'in-progress' ? 'Sedang berlangsung' : 'Belum selesai';
    const content = <>
        <span className="learn-agenda-card__number">{index ?? <i className={`bx bx-${config.icon}`} aria-hidden="true" />}</span>
        <div className="learn-agenda-card__copy">
            <h3>{activity}</h3>
            <div className="learn-agenda-card__badges">
                <span className={`learn-agenda-card__status is-${state}`}>{statusLabel}</span>
                {locked && <span className="learn-agenda-card__type"><i className="bx bx-lock-alt" aria-hidden="true" /> Dikunci</span>}
                <span className={`learn-agenda-card__type is-${type}`}>{config.label}</span>
            </div>
            <p className="learn-agenda-card__meta">{config.description}{value != null && <> | Bobot: {value}</>}</p>
        </div>
        {state === 'done' && <span className="learn-agenda-card__complete" aria-hidden="true"><i className="bx bx-check" /></span>}
    </>;
    const className = `learn-agenda-card${isActive ? ' is-active' : ''}${disabled ? ' is-disabled' : ''}`;

    return !disabled && schedule && slug && activityId ? <Link
        href={route('participant.learn.instruction.index', { course: slug, schedule: schedule.id, activity: activityId })}
        className={className}
        aria-current={isActive ? 'step' : undefined}
        onClick={onNavigate}
    >{content}</Link> : <div className={className} aria-disabled={disabled || undefined} title={locked ? "Kegiatan sudah selesai dan tidak dapat diulang" : disabled ? "Selesaikan pertemuan yang sedang berlangsung terlebih dahulu" : undefined}>{content}</div>;
}
