import { Head, Link, router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { toast } from 'react-toastify';
import '../../../../css/participant-rating.css';

const ratingLabels: Record<number, string> = {
    1: 'Sangat kurang',
    2: 'Kurang',
    3: 'Cukup',
    4: 'Baik',
    5: 'Sangat baik',
};

const Index = ({ course, progress, schedule_id }) => {
    const { site } = usePage().props;
    const firstStarRef = useRef<HTMLInputElement>(null);
    const [rating, setRating] = useState(0);
    const [description, setDescription] = useState('');
    const [validationAttempted, setValidationAttempted] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const ratingMissing = rating < 1;
    const showRatingError = validationAttempted && ratingMissing;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (submitting) return;

        if (ratingMissing) {
            setValidationAttempted(true);
            firstStarRef.current?.focus();
            return;
        }

        setValidationAttempted(false);
        setSubmitting(true);

        router.post(
            route('participant.rating.store', [course.slug, schedule_id]),
            { rating, description: description.trim() },
            {
                onError: (errors) => {
                    toast.error(
                        errors.rating || errors.description || errors.message || 'Gagal menyimpan penilaian. Silakan coba lagi.',
                        {
                            toastId: 'rating-error',
                            position: 'top-center',
                            autoClose: 5000,
                            theme: 'colored',
                        },
                    );
                },
                onFinish: () => setSubmitting(false),
            },
        );
    };

    return (
        <div className="rating-page">
            <Head title={`Penilaian Kelas - ${course.title}`} />
            <div className="rating-shell">
                <header className="rating-header">
                    <Link href="/my-course" className="rating-brand" aria-label="Gapura — Kelas Saya">
                        <img src={site?.logo} alt="GAPURA" />
                    </Link>
                    <div className="rating-header__course">
                        <span>Kelas Anda</span>
                        <h1>{course.title}</h1>
                    </div>
                    <span className="rating-progress" aria-label={`Progres belajar ${progress}`}>
                        <i className="bx bx-hourglass" aria-hidden="true" />
                        {progress}
                    </span>
                </header>

                <main className="rating-body">
                    <section className="rating-card" aria-labelledby="rating-title">
                        <div className="rating-card__head">
                            <span className="rating-complete"><i className="bx bx-check-circle" aria-hidden="true" />Pembelajaran selesai</span>
                            <div>
                                <p className="rating-card__eyebrow">Penilaian kelas</p>
                                <h2 id="rating-title" className="rating-card__title">Bagaimana pengalaman belajar Anda?</h2>
                                <p className="rating-card__description">Bagikan penilaian dan masukan Anda agar kelas berikutnya semakin baik.</p>
                            </div>
                            <div className="rating-next-step"><i className="bx bx-right-arrow-circle" aria-hidden="true" /><p>Setelah penilaian dikirim, Anda akan melanjutkan ke survei kelas.</p></div>
                        </div>

                        <form onSubmit={handleSubmit} noValidate className="rating-form">
                            <fieldset className="rating-choice" aria-describedby={showRatingError ? 'rating-star-error' : 'rating-star-help'} aria-invalid={showRatingError}>
                                <legend>Penilaian Anda <span className="rating-required">(wajib)</span></legend>
                                <p id="rating-star-help" className="rating-form__hint">Pilih bintang yang sesuai dengan pengalaman Anda.</p>
                                <div className="rating-stars">
                                    {[1, 2, 3, 4, 5].map((star) => (
                                        <label key={star} className={`rating-stars__option${rating === star ? ' is-selected' : ''}${rating >= star ? ' is-active' : ''}`}>
                                            <input ref={star === 1 ? firstStarRef : undefined} type="radio" name="rating" value={star} checked={rating === star} onChange={() => setRating(star)} disabled={submitting} aria-label={`${star} bintang — ${ratingLabels[star]}`} />
                                            <i className="bx bxs-star" aria-hidden="true" />
                                            <span>{ratingLabels[star]}</span>
                                        </label>
                                    ))}
                                </div>
                                <p className="rating-stars__label" aria-live="polite">{rating ? <><strong>{rating} dari 5</strong> · {ratingLabels[rating]}</> : 'Belum ada penilaian dipilih'}</p>
                                {showRatingError && <p id="rating-star-error" className="rating-error" role="alert">Pilih penilaian sebelum mengirim.</p>}
                            </fieldset>

                            <div className="rating-field">
                                <label htmlFor="rating-description" className="rating-field__label">Ceritakan pengalaman Anda <span className="rating-required">(opsional)</span></label>
                                <p id="rating-comment-help" className="rating-form__hint">Apa yang bermanfaat, dan apa yang bisa kami perbaiki?</p>
                                <textarea
                                    id="rating-description"
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    placeholder="Tuliskan kesan, kendala, atau saran Anda selama mengikuti kelas…"
                                    rows={6}
                                    maxLength={1000}
                                    disabled={submitting}
                                    aria-describedby="rating-comment-help"
                                    className="rating-field__input"
                                />
                                <div className="rating-field__meta"><span>Masukan singkat Anda sangat berarti.</span><span>{description.length}/1000</span></div>
                            </div>

                            <div className="rating-actions">
                                <span className="rating-thanks">Terima kasih atas masukan Anda.</span>
                                <button type="submit" className="rating-submit" disabled={submitting} aria-busy={submitting}>
                                    {submitting ? 'Mengirim…' : 'Kirim penilaian'}
                                    <i className={`bx ${submitting ? 'bx-loader-alt bx-spin' : 'bx-right-arrow-alt'}`} aria-hidden="true" />
                                </button>
                            </div>
                        </form>
                    </section>
                </main>

                <footer className="rating-footer">&copy; {new Date().getFullYear()} GAPURA. All Rights Reserved.</footer>
            </div>
        </div>
    );
};

export default Index;
