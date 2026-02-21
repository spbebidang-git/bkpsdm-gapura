import BreadcrumbLearn from "@/Components/BreadcrumbLearn";
import App from "@/Layouts/Participant/App";
import { Link, router } from "@inertiajs/react";

const Index = ({ courses }) => {
    const handleSubmit = (e, slug) => {
        e.preventDefault();
        router.post(
            route("participant.certificate"),
            {
                // data yang dikirim
                course: slug,
            },
            {
                onSuccess: (page) => {
                    // console.log(page.props.redirect_url);
                    const sertifikatUrl = page.props.redirect_url;
                    if (sertifikatUrl) {
                        window.open(sertifikatUrl, "_blank");
                    }
                },
            }
        );
    };

    return (
        <App>
            <div className="max-w-5xl mx-auto">
                <div className="flex justify-center mb-10 w-full relative z-10">
                    <div className="inline-flex flex-wrap items-center gap-2 md:gap-4 bg-white/90 backdrop-blur-md py-3 px-8 rounded-full shadow-[0_4px_25px_-4px_rgba(0,0,0,0.06)] border border-gray-100/80 transition-all hover:shadow-[0_4px_30px_-4px_rgba(0,0,0,0.1)]">
                        <Link href="/dashboard" className="transition-transform hover:-translate-y-0.5 opacity-80 hover:opacity-100">
                            <BreadcrumbLearn title="Dashboard" activeTab={false} icon="grid-alt" size="lg" />
                        </Link>
                        <box-icon name="chevron-right" color="#cbd5e1" size="22px"></box-icon>
                        <BreadcrumbLearn title="Kelas Saya" activeTab={true} icon="book-bookmark" size="lg" />
                    </div>
                </div>
                <div className="grid grid-cols-3 gap-6">
                    {courses.map((item, index) => {
                        return (
                            <div key={index} className="rounded-2xl col-span-3 xl:col-span-1 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 bg-white group flex flex-col">
                                <div className="relative h-48 overflow-hidden bg-gray-100">
                                    <img
                                        src={item.thumbnail}
                                        className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                        alt={item.title}
                                    />
                                </div>
                                <div className="p-6 flex flex-col flex-1">
                                    <div className="mb-4 flex-1">
                                        <h3 className="text-lg text-gray-800 font-bold group-hover:text-primary transition-colors line-clamp-2 mb-2 leading-tight">
                                            {item.title}
                                        </h3>
                                        <p className="text-gray-500 text-sm line-clamp-2 leading-relaxed">
                                            {item.short_description}
                                        </p>
                                    </div>
                                    <div className="flex gap-3 mt-auto pt-4 border-t border-gray-50">
                                        <Link
                                            className="w-full transition-transform active:scale-95"
                                            href={route('participant.course.show', { course: item.slug })}
                                        >
                                            <button className="button-primary w-full shadow-sm hover:shadow-md">
                                                Detail
                                            </button>
                                        </Link>
                                        {item.finished_at == null && (
                                            <a
                                                className="w-full transition-transform active:scale-95"
                                                href={`/learn/${item.slug}/${item.schedule_id}/${item.initial_activity}/instruction`}
                                            >
                                                <button className="button-primary w-full shadow-sm hover:shadow-md">
                                                    Ikuti Kelas
                                                </button>
                                            </a>
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
