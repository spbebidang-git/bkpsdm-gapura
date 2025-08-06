import App from "@/Layouts/Instructor/App";

const Index = ({ courses }) => {
    return (
        <App>

            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow">
                <div className="p-5 text-lg font-bold flex justify-between items-center">
                    Results
                </div>
                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left rtl:text-right text-gray-500 ">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 ">
                            <tr className="sticky top-0 z-10">
                                <th
                                    scope="col"
                                    className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap"
                                >
                                    Course
                                </th>
                                <th
                                    scope="col"
                                    className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {courses.length ? (
                                courses.map((item, index) => (
                                    <tr
                                        className="bg-white border-b"
                                        key={index}
                                    >
                                        <td className="px-6 py-4 text-center">
                                            {item.title}
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <div className="">
                                                <div className="">
                                                    <button className="px-5 h-7 text-sm font-medium bg-green-500 text-white rounded-full shadow">
                                                        Detail
                                                    </button>
                                                    <button className="px-5 h-7 text-sm font-medium bg-green-500 text-white rounded-full shadow">
                                                        Post Test
                                                    </button>
                                                </div>
                                            </div>
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
