import App from "@/Layouts/Participant/App";
import { Link, router } from "@inertiajs/react";

const Index = ({ courses }) => {
    const handleSubmit = (e, slug) => {
        e.preventDefault();

        router.post(route("participant.certificate"), {
            course: slug,
        });
    };

    const handleDownload = async (slug) => {
        try {
            const response = await axios.post(
                route("participant.certificate"),
                { course: slug },
                {
                    responseType: "blob",
                }
            );

            // Ambil nama file dari header `Content-Disposition`
            const contentDisposition = response.headers["content-disposition"];
            let fileName = "certificate.pdf"; // Default nama file

            if (contentDisposition) {
                const match = contentDisposition.match(/filename="(.+)"/);
                if (match.length > 1) {
                    fileName = match[1];
                }
            }

            // Buat URL Blob untuk unduhan
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", fileName);
            document.body.appendChild(link);
            link.click();

            // Hapus elemen setelah unduhan
            document.body.removeChild(link);
        } catch (error) {
            console.error("Gagal mengunduh sertifikat:", error);
        }
    };

    return (
        <App>
            <div className="max-w-5xl mx-auto">
                <div className="border bg-white rounded-xl shadow p-5 mb-6 text-xl font-bold text-center">
                    Kelas Saya
                </div>
                <div className="grid grid-cols-3 gap-5">
                    {courses.map((item, index) => {
                        return (
                            <div className="rounded-xl col-span-3 xl:col-span-1 overflow-hidden shadow ">
                                <div className="bg-red-200 h-36">
                                    <img
                                        src={item.thumbnail}
                                        className="w-full h-full object-fill"
                                        alt={item.title}
                                    />
                                </div>
                                <div className="p-5 bg-white">
                                    <div className="mb-2.5">
                                        <div className="text-lg text-gray-700 font-semibold">
                                            {item.title}
                                        </div>
                                        <div className="text-gray-700">
                                            {item.short_description}
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Link
                                            className="w-full"
                                            href={`/course/${item.slug}`}
                                        >
                                            <button className="button-primary">
                                                Detail
                                            </button>
                                        </Link>
                                        {item.finished_at == null ? (
                                            <a
                                                className="w-full"
                                                href={`/learn/${item.slug}/${item.schedule_id}/${item.initial_activity}/instruction`}
                                            >
                                                <button className="button-primary">
                                                    Ikuti Kelas
                                                </button>
                                            </a>
                                        ) : (
                                            <button
                                                className="button-primary"
                                                onClick={() =>
                                                    handleDownload(item.slug)
                                                }
                                            >
                                                Unduh Sertifikat
                                            </button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </App>
    );
};

export default Index;
