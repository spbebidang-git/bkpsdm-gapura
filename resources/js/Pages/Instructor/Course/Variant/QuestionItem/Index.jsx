import App from "@/Layouts/Instructor/App"
import { Link } from "@inertiajs/react"

const Index = ({ slug, activityId, questionId, questionItems }) => {
    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">Question Sets</div>

            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow">
                <div className="p-5 text-lg font-bold flex justify-between items-center">List Pertanyaan
                    <Link href={`/instructor/course/${slug}/activity/variant/${activityId}/${questionId}/question-item/create`} className="px-4 py-2 bg-purple-500 text-white rounded-xl shadow text-sm">Tambah Pertanyaan</Link>
                </div>
                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left rtl:text-right text-gray-500 ">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 ">
                            <tr className="sticky top-0 z-10">
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    #
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Pertanyaan
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Option A
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Option B
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Option C
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Option D
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Skor
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {questionItems.length ? questionItems.map((item, index) => (
                                <tr className="bg-white border-b" key={index}>
                                    <td className="px-6 py-4 text-center">{index + 1}</td>
                                    <td className="px-6 py-4 text-center">{item.question}</td>
                                    <td className={`px-6 py-4 text-center ${item.is_correct === item.option1 ? 'text-green-600 font-bold' : ''}`}>{item.option1}</td>
                                    <td className={`px-6 py-4 text-center ${item.is_correct === item.option2 ? 'text-green-600 font-bold' : ''}`}>{item.option2}</td>
                                    <td className={`px-6 py-4 text-center ${item.is_correct === item.option3 ? 'text-green-600 font-bold' : ''}`}>{item.option3}</td>
                                    <td className={`px-6 py-4 text-center ${item.is_correct === item.option4 ? 'text-green-600 font-bold' : ''}`}>{item.option4}</td>
                                    <td className="px-6 py-4 text-center">{item.score}</td>
                                    <td>
                                        <div className="flex gap-2 justify-center">
                                            <Link href={`/instructor/course/${slug}/activity/variant/${activityId}/${item.id}/edit`}>
                                                <button className="button-warning">Edit</button>
                                            </Link>
                                            <button onClick={(e) => handleSubmitDelete(e, item.id)} className="button-danger">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            )) : (<tr className="bg-white border-b">
                                <td className="p-3 text-center" colspan="100%">Question not available.</td>
                            </tr>)}
                        </tbody>
                    </table>
                </div>
            </div>

        </App>
    )
}

export default Index