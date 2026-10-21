import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import FormInput from '@/Components/FormInput';
import FormTextArea from '@/Components/FormTextArea';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import '@/../css/admin-notification-message.css';

type Template = { event_key: string; group: string; label: string; title: string; message: string; type?: string };
type TemplateValues = { title: string; message: string };
type Option = { id: string; name?: string; nip?: string | null; title?: string };

export default function Index({ templates, participants = [], courses = [] }: { templates: Template[]; participants: Option[]; courses: Option[] }) {
    const initialTemplates: Record<string, TemplateValues> = Object.fromEntries(
        templates.map((template) => [template.event_key, { title: template.title, message: template.message }]),
    );
    const { data, setData, put, processing, errors } = useForm({ templates: initialTemplates });
    const testForm = useForm({ participant_id: '', course_id: '', event_key: templates[0]?.event_key || '' });
    const groups = templates.reduce<Record<string, Template[]>>((result, template) => {
        (result[template.group] ||= []).push(template);
        return result;
    }, {});

    const updateField = (eventKey: string, field: keyof TemplateValues, value: string) => {
        setData('templates', {
            ...data.templates,
            [eventKey]: { ...data.templates[eventKey], [field]: value },
        });
    };

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        put(route('admin.management.notification-messages.update'));
    };

    return <AdminLayout title="Pesan notifikasi" hideTitle>
        <Head title="Pesan notifikasi" />
        <div className="notification-message-admin">
            <CrudPageHeader title="Pesan notifikasi" description="Atur pesan otomatis yang diterima peserta." actions={
                <Button type="submit" form="notification-message-form" loading={processing} className="min-w-28">
                    {processing ? 'Menyimpan…' : 'Simpan'}
                </Button>
            } />
            <form id="notification-message-form" noValidate onSubmit={submit} aria-busy={processing} className="admin-crud-form">
                {Object.entries(groups).map(([group, groupTemplates]) => <CrudFormSection key={group} title={group} icon="bx-bell" description="Sesuaikan judul dan isi pesan untuk setiap peristiwa.">
                    <div className="notification-message-list">
                        {groupTemplates.map((template) => <div key={template.event_key} className="notification-message-row">
                            <div className="notification-message-context">
                                <h3>{template.label}</h3>
                                {template.message.includes(':course_name') && <p><code>:course_name</code> otomatis diisi nama kelas.</p>}
                            </div>
                            <div className="notification-message-fields">
                                <FormInput id={`${template.event_key}_title`} label="Judul" value={data.templates[template.event_key].title}
                                    onChange={(event) => updateField(template.event_key, 'title', event.target.value)}
                                    error={errors[`templates.${template.event_key}.title`]} />
                                <FormTextArea id={`${template.event_key}_message`} label="Isi pesan" rows={3} inputClassName="resize-y"
                                    value={data.templates[template.event_key].message}
                                    onChange={(event) => updateField(template.event_key, 'message', event.target.value)}
                                    error={errors[`templates.${template.event_key}.message`]} />
                            </div>
                        </div>)}
                    </div>
                </CrudFormSection>)}
            </form>
            <CrudFormSection title="Uji notifikasi peserta" icon="bx-send" description="Kirim salinan pesan yang sudah tersimpan ke kotak notifikasi peserta yang dipilih.">
                <form noValidate onSubmit={(event) => { event.preventDefault(); testForm.post(route('admin.management.notification-messages.test')); }} className="grid gap-4 md:grid-cols-2">
                    <label className="grid gap-1.5 text-sm font-medium text-ink">Peserta
                        <select className="form-control" value={testForm.data.participant_id} onChange={(event) => testForm.setData('participant_id', event.target.value)} required>
                            <option value="">Pilih peserta</option>{participants.map((participant) => <option key={participant.id} value={participant.id}>{participant.name}{participant.nip ? ` · ${participant.nip}` : ''}</option>)}
                        </select>{testForm.errors.participant_id && <span className="text-xs text-red-600">{testForm.errors.participant_id}</span>}
                    </label>
                    <label className="grid gap-1.5 text-sm font-medium text-ink">Kelas
                        <select className="form-control" value={testForm.data.course_id} onChange={(event) => testForm.setData('course_id', event.target.value)} required>
                            <option value="">Pilih kelas</option>{courses.map((course) => <option key={course.id} value={course.id}>{course.title}</option>)}
                        </select>{testForm.errors.course_id && <span className="text-xs text-red-600">{testForm.errors.course_id}</span>}
                    </label>
                    <label className="grid gap-1.5 text-sm font-medium text-ink">Jenis notifikasi
                        <select className="form-control" value={testForm.data.event_key} onChange={(event) => testForm.setData('event_key', event.target.value)} required>
                            {templates.map((template) => <option key={template.event_key} value={template.event_key}>{template.label}</option>)}
                        </select>{testForm.errors.event_key && <span className="text-xs text-red-600">{testForm.errors.event_key}</span>}
                    </label>
                    <div className="flex items-end"><Button type="submit" loading={testForm.processing} disabled={!participants.length || !courses.length}>
                        {testForm.processing ? 'Mengirim…' : 'Kirim notifikasi uji'}
                    </Button></div>
                </form>
            </CrudFormSection>
        </div>
    </AdminLayout>;
}
