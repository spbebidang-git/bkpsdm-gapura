import LearnPdfReader from './LearnPdfReader';
import DOMPurify from "dompurify";
import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import type { ChangeEvent, Dispatch, SetStateAction } from 'react';
import { getYouTubeEmbedUrl } from '@/Utils/youtube';

interface AnswerValue { question?: string; option_select?: string; question_id?: number | string; [key: string]: unknown; }
interface QuestionItem { question?: string; question_id?: number | string; option?: Record<string, string | null>; [key: string]: unknown; }
interface ActivityData { duration?: number; mix?: number; question_items: QuestionItem[]; min_score?: number; is_loop?: number; description?: string; meeting_url?: string; src_video?: string; src_pdf?: string; [key: string]: unknown; }
interface QuestionResult { score?: number; correct?: number; wrong?: number; [key: string]: unknown; }
interface ActivityProps {
    title?: string;
    id: number | string;
    type?: string;
    data: ActivityData;
    slug: string;
    question_result?: QuestionResult;
    status?: string | boolean | null;
    answer?: AnswerValue;
    answers?: Record<string, AnswerValue>;
    showAnswerErrors?: boolean;
    showFileErrors?: boolean;
    setAnswers?: Dispatch<SetStateAction<Record<string, AnswerValue>>>;
    file?: File | null;
    setFile?: (value: File | null) => void;
    schedule?: { id: number | string };
}

const Activity = ({ id, type, data, title, slug, question_result, status, answer, answers, setAnswers, showAnswerErrors = false, showFileErrors = false, file, setFile, schedule }: ActivityProps) => {
    const [duration, setDuration] = useState(data.duration)
    const [activeButton, setActiveButton] = useState(false)

    const [questions, setQuestions] = useState<QuestionItem[]>([]);
    const { errors } = usePage().props


    useEffect(() => {

        if (type === 'pre-test' || type === 'post-test') {
            if (data.mix === 1) {
                // Fungsi untuk mengacak array
                    const shuffleArray = <T,>(array: T[]): T[] => {
                    return array.sort(() => Math.random() - 0.5);
                };

                // Mengacak pertanyaan
                const shuffledQuestions = shuffleArray([...data.question_items]);

                // Mengacak jawaban untuk setiap pertanyaan
                    const shuffledQuestionsWithOptions = shuffledQuestions.map((item) => {
                    const shuffledOptions = shuffleArray(Object.entries(item.option ?? {}));
                    return {
                        ...item,
                        option: Object.fromEntries(shuffledOptions)
                    };
                });

                setQuestions(shuffledQuestionsWithOptions);
            }
            else {
                setQuestions(data.question_items);
            }
        }

        if (type === 'video' || type === 'literature') {
            // setDuration(data.duration)
            if (duration === 0) {
                setActiveButton(true)
                return;
            }
            // Atur interval untuk menghitung mundur
            const interval = setInterval(() => {
                setDuration(prevDuration => {
                    if (prevDuration > 1) {
                        return prevDuration - 1;
                    } else {
                        clearInterval(interval); // Hentikan interval saat durasi mencapai 0
                        setActiveButton(true); // Aktifkan tombol
                        return 0;
                    }
                });
            }, 1000);

            // Bersihkan interval ketika komponen unmount
            return () => clearInterval(interval);
        }
    }, [data, duration]);

    if (type === 'pre-test') {
        const handleAnswerChange = (index: number, answer: string, questionId?: number | string) => {
            setAnswers?.((prevAnswers) => ({
                ...prevAnswers,
                [index]: {
                    question: questions[index]?.question,
                    option_select: answer,
                    question_id: questionId
                }
            }));
        };

        return (
            <div className="learn-questions-scroll" role="region" aria-label="Soal pre-test" tabIndex={0}>
                {questions.map((item, index) => {
                    const option = Object.entries(item.option ?? {}).map(([key, value], index2) => {
                        return (
                            <li key={index2}>
                                <input
                                    type="radio"
                                    id={`option-${index}-${index2}`}
                                    name={`option-${index}`}
                                    value={key}
                                    className="hidden peer"
                                    required
                                    onChange={() => handleAnswerChange(index, value, item.question_id)}
                                />
                                {/* <label
                                    htmlFor={`option-${index}-${index2}`}
                                    className="flex text-sm xl:text-base gap-4 p-2 xl:p-3 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100"
                                >
                                    <div className="font-bold border-2 rounded-full text-sm w-8 h-8 flex items-center justify-center uppercase">
                                        {key}
                                    </div>
                                    <div className="block">
                                        <div className="w-full">{value}</div>
                                    </div>
                                </label> */}
                                <label
                                    htmlFor={`option-${index}-${index2}`}
                                    className="flex text-sm xl:text-base gap-4 p-3 xl:p-4 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100 transition-all"
                                >
                                    {/* 🔹 HURUF ABCD SUDAH DIHAPUS DI SINI 🔹 */}
                                    <div className="block">
                                        <div className="w-full">{value}</div>
                                    </div>
                                </label>
                            </li>
                        );
                    });

                    const isAnswered = answers?.[index]?.option_select !== undefined && answers?.[index]?.option_select !== null;

                    return (
                        <div className={`learn-question-item mb-5 xl:mb-8 px-5${showAnswerErrors && !isAnswered ? ' is-invalid' : ''}`} data-question-index={index} aria-invalid={showAnswerErrors && !isAnswered} key={index}>
                            <h3 tabIndex={-1} className="learn-question-item__title mb-3 text-lg font-medium text-gray-800 dark:text-white">
                                {index + 1 + '. ' + item.question}
                            </h3>
                            <ul className="grid w-full gap-4 md:grid-cols-1">
                                {option}
                            </ul>
                            {showAnswerErrors && !isAnswered && <p className="learn-question-item__error"><i className="bx bx-error-circle" aria-hidden="true" /> Belum dijawab</p>}
                        </div>
                    );
                })}
            </div>
        );
    }

    if (type === 'post-test') {
        const handleAnswerChange = (index: number, answer: string, questionId?: number | string) => {
            setAnswers?.((prevAnswers) => ({
                ...prevAnswers,
                [index]: {
                    question: questions[index]?.question,
                    option_select: answer,
                    question_id: questionId
                }
            }));
        };
        return (
            <div className="">
                <div className="learn-questions-scroll" role="region" aria-label="Soal post-test" tabIndex={0}>
                {questions.map((item, index) => {
                    const option = Object.entries((item.option ?? {}) as Record<string, any>).map(([key, value], index2) => {
                        return (
                            <li key={index2}>
                                <input
                                    type="radio"
                                    id={`option-${index}-${index2}`}
                                    name={`option-${index}`}
                                    value={key}
                                    className="hidden peer"
                                    required
                                    onChange={() => handleAnswerChange(index, value, item.question_id)}
                                />
                                <label
                                    htmlFor={`option-${index}-${index2}`}
                                    className="flex text-sm xl:text-base gap-4 p-2 xl:p-3 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100"
                                >
                                    <div className="font-bold border-2 rounded-full text-sm w-8 h-8 flex items-center justify-center uppercase">
                                        {key}
                                    </div>
                                    <div className="block">
                                        <div className="w-full">{value}</div>
                                    </div>
                                </label>
                            </li>
                        );
                    });

                    const isAnswered = answers?.[index]?.option_select !== undefined && answers?.[index]?.option_select !== null;

                    return (
                        <div className={`learn-question-item mb-5 xl:mb-8 px-5${showAnswerErrors && !isAnswered ? ' is-invalid' : ''}`} data-question-index={index} aria-invalid={showAnswerErrors && !isAnswered} key={index}>
                            <h3 tabIndex={-1} className="learn-question-item__title mb-3 text-lg font-medium text-gray-800 dark:text-white">
                                {index + 1 + '. ' + item.question}
                            </h3>
                            <ul className="grid w-full gap-4 md:grid-cols-1">
                                {option}
                            </ul>
                            {showAnswerErrors && !isAnswered && <p className="learn-question-item__error"><i className="bx bx-error-circle" aria-hidden="true" /> Belum dijawab</p>}
                        </div>
                    );
                })}
                </div>
            </div>
        );
    }

    if (type === 'task') {
        const [fileError, setFileError] = useState<string | null>(null);

        // Fungsi untuk menangani perubahan file
        const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
            const selected = e.target.files?.[0];
            if (!selected) return;
            if (!selected) return;

            // Validasi client-side: hanya PDF, maks 2MB
            if (selected.type !== 'application/pdf') {
                setFileError('Hanya file PDF yang diperbolehkan.');
                e.target.value = null;
                setFile(null);
                return;
            }
            if (selected.size > 2 * 1024 * 1024) {
                setFileError('Ukuran file tidak boleh melebihi 2MB.');
                e.target.value = null;
                setFile(null);
                return;
            }

            setFileError(null);
            setFile(selected);
        };

        return (
            <div className="learn-task">
                <div className="learn-task__panel">
                    <div className="learn-task__intro">
                        <p className="learn-task__eyebrow"><i className="bx bx-task" aria-hidden="true" /> Tugas Kegiatan</p>
                        {data.description && <div className="learn-task__description prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: data.description }} />}
                    </div>

                    <div className="mx-auto w-full max-w-xl">
                        <label
                            htmlFor="task-file"
                            className={`learn-task__dropzone${fileError || (showFileErrors && !file) ? ' is-invalid' : ''}${file && !fileError ? ' is-filled' : ''}`}
                        >
                            <span className="learn-task__icon" aria-hidden="true">
                                <i className={`bx ${file && !fileError ? 'bx-check-circle' : 'bx-cloud-upload'}`} />
                            </span>
                            {file && !fileError ? (
                                <>
                                    <p className="learn-task__drop-title">File siap dikirim</p>
                                    <p className="learn-task__drop-hint">Klik untuk mengganti file</p>
                                </>
                            ) : (
                                <>
                                    <p className="learn-task__drop-title"><span>Klik untuk upload</span> atau seret file ke sini</p>
                                    <p className="learn-task__drop-hint">Hanya format PDF · Maksimum 2MB</p>
                                </>
                            )}
                            <input id="task-file" type="file" accept=".pdf,application/pdf" className="hidden" onChange={handleFileChange} />
                        </label>

                        {file && !fileError && (
                            <p className="learn-task__file-meta">
                                <i className="bx bxs-file-pdf" aria-hidden="true" />
                                <strong>{file.name}</strong>
                                <span>{(file.size / 1024 / 1024).toFixed(2)} MB</span>
                            </p>
                        )}

                        {(fileError || errors.file || (showFileErrors && !file)) && (
                            <div className="learn-task__alert" role="alert" aria-live="assertive">
                                <span className="learn-task__alert-icon" aria-hidden="true"><i className="bx bx-error-circle" /></span>
                                <div className="learn-task__alert-copy">
                                    <strong>Berkas belum valid</strong>
                                    <span>{fileError || errors.file || 'Pilih berkas PDF terlebih dahulu sebelum mengirim tugas.'}</span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        );
    }

    if (type === 'meeting') {
        const handleSubmit = () => {
            router.post(`/learn/${slug}/${schedule.id}/${id}`, { status: 'in_progress' })
        }

        const { flash } = usePage().props

        const handleSubmitDone = () => {
            router.post(`/learn/${slug}/${schedule.id}/${id}`, { status: 'done' })
        }

        const meetingUrl = typeof data.meeting_url === 'string' ? data.meeting_url : '';
        const isDone = status === 'done';
        const isLive = status === 'in_progress';
        const stateClass = isDone ? ' is-done' : isLive ? ' is-live' : '';

        const statusLabel = isDone ? 'Selesai' : isLive ? 'Sedang Berlangsung' : 'Belum Dimulai';
        const statusIcon = isDone ? 'bx-check-circle' : isLive ? 'bx-radio-circle-marked' : 'bx-time-five';
        const description = isDone
            ? 'Terima kasih telah mengikuti pertemuan ini.'
            : isLive
                ? 'Ikuti sesi hingga selesai, lalu tandai pertemuan sebagai selesai.'
                : 'Buka ruang pertemuan, lalu konfirmasikan setelah Anda bergabung.';

        return <div className="learn-meeting">
            <div className={`learn-meeting__panel${stateClass}`}>
                <div className="learn-meeting__head">
                    <span className="learn-meeting__icon" aria-hidden="true"><i className={`bx ${isDone ? 'bx-check-shield' : 'bx-video'}`} /></span>
                    <div className="learn-meeting__heading-copy"><h3>Pertemuan daring</h3><p className="learn-meeting__description">{description}</p></div>
                    <span className={`learn-meeting__status${stateClass}`} role="status">
                        <i className={`bx ${statusIcon}`} aria-hidden="true" />
                        {statusLabel}
                    </span>
                </div>

                {flash?.danger && (
                    <div className="learn-task__alert" role="alert" aria-live="assertive">
                        <span className="learn-task__alert-icon" aria-hidden="true"><i className="bx bx-error-circle" /></span>
                        <div className="learn-task__alert-copy">
                            <strong>Terjadi kesalahan</strong>
                            <span>{flash.danger}</span>
                        </div>
                    </div>
                )}

                {meetingUrl && (
                    <a href={meetingUrl} target="_blank" rel="noreferrer" className="learn-meeting__link-card">
                        <span className="learn-meeting__link-icon" aria-hidden="true"><i className="bx bx-link-external" /></span>
                        <span className="learn-meeting__link-copy">
                            <strong>Buka ruang pertemuan</strong>
                            <span>Terbuka di tab baru · Kembali ke sini setelah bergabung</span>
                        </span>
                        <i className="bx bx-right-arrow-alt" aria-hidden="true" />
                    </a>
                )}

                {data.description && <div className="learn-meeting__about">
                    <h4>Tentang pertemuan</h4>
                    <div className="prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(data.description) }} />
                </div>}

                <div className="learn-meeting__guide">
                    <h4>Alur pertemuan</h4>
                    <ol>
                        {[
                            { title: 'Buka ruang pertemuan', description: 'Gunakan tautan di atas dan bergabung sesuai jadwal yang ditentukan.' },
                            { title: 'Konfirmasi kehadiran Anda', description: 'Setelah masuk ke ruang, kembali ke halaman ini dan tekan “Saya sudah bergabung”.' },
                            { title: 'Ikuti sesi hingga selesai', description: 'Setelah sesi berakhir, tekan “Selesaikan pertemuan” untuk melanjutkan pembelajaran.' },
                        ].map((step, index) => {
                            const completed = isDone || (isLive && index < 2);
                            const current = !isDone && index === (isLive ? 2 : 0);
                            return <li key={step.title} className={`${completed ? 'is-complete' : ''}${current ? ' is-current' : ''}`} aria-current={current ? 'step' : undefined}>
                                <span className="learn-meeting__step-number" aria-hidden="true">{completed ? <i className="bx bx-check" /> : index + 1}</span>
                                <div><strong>{step.title}</strong><p>{step.description}</p></div>
                            </li>;
                        })}
                    </ol>
                </div>

                <div className="learn-meeting__actions">
                    {isDone ? (
                        <Link href={route('participant.learn.result.index', { course: slug, schedule: schedule.id, activity: id })} className="learn-action">
                            Selanjutnya
                            <i className="bx bx-right-arrow-alt" aria-hidden="true" />
                        </Link>
                    ) : isLive ? (
                        <button type="button" onClick={handleSubmitDone} className="learn-action is-secondary">
                            Selesaikan pertemuan
                            <i className="bx bx-log-out" aria-hidden="true" />
                        </button>
                    ) : (
                        <button type="button" onClick={handleSubmit} className="learn-action">
                            Saya sudah bergabung
                            <i className="bx bx-check" aria-hidden="true" />
                        </button>
                    )}
                </div>

                {!meetingUrl && !isDone && <p className="learn-meeting__hint"><i className="bx bx-info-circle" aria-hidden="true" />Tautan belum tersedia. Hubungi penyelenggara untuk mendapatkan akses pertemuan.</p>}

            </div>
        </div>;
    }

    if (type === 'video' || type === 'literature') {
        const isVideo = type === 'video';
        const materialTitle = title || (isVideo ? 'Materi video' : 'Materi bacaan');
        const videoUrl = isVideo ? getYouTubeEmbedUrl(data.src_video) : null;

        return (
            <article className="learn-media" aria-label={materialTitle}>
                <div className={isVideo ? 'learn-media__player' : 'learn-media__reader'}>
                    {isVideo ? (videoUrl ? <iframe
                        src={`${videoUrl}?rel=0`}
                        title={materialTitle}
                        referrerPolicy="strict-origin-when-cross-origin"
                        allowFullScreen
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    /> : <div className="learn-media__video-unavailable" role="status">
                        Video tidak dapat dimuat. Periksa kembali tautan video atau hubungi admin.
                    </div>) : <LearnPdfReader src={data.src_pdf} title={materialTitle} />}
                </div>
                <div className="learn-media__copy">
                    <h3>{materialTitle}</h3>
                    {data.description && <div className="learn-media__description prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(data.description) }} />}
                </div>
            </article>
        );
    }

    return null;
};


export default Activity;
