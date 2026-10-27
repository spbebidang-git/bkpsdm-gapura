import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import ErrorMessage from '@/Components/ErrorMessage';
import FormInput from '@/Components/FormInput';
import FormTextArea from '@/Components/FormTextArea';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import '@/../css/admin-assessment.css';

export default function QuestionForm({ slug, activityId, questionId, question = null }) {
    const base = `/admin/course/${slug}/activity/variant/${activityId}/${questionId}/question-item`;
    const title = question ? 'Ubah soal' : 'Tambah soal';
    const { data, setData, post, put, processing, errors } = useForm({
        description: question?.description ?? '', score: question?.score ?? 1,
        options: { option1: question?.option1 ?? '', option2: question?.option2 ?? '', option3: question?.option3 ?? '', option4: question?.option4 ?? '' },
        correct_answer: String(question?.correct_answer ?? '1'),
    });
    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (question) put(`${base}/${question.id}`);
        else post(base);
    };
    return <AdminLayout title={title} hideTitle>
        <Head title={title} />
        <div className="assessment-admin assessment-form assessment-question-page">
            <CrudPageHeader title={title} description="Tulis pertanyaan, empat pilihan jawaban, dan pilih satu kunci jawaban." actions={<>
                <Button variant="secondary" href={base} className="min-w-28"><i className="bx bx-arrow-back" aria-hidden />Batal</Button>
                <Button className="min-w-28" type="submit" form="question-form" loading={processing}>{processing ? 'Menyimpan…' : 'Simpan'}</Button>
            </>} />
            <form id="question-form" noValidate onSubmit={submit} aria-busy={processing} className="admin-crud-form">
                <CrudFormSection title="Informasi soal" description="Skor diberikan saat pengguna memilih jawaban yang benar." icon="bx-help-circle">
                    <FormTextArea id="description" label="Pertanyaan" value={data.description} onChange={(e) => setData('description', e.target.value)} error={errors.description} rows={5} isRequired placeholder="Tulis pertanyaan di sini" />
                    <div><FormInput id="score" label="Skor jawaban benar" type="number" min={1} value={data.score} onChange={(e) => setData('score', e.target.value)} error={errors.score} small="Masukkan skor antara 1–100." isRequired /></div>
                </CrudFormSection>
                <CrudFormSection title="Pilihan jawaban" description="Isi empat pilihan, lalu pilih satu kunci jawaban." icon="bx-list-check">
                    <fieldset className="question-answer-options">
                        <legend className="sr-only">Pilihan jawaban dan kunci jawaban</legend>
                        {(['option1', 'option2', 'option3', 'option4'] as const).map((key, index) => {
                            const letter = String.fromCharCode(65 + index);
                            const selected = data.correct_answer === String(index + 1);
                            return <div className={`question-answer-option ${selected ? 'is-selected' : ''}`} key={key}>
                                <div className="question-answer-option__header">
                                    <label htmlFor={key} className="question-answer-option__title"><span>{letter}</span>Jawaban {letter}</label>
                                    <label className="question-answer-option__select">
                                        <input type="radio" name="correct_answer" value={String(index + 1)} checked={selected} onChange={(e) => setData('correct_answer', e.target.value)} aria-label={`Jadikan jawaban ${letter} sebagai kunci jawaban`} />
                                        <span>{selected ? 'Kunci jawaban' : 'Jadikan kunci'}</span>
                                    </label>
                                </div>
                                <FormTextArea rows={3} id={key} label={`Jawaban ${letter}`} className="sr-only" value={data.options[key]} onChange={(e) => setData('options', { ...data.options, [key]: e.target.value })} error={errors[`options.${key}`]} isRequired placeholder={`Tulis jawaban ${letter} di sini…`} />
                            </div>;
                        })}
                    </fieldset>
                    <ErrorMessage message={errors.correct_answer} />
                </CrudFormSection>
            </form>
        </div>
    </AdminLayout>;
}
