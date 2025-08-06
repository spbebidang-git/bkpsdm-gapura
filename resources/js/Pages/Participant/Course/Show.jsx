import App from "@/Layouts/Participant/App";
import { Link, router } from "@inertiajs/react";

const Show = ({ course, learn }) => {

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
            <div className="max-w-7xl mx-auto p-6">
                {/* Course */}
                <div className="flex justify-between">
                    <div className="grid grid-cols-3 gap-5 mb-5 w-full">
                        <div className="col-span-3 xl:col-span-1">
                            <div className="h-48 w-full rounded-lg overflow-hidden shadow relative">
                                <div className="absolute top-2.5 right-2.5 bg-secondary text-white text-[10px] font-bold text-sm px-2 rounded">
                                    {course.employee_status}
                                </div>
                                <img
                                    src={course.thumbnail}
                                    alt={course.title}
                                    className="w-full h-full object-fill"
                                />
                            </div>
                        </div>
                        <div className="col-span-3 xl:col-span-2">
                            <div className="text-xl font-bold text-gray-800 mb-0.5 capitalize">
                                {course.title}
                            </div>
                            <div className="text-sm italic mb-1 text-gray-700">
                                oleh{" "}
                                <span className="not-italic font-bold">
                                    {course.instructor}
                                </span>
                            </div>
                            <div className="mb-2.5 text-gray-700 text-sm">
                                {course.short_description}
                            </div>
                            <div className="flex flex-col xl:flex-row max-w-full w-full xl:max-w-sm gap-3">
                                <button className="button-primary" onClick={(e) =>
                                    handleSubmit(e, course.slug)
                                }>Unduh Sertifikat</button>
                                <a target="_blank" className="w-full" href={route('course.show', { course: course.slug })}>
                                    <button className="button-primary">
                                        Detail Kelas
                                    </button>
                                </a>
                            </div>
                        </div>

                    </div>
                    <div className="">
                        <Link href={route('participant.course.index')}>
                            <button className="button-secondary">Back</button>
                        </Link>
                    </div>
                </div>
                {/* Learn Data */}
                <div className="bg-white">
                    <table className="w-full text-center text-sm">
                        <thead>
                            <tr>
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
            </div>

        </App >
    );
};

export default Show;
