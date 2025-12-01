import App from "@/Layouts/Participant/App";
import { usePage } from "@inertiajs/react";

const Index = () => {
    const { profile } = usePage().props;

    return (
        <App>
            <div className="max-w-2xl mx-auto border bg-white rounded-xl shadow p-5 mb-6 text-xl font-bold text-center">
                Profile
            </div>
            <div className="max-w-2xl mx-auto mt-6 bg-white rounded-xl shadow p-8 border text-center">
                <div className="flex flex-col items-center space-y-4">
                    <img
                        className="h-32 w-32 rounded-full object-cover border-4 border-gray-300"
                        src="assets/media/images/static/default-avatar.jpg"
                        alt="User avatar"
                    />
                    <h2 className="text-2xl font-bold text-gray-800">
                        {profile.name}
                    </h2>
                    <span className="bg-secondary text-white text-xs font-semibold px-3 py-1 rounded-full">
                        Verified
                    </span>
                </div>

                <div className="mt-8 space-y-5 text-center">
                    <ProfileItem label="NIP" value={profile.nip} />
                    <ProfileItem label="Status Pegawai" value={profile.status_pegawai} />
                    <ProfileItem label="Jabatan" value={profile.jabatan} />
                </div>
            </div>
        </App>
    );
};

const ProfileItem = ({ label, value }) => (
    <div>
        <p className="text-sm text-gray-500">{label}</p>
        <p className="text-lg font-medium text-gray-800">{value}</p>
    </div>
);

export default Index;
