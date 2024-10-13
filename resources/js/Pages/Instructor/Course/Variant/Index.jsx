import App from "@/Layouts/Instructor/App"
import { Link, router } from "@inertiajs/react"

const Index = ({ slug, activityId, variants }) => {

    function handleSubmitDelete(e, variantId) {
        e.preventDefault()
        router.delete(`/instructor/course/${slug}/activity/variant/${activityId}/${variantId}`)
    }

    return (
        <App>
            <div className="text-center text-2xl font-bold text-gray-700 mb-5">Variasi Soal</div>
            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow mb-8">
                <div className="p-5 text-lg font-bold flex justify-between items-center">Activity
                    <Link href={`/instructor/course/${slug}/activity/variant/${activityId}/create`} className="px-4 py-2 bg-purple-500 text-white rounded-xl shadow text-sm">Add New Variant</Link>
                </div>
                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left rtl:text-right text-gray-500 ">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 ">
                            <tr className="sticky top-0 z-10">
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Judul
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Deskripsi
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Min Score
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr className="bg-white border-b" >
                                <td className="px-6 py-4 text-center">1</td>
                                <td className="px-6 py-4 text-center">1</td>
                                <td className="px-6 py-4 text-center">1</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div className="max-w-7xl mx-auto bg-gray-100 border overflow-hidden rounded-xl shadow">
                <div className="p-5 text-lg font-bold flex justify-between items-center">Variant List
                    <Link href={`/instructor/course/${slug}/activity/variant/${activityId}/create`} className="px-4 py-2 bg-purple-500 text-white rounded-xl shadow text-sm">Add New Variant</Link>
                </div>
                <div className="relative overflow-x-auto max-h-[512px] overflow-auto">
                    <table className="w-full text-sm text-left rtl:text-right text-gray-500 ">
                        <thead className="text-xs text-gray-700 uppercase bg-gray-50 ">
                            <tr className="sticky top-0 z-10">
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Variant
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Jumlah Soal
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Acak
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Ulangi
                                </th>
                                <th scope="col" className="px-6 py-3 text-center bg-gray-50 whitespace-nowrap">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {variants.length ? variants.map((item, index) => (
                                <tr className="bg-white border-b" key={index}>
                                    <td className="px-6 py-4 text-center">{item.variant}</td>
                                    <td className="px-6 py-4 text-center">{item.question_count}</td>
                                    <td className="px-6 py-4 text-center">{item.mix}</td>
                                    <td className="px-6 py-4 text-center">{item.loop}</td>
                                    <td>
                                        <div className="flex justify-center items-center gap-2">
                                            <Link href={`/instructor/course/${slug}/activity/variant/${activityId}/${item.id}/question-item`}>
                                                <button className="w-32 h-7 text-sm font-medium bg-primary text-white rounded-full shadow">Set Question</button>
                                            </Link>
                                            <Link href={`/instructor/course/${slug}/activity/variant/${activityId}/${item.id}/edit`}>
                                                <button className="button-warning">Edit</button>
                                            </Link>
                                            <button onClick={(e) => handleSubmitDelete(e, item.id)} className="button-danger">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            )) : (<tr className="bg-white border-b">
                                <td className="p-3 text-center" colspan="100%">Variant not available.</td>
                            </tr>)}
                        </tbody>
                    </table>
                </div>
            </div>

        </App>
    )
}

export default Index