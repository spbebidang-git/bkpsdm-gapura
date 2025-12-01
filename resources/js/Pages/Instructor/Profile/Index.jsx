import FormInput from "@/Components/FormInput";
import App from "@/Layouts/Instructor/App";

const Index = ({ instructor }) => {
    const fallbackImage = "/images/default-profile.png"; // siapkan gambar default

    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">
                Profile
            </div>

            {/* ALERT INFO */}
            <div className="max-w-5xl mx-auto mb-5">
                <div className="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                    Untuk perubahan data silakan hubungi admin di no 08xxxxxx atau email admin@example.com
                </div>
            </div>

            <div className="grid grid-cols-12 gap-8 max-w-5xl mx-auto">

                {/* FOTO PROFILE */}
                <div className="col-span-3">
                    <div className="w-full h-36 border bg-white rounded-lg overflow-hidden">
                        <img
                            src={instructor.profile_picture || fallbackImage}
                            onError={(e) => (e.target.src = fallbackImage)}
                            className="w-full h-full object-cover"
                            alt="Profile Picture"
                        />
                    </div>
                </div>

                {/* DATA PROFILE */}
                <div className="col-span-9 bg-white rounded-lg shadow p-5">
                    <FormInput
                        title="nama"
                        value={instructor.name}
                        readOnly
                    />

                    <FormInput
                        title="email"
                        value={instructor.email}
                        readOnly
                    />
                </div>

            </div>
        </App>
    );
};

export default Index;
