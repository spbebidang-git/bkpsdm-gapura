import {
    btnPrimary,
} from '@/Components/Admin/ui';
import { CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import ErrorMessage from '@/Components/ErrorMessage';
import FormInput from '@/Components/FormInput';
import FormLabel from '@/Components/FormLabel';
import FormSelect from '@/Components/FormSelect';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, Link, useForm } from '@inertiajs/react';
import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';
import '@/../css/admin-form.css';

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
            <div className="admin-managed-form">
                <CrudPageHeader
                    title={isEdit ? 'Edit pertanyaan' : 'Buat pertanyaan'}
                    description={isEdit ? 'Perbarui isi dan status tampilan untuk pertanyaan ini.' : 'Susun pertanyaan yang sering diajukan peserta beserta jawabannya.'}
                    actions={<div className="admin-form-header-actions">
                        <Link href={route('admin.help.index')} className="admin-form-back">
                            <i className="bx bx-left-arrow-alt text-lg" aria-hidden />
                            Kembali
                        </Link>
                        <button type="submit" form="help-form" disabled={processing} className={btnPrimary}>Simpan</button>
                    </div>}
                />

                <form id="help-form" noValidate onSubmit={submit} className="admin-managed-form-card admin-managed-form-card--wide">
                    <div className="space-y-6">
                        <div>
                            <FormInput
                                id="question"
                                label="Pertanyaan"
                                isRequired
                                required
                                value={data.question}
                                onChange={(e) => setData('question', e.target.value)}
                                error={errors.question}
                                placeholder="Contoh: Bagaimana cara mendaftar kelas?"
                            />
                        </div>
                        <div>
                            <FormLabel title="Jawaban" id="answer" isRequired />
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
                            <ErrorMessage message={errors.answer} />
                        </div>

                        <div className="border-t border-hairline pt-5">
                            <FormSelect
                                id="is_active"
                                label="Status"
                                value={data.is_active ? '1' : '0'}
                                onChange={(e) => setData('is_active', e.target.value === '1')}
                                error={errors.is_active}
                                options={[
                                    { value: '1', label: 'Aktif' },
                                    { value: '0', label: 'Nonaktif' },
                                ]}
                                emptyOption={null}
                            />
                        </div>
                    </div>

                    <div className="admin-managed-form-footer">
                        <p className="text-xs text-steel"><span className="text-[#A43B2F]">*</span> Wajib diisi sebelum menyimpan bantuan.</p>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
