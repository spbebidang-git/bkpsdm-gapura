import App from '@/Layouts/Participant/App';
import { Head, usePage } from '@inertiajs/react';
import { ParticipantCard } from '@/Components/Participant/Primitives';
import '../../../../css/participant-pages.css';

export default function Index() {
    const { profile } = usePage().props;
    const name = profile.name?.trim() || 'Pengguna GAPURA';
    const initials = name.split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase();
    return <App>
        <Head title="Profil Saya" />
        <div>
            <header className="participant-page-header"><div><h1>Profil Saya</h1><p>Informasi identitas Anda dalam sistem pembelajaran Gapura.</p></div></header>
            <div className="grid items-start gap-6 lg:grid-cols-[.85fr_1.65fr]">
                <ParticipantCard>
                    <div className="mb-6 h-2 w-12 rounded-full bg-[linear-gradient(90deg,#019B91,#018083)]" />
                    <div className="grid h-20 w-20 place-items-center rounded-full participant-icon-surface font-poppins text-2xl font-semibold text-primary" aria-label={`Inisial ${name}`}>{initials}</div>
                    <p className="participant-eyebrow mt-6">Peserta GAPURA</p>
                    <h2 className="break-words text-xl leading-8">{name}</h2>
                    <p className="mt-3 text-sm leading-6 text-[#526176]">Identitas peserta untuk pengembangan kompetensi menuju SMART ASN.</p>
                </ParticipantCard>
                <div className="space-y-5">
                    <ParticipantCard>
                        <h2 className="mb-5 text-xl">Identitas peserta</h2>
                        <dl className="divide-y divide-[#E8ECF1]">{[{ label: 'Nama', value: profile.name, icon: 'user' }, { label: 'Email', value: profile.email, icon: 'envelope' }, { label: 'NIP', value: profile.nip, icon: 'badge' }, { label: 'Unit Kerja', value: profile.unit_kerja, icon: 'buildings' }, { label: 'Status Pegawai', value: profile.status_pegawai, icon: 'briefcase' }].map(item => <div key={item.label} className="flex items-start gap-4 py-5 first:pt-0 last:pb-0"><span className="grid h-10 w-10 shrink-0 place-items-center rounded-[10px] participant-icon-surface"><box-icon name={item.icon} color="#018083" size="21px" aria-hidden="true" /></span><div className="min-w-0"><dt className="text-xs font-medium text-[#526176]">{item.label}</dt><dd className="mt-1.5 break-words text-sm font-medium leading-6 text-[#1A2B3F]">{item.value && item.value !== '-' ? item.value : 'Belum tersedia'}</dd></div></div>)}</dl>
                    </ParticipantCard>
                    <div className="flex items-start gap-3 rounded-[15px] border border-[#BDE8E2] bg-[#F5FBFA] p-5"><span className="shrink-0"><box-icon name="info-circle" color="#018083" size="22px" aria-hidden="true" /></span><p className="text-sm leading-6 text-[#526176]">Pastikan informasi kepegawaian Anda sesuai. Hubungi administrator GAPURA jika terdapat data yang perlu diperbarui.</p></div>
                </div>
            </div>
        </div>
    </App>;
}
