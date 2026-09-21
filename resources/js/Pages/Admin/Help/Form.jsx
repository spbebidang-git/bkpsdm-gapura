import {
    btnPrimary,
    btnSecondary,
    card,
    fieldError,
    pageDescription,
    pageEyebrow,
    pageHeader,
    pageTitle,
} from '@/Components/Admin/ui';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';

export default function Form({ help }) {
    const isEdit = !!help;
    const { data, setData, post, put, processing, errors } = useForm({
        question: help?.question ?? '',
        answer: help?.answer ?? '',
        is_active: help?.is_active ?? true,
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.help.update', help.id));
        } else {
            post(route('admin.help.store'));
        }
    };

    return (
        <AdminLayout title={isEdit ? 'Edit Bantuan' : 'Buat Bantuan'} hideTitle>
            <Head title={isEdit ? 'Edit Bantuan' : 'Buat Bantuan'} />
            <div className="max-w-none">
                <div className={pageHeader}>
                    <div className="min-w-0">
                        <p className={pageEyebrow}>Bantuan</p>
                        <h1 className={pageTitle}>{isEdit ? 'Edit pertanyaan' : 'Buat pertanyaan'}</h1>
                        <p className={pageDescription}>
                            {isEdit
                                ? `Perbarui isi dan status tampilan untuk pertanyaan ini.`
                                : 'Susun pertanyaan yang sering diajukan peserta beserta jawabannya.'}
                        </p>
                    </div>
                    <Link href={route('admin.help.index')} className={btnSecondary}>
                        <i className="bx bx-left-arrow-alt text-lg" aria-hidden />
                        Kembali
                    </Link>
                </div>

                <form onSubmit={submit} className={`${card} mt-6 max-w-none space-y-6 p-5 sm:p-7`}>
                    <div className="space-y-6">
                        <div>
                            <label htmlFor="question" className="form-label">Pertanyaan <span className="text-[#A43B2F]">*</span></label>
                            <input
                                id="question"
                                type="text"
                                value={data.question}
                                onChange={(e) => setData('question', e.target.value)}
                                className="form-control"
                                placeholder="Contoh: Bagaimana cara mendaftar kelas?"
                                required
                            />
                            {errors.question && <p className={fieldError}>{errors.question}</p>}
                        </div>
                        <div>
                            <label htmlFor="answer" className="form-label">Jawaban <span className="text-[#A43B2F]">*</span></label>
                            <div>
                                <ReactQuill
                                    theme="snow"
                                    value={data.answer}
                                    onChange={(value) => setData('answer', value)}
                                    className="rounded-lg overflow-hidden help-quill"
                                    placeholder="Jelaskan langkah penyelesaian secara runtut dan mudah dipahami peserta."
                                />
                                <style>{`.help-quill .ql-editor { min-height: 320px; }`}</style>
                            </div>
                            {errors.answer && <p className={fieldError}>{errors.answer}</p>}
                        </div>

                        <div className="border-t border-hairline pt-5">
                            <label htmlFor="is_active" className="form-label">Status</label>
                            <select
                                id="is_active"
                                value={data.is_active ? '1' : '0'}
                                onChange={(e) => setData('is_active', e.target.value === '1')}
                                className="form-control"
                            >
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                            {errors.is_active && <p className={fieldError}>{errors.is_active}</p>}
                        </div>
                    </div>

                    <div className="flex flex-col-reverse gap-3 border-t border-hairline pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-xs text-steel"><span className="text-[#A43B2F]">*</span> Wajib diisi sebelum menyimpan bantuan.</p>
                        <div className="flex flex-col-reverse gap-3 sm:flex-row">
                            <Link href={route('admin.help.index')} className={btnSecondary}>Batal</Link>
                            <button type="submit" disabled={processing} className={btnPrimary}>
                                <i className="bx bx-save text-lg" aria-hidden />
                                {processing ? 'Menyimpan...' : isEdit ? 'Simpan perubahan' : 'Simpan Bantuan'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
