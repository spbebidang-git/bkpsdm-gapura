import CountdownTimer from '@/Components/CountDownTimer';
import ListActivity from '@/Components/ListActivity';
import type { LearnLayoutProps } from '@/types/learn';
import { Head, Link, router, usePage } from '@inertiajs/react';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'react-toastify';
import '../../../css/participant-learn.css';

export default function Learn({ type, course, progress, activity, children, activities = [], timer, displayTimerSeconds, activity_id, formattedAnswers, schedule }: LearnLayoutProps) {
    const { site } = usePage().props;
    const [desktopAgendaOpen, setDesktopAgendaOpen] = useState(true);
    const [mobileAgendaOpen, setMobileAgendaOpen] = useState(false);
    const dialogRef = useRef<HTMLDialogElement>(null);
    const mobileTriggerRef = useRef<HTMLButtonElement>(null);
    const mobileCloseRef = useRef<HTMLButtonElement>(null);
    const desktopTriggerRef = useRef<HTMLButtonElement>(null);
    const desktopCloseRef = useRef<HTMLButtonElement>(null);
    const answersRef = useRef(formattedAnswers);
    answersRef.current = formattedAnswers;
    const currentActivityId = activity_id ?? activity.activity_id;
    const activeMeeting = activities.find(item => item.type === 'meeting' && item.status === 'in_progress');
    const timeoutSubmittedRef = useRef(false);

    // Keep the callback stable when toggling the agenda so the quiz timer stays mounted.
    const handleTimeout = useCallback(() => {
        if (timeoutSubmittedRef.current) return;
        timeoutSubmittedRef.current = true;

        const questions = Array.isArray(activity.data?.question_items)
            ? activity.data.question_items as Array<{ question_id?: string | number; question?: string }>
            : [];
        const currentAnswers = Array.isArray(answersRef.current)
            ? answersRef.current as Array<Record<string, unknown>>
            : [];
        const answersByQuestion = new Map(currentAnswers.map(answer => [String(answer.question_id), answer]));
        const timedAnswers = questions.map(question => answersByQuestion.get(String(question.question_id)) ?? ({
            question: question.question,
            option_select: null,
            question_id: question.question_id,
        }));

        router.post(route('participant.learn.store', { course: course.slug, schedule: schedule.id, activity: currentActivityId }), timedAnswers as unknown as Parameters<typeof router.post>[1], {
            onError: (errors) => toast.error(errors.error, { position: 'top-center', autoClose: 5000 }),
        });
    }, [course.slug, schedule.id, currentActivityId, activity.data]);

    useEffect(() => {
        timeoutSubmittedRef.current = false;
    }, [currentActivityId]);

    useEffect(() => {
        const dialog = dialogRef.current;
        if (!mobileAgendaOpen || !dialog) return;
        dialog.showModal();
        mobileCloseRef.current?.focus();
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const desktop = window.matchMedia('(min-width: 1024px)');
        const closeOnDesktop = () => { if (desktop.matches) setMobileAgendaOpen(false); };
        desktop.addEventListener('change', closeOnDesktop);
        closeOnDesktop();
        return () => {
            desktop.removeEventListener('change', closeOnDesktop);
            document.body.style.overflow = previousOverflow;
            dialog.close();
            if (!desktop.matches) mobileTriggerRef.current?.focus();
        };
    }, [mobileAgendaOpen]);

    const agenda = (onNavigate?: () => void) => <><p className="learn-agenda-lock" hidden={!activeMeeting}><i className="bx bx-lock-alt" aria-hidden="true" />Selesaikan pertemuan yang berlangsung untuk membuka kegiatan lain.</p><ol className="learn-agenda-list">
        {activities.map((item, index) => <li key={item.id}><ListActivity
            index={index + 1} type={item.type} slug={course.slug} activityId={item.id}
            activity={item.title} status={item.status} isActive={item.id === activity.activity_id}
            value={item.value} schedule={schedule} onNavigate={onNavigate}
            locked={item.locked} disabled={item.locked || (!!activeMeeting && String(item.id) !== String(activeMeeting.id))}
        /></li>)}
        {activities.length === 0 && <li className="learn-agenda-empty">Kegiatan belum tersedia.</li>}
    </ol></>;

    return <div className={`learn-page${type === 'material' && activity.type !== 'task' && activity.type !== 'meeting' ? ' learn-page--material' : ''}`}>
        <Head title={`${activity.title} - ${course.title}`} />
        <div className="learn-shell">
            <header className="learn-header">
                <Link href="/my-course" className="learn-brand" aria-label="Gapura — Kelas Saya"><img src={site.logo} alt="GAPURA" /></Link>
                <span className="learn-header__label">Ruang belajar</span>
            </header>
            <div className={`learn-columns${desktopAgendaOpen ? '' : ' is-agenda-collapsed'}`}>
                <main className="learn-main" id="learn-content">
                    <div className="learn-course-bar">
                        <Link href="/my-course" className="learn-icon-button" aria-label="Kembali ke Kelas Saya"><i className="bx bx-arrow-back" aria-hidden="true" /></Link>
                        <div className="learn-course-bar__title"><span>Kelas Anda</span><h1>{course.title}</h1></div>
                        <button ref={mobileTriggerRef} type="button" className="learn-agenda-toggle is-mobile" aria-haspopup="dialog" aria-expanded={mobileAgendaOpen} aria-controls="learn-mobile-agenda" onClick={() => setMobileAgendaOpen(true)}><i className="bx bx-list-ul" aria-hidden="true" />Agenda</button>
                        {!desktopAgendaOpen && <button ref={desktopTriggerRef} type="button" className="learn-icon-button is-desktop" aria-label="Buka agenda" aria-expanded={false} aria-controls="learn-desktop-agenda" onClick={() => { setDesktopAgendaOpen(true); requestAnimationFrame(() => desktopCloseRef.current?.focus()); }}><i className="bx bx-chevron-left" aria-hidden="true" /></button>}
                    </div>
                    <section className="learn-content-card" aria-label="Aktivitas belajar">
                        <div className="learn-activity-heading">
                            <div><p>Kegiatan pembelajaran</p><h2>{activity.title}</h2></div>
                            {type === 'material' && (activity.type === 'pre-test' || activity.type === 'post-test') && <CountdownTimer id={currentActivityId} initialSeconds={timer} onTimeout={handleTimeout} />}
                            {type === 'material' && (activity.type === 'video' || activity.type === 'literature') && <CountdownTimer currentSeconds={displayTimerSeconds} variant="muted" />}
                        </div>
                        <ol className="learn-stages" aria-label="Tahapan belajar">
                            {(['instruction', 'material', 'result'] as const).map((stage, index) => <li key={stage} className={type === stage ? 'is-active' : ''} aria-current={type === stage ? 'step' : undefined}><span>{index + 1}</span>{['Instruksi', 'Materi', 'Hasil'][index]}</li>)}
                        </ol>
                        <div className="learn-content">
                            {React.Children.map(children, child => React.isValidElement(child) && typeof child.type !== 'string' ? React.cloneElement(child as React.ReactElement<{ onTimeout?: () => void }>, { onTimeout: handleTimeout }) : child)}
                        </div>
                    </section>
                </main>
                <aside id="learn-desktop-agenda" className="learn-desktop-agenda" aria-label="Agenda kegiatan" aria-hidden={!desktopAgendaOpen} ref={element => { if (element) element.inert = !desktopAgendaOpen; }}>
                    <div className="learn-agenda-heading"><button ref={desktopCloseRef} type="button" className="learn-icon-button" aria-label="Sembunyikan agenda" aria-expanded={true} aria-controls="learn-desktop-agenda" onClick={() => { setDesktopAgendaOpen(false); requestAnimationFrame(() => desktopTriggerRef.current?.focus()); }}><i className="bx bx-chevron-right" aria-hidden="true" /></button><h2>Agenda Kegiatan</h2><span className="learn-progress" aria-label={`Progres belajar ${progress}`}><i className="bx bx-hourglass" aria-hidden="true" />{progress}</span></div>
                    {agenda()}
                </aside>
            </div>
            <footer className="learn-footer">&copy; {new Date().getFullYear()} GAPURA. All Rights Reserved.</footer>
        </div>
        <dialog ref={dialogRef} id="learn-mobile-agenda" className="learn-mobile-dialog" aria-labelledby="learn-agenda-title" onCancel={(event) => { event.preventDefault(); setMobileAgendaOpen(false); }} onClose={() => setMobileAgendaOpen(false)} onClick={(event) => { if (event.target === event.currentTarget) setMobileAgendaOpen(false); }} onKeyDown={(event) => {
            if (event.key !== 'Tab') return;
            const focusable = event.currentTarget.querySelectorAll<HTMLElement>('button:not(:disabled), a[href]');
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        }}>
            <div className="learn-mobile-panel">
                <div className="learn-agenda-heading"><h2 id="learn-agenda-title">Agenda Kegiatan</h2><button ref={mobileCloseRef} type="button" className="learn-icon-button" aria-label="Tutup agenda" onClick={() => setMobileAgendaOpen(false)}><i className="bx bx-x" aria-hidden="true" /></button></div>
                <div className="learn-mobile-summary"><p>{course.title}</p><span className="learn-progress" aria-label={`Progres belajar ${progress}`}><i className="bx bx-hourglass" aria-hidden="true" />{progress}</span></div>
                {agenda(() => setMobileAgendaOpen(false))}
            </div>
        </dialog>
    </div>;
}
