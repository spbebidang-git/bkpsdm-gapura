import BreadcrumbLearn from "@/Components/BreadcrumbLearn";
import App from "@/Layouts/Participant/App";
import { Link, router } from "@inertiajs/react";

const Show = ({ course, learn }) => {
    const handleSubmit = (e, slug) => {
        e.preventDefault();

        router.post(
            route("participant.certificate"),
            { course: slug },
            {
                onSuccess: (page) => {
                    const sertifikatUrl = page.props.redirect_url;
                    if (sertifikatUrl) window.open(sertifikatUrl, "_blank");
                },
            }
        );
    };



    return (
        <App>
            <div className="max-w-7xl mx-auto">

                <div className="flex justify-center mb-10 w-full relative z-10">
                    <div className="inline-flex flex-wrap items-center gap-2 md:gap-4 bg-white/90 backdrop-blur-md py-3 px-8 rounded-full shadow-[0_4px_25px_-4px_rgba(0,0,0,0.06)] border border-gray-100/80 transition-all hover:shadow-[0_4px_30px_-4px_rgba(0,0,0,0.1)]">
                        <Link href={route("participant.course.index")} className="transition-transform hover:-translate-y-0.5 opacity-80 hover:opacity-100">
                            <BreadcrumbLearn title="Kelas Saya" activeTab={false} icon="book-bookmark" size="lg" />
                        </Link>
                        <box-icon name="chevron-right" color="#cbd5e1" size="22px"></box-icon>
                        <BreadcrumbLearn title={course.title} activeTab={true} icon="chalkboard" size="lg" />
                    </div>
                </div>

                <div className="flex flex-col gap-8 mb-8">
                    {/* LEADING CONTENT */}
                    <div className="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 md:p-8 w-full">


                        <div className="flex flex-col lg:flex-row gap-6 md:gap-8">
                            {/* Thumbnail */}
                            <div className="w-full lg:w-1/3 shrink-0">
                                <div className="aspect-video lg:aspect-square w-full rounded-2xl overflow-hidden shadow-sm relative group">
                                    <div className="absolute top-3 right-3 bg-secondary/90 backdrop-blur-sm text-white text-xs font-bold px-3 py-1.5 rounded-full z-10 uppercase tracking-wider shadow-sm">
                                        {course.employee_status}
                                    </div>
                                    <img
                                        src={course.thumbnail}
                                        alt={course.title}
                                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    />
                                </div>
                            </div>

                            {/* Judul + Instruktor */}
                            <div className="w-full lg:w-2/3 flex flex-col pt-2">
                                <div className="flex justify-between items-start flex-col sm:flex-row gap-4 mb-2">
                                    <h1 className="text-xl md:text-2xl lg:text-3xl font-extrabold text-gray-900 leading-tight">
                                        {course.title}
                                    </h1>
                                    <Link href={route("participant.course.index")} className="shrink-0 w-full sm:w-auto">
                                        <button className="flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:text-primary transition-colors shadow-sm">
                                            <box-icon name="left-arrow-alt" color="currentColor"></box-icon>
                                            Kembali
                                        </button>
                                    </Link>
                                </div>

                                <p className="text-sm md:text-base text-gray-600 mb-8 leading-relaxed">
                                    {course.short_description}
                                </p>

                                <div className="mt-auto flex flex-col sm:flex-row gap-4 w-full md:max-w-lg">
                                    <button
                                        className={`button-secondary w-full bg-white border-2 transition-all duration-300 text-sm md:text-base py-2.5 md:py-3 flex justify-center items-center h-full ${learn.url_certificate
                                                ? 'border-primary text-primary hover:bg-primary/5 shadow-sm'
                                                : 'border-gray-200 text-gray-400 cursor-not-allowed shadow-none'
                                            }`}
                                        disabled={!learn.url_certificate}
                                        onClick={(e) => {
                                            if (learn.url_certificate) {
                                                window.open(learn.url_certificate, '_blank');
                                            } else {
                                                e.preventDefault();
                                            }
                                        }}
                                        title={!learn.url_certificate ? "Sertifikat belum tersedia" : "Unduh Sertifikat"}
                                    >
                                        <div className="flex items-center justify-center gap-2">
                                            <box-icon name="download" color={learn.url_certificate ? "#018083" : "#9ca3af"} size="sm"></box-icon>
                                            Unduh Sertifikat
                                        </div>
                                    </button>

                                    <a
                                        target="_blank"
                                        className="w-full"
                                        href={route("course.show", { course: course.slug })}
                                    >
                                        <button className="button-secondary w-full bg-white border-2 border-primary text-primary hover:bg-primary/5 shadow-sm transition-all duration-300 text-sm md:text-base py-2.5 md:py-3 flex justify-center items-center h-full">
                                            Detail Kelas
                                        </button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* LEARN DATA */}
                <div className="bg-white shadow-sm border border-gray-100 rounded-3xl p-5 md:p-8">
                    <h2 className="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <box-icon name="bar-chart-alt-2" color="#018083"></box-icon>
                        Data Pembelajaran
                    </h2>
                    <div className="overflow-x-auto whitespace-nowrap">
                        <table className="w-full text-left text-sm text-gray-600 border border-gray-100 rounded-xl overflow-hidden">
                            <thead className="bg-gray-50 border-b border-gray-100 text-gray-700 font-semibold">
                                <tr>
                                    <th className="px-6 py-4 uppercase tracking-wider text-xs">Activation Date</th>
                                    <th className="px-6 py-4 uppercase tracking-wider text-xs">Finished At</th>
                                    <th className="px-6 py-4 uppercase tracking-wider text-xs">Post Test Avg</th>
                                    <th className="px-6 py-4 uppercase tracking-wider text-xs">Jam Pelajaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="hover:bg-blue-50/30 transition-colors">
                                    <td className="px-6 py-5 font-medium text-gray-800 border-b border-gray-50">{learn.activation_date || '-'}</td>
                                    <td className="px-6 py-5 border-b border-gray-50">
                                        {learn.finished_at ? (
                                            <span className="bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium text-xs">
                                                {learn.finished_at}
                                            </span>
                                        ) : (
                                            <span className="text-gray-400 italic">Belum Selesai</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-5 font-bold text-gray-800 border-b border-gray-50">{learn.post_test_avg || '0'}</td>
                                    <td className="px-6 py-5 border-b border-gray-50 whitespace-nowrap">
                                        <span className="font-semibold text-primary bg-primary/10 px-3 py-1 rounded-full text-xs">
                                            {learn.jam_pelajaran || '0'} JP
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </App>
    );
};

export default Show;
