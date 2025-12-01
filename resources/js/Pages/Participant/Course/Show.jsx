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

    const scrollToSection = (id) => {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    };


    return (
        <App>
            <div className="max-w-7xl mx-auto p-6">

                {/* HEADER COURSE */}
                <div className="flex justify-between items-start mb-6">
                    <div className="grid grid-cols-3 gap-5 w-full">

                        {/* Thumbnail */}
                        <div className="col-span-3 xl:col-span-1">
                            <div className="h-48 w-full rounded-lg overflow-hidden shadow relative">
                                <div className="absolute top-2 right-2 bg-secondary text-white text-[10px] font-bold px-2 rounded">
                                    {course.employee_status}
                                </div>
                                <img
                                    src={course.thumbnail}
                                    alt={course.title}
                                    className="w-full h-full object-fill"
                                />
                            </div>
                        </div>

                        {/* Judul + Instruktor */}
                        <div className="col-span-3 xl:col-span-2">
                            <h1 className="text-xl font-bold text-gray-800 mb-1 capitalize">
                                {course.title}
                            </h1>

                            <p className="text-sm italic text-gray-700 mb-2">
                                oleh{" "}
                                <span className="not-italic font-bold">{course.instructor}</span>
                            </p>

                            <p className="text-sm text-gray-700 mb-3">
                                {course.short_description}
                            </p>

                            <div className="flex flex-col xl:flex-row w-full xl:max-w-sm gap-3">
                                <button
                                    className="button-primary"
                                    onClick={(e) => handleSubmit(e, course.slug)}
                                >
                                    Unduh Sertifikat
                                </button>

                                <a
                                    target="_blank"
                                    className="w-full"
                                    href={route("course.show", { course: course.slug })}
                                >
                                    <button className="button-primary w-full">
                                        Detail Kelas
                                    </button>
                                </a>
                            </div>
                        </div>

                    </div>

                    {/* Back */}
                    <Link href={route("participant.course.index")}>
                        <button className="button-secondary">Back</button>
                    </Link>
                </div>

                {/* NAVIGATION SECTION LINKS */}
                <div className="flex gap-6 bg-white p-4 shadow mb-6 rounded-lg text-sm">
                    <button onClick={() => scrollToSection("schedule")} className="hover:text-blue-600">
                        Schedule
                    </button>
                    <button onClick={() => scrollToSection("teaser")} className="hover:text-blue-600">
                        Teaser Video
                    </button>
                    <button onClick={() => scrollToSection("description")} className="hover:text-blue-600">
                        Description
                    </button>
                    <button onClick={() => scrollToSection("review")} className="hover:text-blue-600">
                        Review
                    </button>
                </div>

                {/* LEARN DATA */}
                <div className="bg-white shadow rounded-lg p-4">
                    <table className="w-full text-center text-sm">
                        <thead>
                            <tr className="bg-gray-50">
                                <th className="border p-3">Activation Date</th>
                                <th className="border p-3">Finished At</th>
                                <th className="border p-3">Post Test Avg</th>
                                <th className="border p-3">Jam Pelajaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td className="border p-3">{learn.activation_date}</td>
                                <td className="border p-3">{learn.finished_at}</td>
                                <td className="border p-3">{learn.post_test_avg}</td>
                                <td className="border p-3">{learn.jam_pelajaran}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {/* SECTION TARGETS */}
                <div className="mt-10">

                    <div id="schedule" className="mb-12">
                        <h2 className="text-lg font-bold mb-2">Schedule</h2>
                        <p className="text-sm text-gray-600">Isi schedule di sini…</p>
                    </div>

                    <div id="teaser" className="mb-12">
                        <h2 className="text-lg font-bold mb-2">Teaser Video</h2>
                        <p className="text-sm text-gray-600">Video teaser muncul di sini…</p>
                    </div>

                    <div id="description" className="mb-12">
                        <h2 className="text-lg font-bold mb-2">Description</h2>
                        <p className="text-sm text-gray-600">{course.description}</p>
                    </div>

                    <div id="review" className="mb-12">
                        <h2 className="text-lg font-bold mb-2">Review</h2>
                        <p className="text-sm text-gray-600">Review peserta muncul di sini…</p>
                    </div>

                </div>

            </div>
        </App>
    );
};

export default Show;
