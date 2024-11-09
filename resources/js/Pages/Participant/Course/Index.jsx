import App from "@/Layouts/Participant/App"
import { Link } from "@inertiajs/react"

const Index = ({ courses }) => {
    return (
        <App>
            <div className="max-w-5xl mx-auto">
                <div className="border bg-white rounded-xl shadow p-5 mb-6 text-xl font-bold text-center">
                    Kelas Saya
                </div>
                <div className="grid grid-cols-3 gap-5">
                    {courses.map((item, index) => {
                        return (<div className="rounded-xl overflow-hidden shadow ">
                            <div className='bg-red-200 h-36'>
                                <img src={item.thumbnail} className="w-full h-full object-fill" alt={item.title} />
                            </div>
                            <div className="p-5 bg-white">
                                <div className='mb-2.5'>
                                    <div className='text-lg text-gray-700 font-semibold'>{item.title}</div>
                                    <div className='text-gray-700'>{item.short_description}</div>
                                </div>
                                <div className='flex gap-2'>
                                    <Link className="w-full" href={`/course/${item.slug}`}>
                                        <button className="button-primary">Detail</button>
                                    </Link>
                                    {item.finished_at == null ? (<a className="w-full" href={`/learn/${item.slug}/${item.schedule_id}/${item.initial_activity}`}>
                                        <button className="button-primary">Ikuti Kelas</button>
                                    </a>) : ''}
                                </div>
                            </div>
                        </div>)
                    })}
                </div>
            </div>
        </App>
    )
}

export default Index