import App from "@/Layouts/Instructor/App";
import { Link } from "@inertiajs/react";

const Index = ({ slug, activity, tasks }) => {
    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">
                Result Task Activity
            </div>
            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow mb-8">
                <div className="p-5 text-lg font-bold flex justify-between items-center">
                    Result Task Activity
                    <Link href={route('instructor.course.show', { course: slug })} className="">
                        <button className="button-secondary">Back</button>
                    </Link>
                </div>
                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left rtl:text-right text-gray-500 ">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 ">
                            <tr className="sticky top-0 z-10">
                                <th
                                    scope="col"
                                    className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap"
                                >
                                    Name
                                </th>
                                <th
                                    scope="col"
                                    className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap"
                                >
                                    Nip
                                </th>
                                <th
                                    scope="col"
                                    className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap"
                                >
                                    File
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {tasks.length ? (
                                tasks.map((item, index) => (
                                    <tr
                                        className="bg-white border-b"
                                        key={index}
                                    >
                                        <td className="px-6 py-4 text-center">
                                            {item.name}
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            {item.nip}
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <a
                                                href={item.file}
                                                download
                                                className="text-blue-600 hover:underline"
                                            >
                                                Download
                                            </a>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr className="bg-white border-b">
                                    <td
                                        className="p-3 text-center"
                                        colspan="100%"
                                    >
                                        Result not available.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </App>
    );
};

export default Index;
