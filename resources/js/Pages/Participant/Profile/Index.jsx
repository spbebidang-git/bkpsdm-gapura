import BreadcrumbLearn from "@/Components/BreadcrumbLearn";
import App from "@/Layouts/Participant/App";
import { Link, usePage } from "@inertiajs/react";

const Index = () => {
    const { profile } = usePage().props;

    return (
        <App>
            <div className="flex justify-center mb-10 w-full relative z-10">
                <div className="inline-flex flex-wrap items-center gap-2 md:gap-4 bg-white/90 backdrop-blur-md py-3 px-8 rounded-full shadow-[0_4px_25px_-4px_rgba(0,0,0,0.06)] border border-gray-100/80 transition-all hover:shadow-[0_4px_30px_-4px_rgba(0,0,0,0.1)]">
                    <Link href="/dashboard" className="transition-transform hover:-translate-y-0.5 opacity-80 hover:opacity-100">
                        <BreadcrumbLearn title="Dashboard" activeTab={false} icon="grid-alt" size="lg" />
                    </Link>
                    <box-icon name="chevron-right" color="#cbd5e1" size="22px"></box-icon>
                    <BreadcrumbLearn title="Profile Saya" activeTab={true} icon="user" size="lg" />
                </div>
            </div>
            <div className="max-w-3xl mx-auto bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden relative">
                {/* Background Banner */}
                <div className="h-32 bg-gradient-to-r from-primary to-blue-400 absolute w-full top-0 left-0 z-0"></div>

                <div className="relative z-10 p-8 pt-16 flex flex-col items-center text-center">
                    <img
                        className="h-32 w-32 rounded-full object-cover border-4 border-white shadow-md bg-white mb-4 transition-transform hover:scale-105 duration-300"
                        src="assets/media/images/static/default-avatar.jpg"
                        alt="User avatar"
                    />
                    <h2 className="text-3xl font-bold text-gray-800 tracking-tight mb-2">
                        {profile.name}
                    </h2>
                    <span className="bg-secondary text-white text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider shadow-sm">
                        Verified
                    </span>

                    <div className="mt-10 w-full grid grid-cols-1 md:grid-cols-3 gap-4">
                        <ProfileItem label="NIP" value={profile.nip} />
                        <ProfileItem label="Status Pegawai" value={profile.status_pegawai} />
                        <ProfileItem label="Jabatan" value={profile.jabatan} />
                    </div>
                </div>
            </div>
        </App>
    );
};

const ProfileItem = ({ label, value }) => (
    <div className="bg-gray-50/50 hover:bg-white rounded-2xl p-5 border border-gray-100 hover:shadow-md hover:border-primary/20 transition-all duration-300 group">
        <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 group-hover:text-primary transition-colors">{label}</p>
        <p className="text-lg font-bold text-gray-800 break-words">{value}</p>
    </div>
);

export default Index;
