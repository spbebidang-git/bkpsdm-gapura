import { CrudFormSection, CrudPageHeader } from '@/Components/Admin/CrudPrimitives';
import Button from '@/Components/Button';
import FormInput from '@/Components/FormInput';
import FormTextArea from '@/Components/FormTextArea';
import AdminLayout from '@/Layouts/Admin/App';
import { Head, useForm } from '@inertiajs/react';
import '@/../css/admin-notification-message.css';

type Template = { event_key: string; group: string; label: string; title: string; message: string };
type TemplateValues = { title: string; message: string };

export default function Index({ templates }: { templates: Template[] }) {
    const initialTemplates: Record<string, TemplateValues> = Object.fromEntries(
        templates.map((template) => [template.event_key, { title: template.title, message: template.message }]),
    );
    const { data, setData, put, processing, errors } = useForm({ templates: initialTemplates });
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
        </div>
    </AdminLayout>;
}
