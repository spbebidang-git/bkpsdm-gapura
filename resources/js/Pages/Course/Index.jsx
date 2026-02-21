import App from "@/Layouts/App"
import { Link } from "@inertiajs/react"

const Index = ({ courses }) => {
    return (
        <App>
            <div className='bg-gray-50 min-h-screen py-12 px-6 lg:px-8'>
                <div className="max-w-7xl mx-auto w-full">
                    <div className="text-center max-w-2xl mx-auto mb-12">
                        <h1 className='font-extrabold text-3xl md:text-4xl text-gray-900 tracking-tight mb-4'>Materi Pilihan</h1>
                        <p className="text-lg text-gray-500">Tingkatkan kompetensi Anda dengan berbagai kelas unggulan kami yang dirancang oleh para ahli.</p>
                    </div>
                    <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8'>
                        {courses.length > 0 ? (
                            courses.map((item, index) => {
                                return (
                                    <div
                                        key={index}
                                        className="bg-white rounded-3xl overflow-hidden shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-100/50 transition-all duration-300 hover:-translate-y-2 group flex flex-col"
                                    >
                                        <Link className="flex flex-col h-full" href={'course/' + item.slug}>
                                            <div className="w-full relative aspect-[4/3] overflow-hidden bg-gray-100 shrink-0">
                                                {/* Label di pojok kanan atas */}
                                                <div className="absolute top-3 right-3 bg-secondary/90 backdrop-blur-md shadow-sm text-white text-xs font-bold px-3 py-1.5 rounded-full z-10 uppercase tracking-wider">
                                                    {item.employee_status}
                                                </div>
                                                <img
                                                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-in-out"
                                                    src={item.thumbnail}
                                                    alt={item.title}
                                                />
                                                <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                            </div>

                                            <div className="p-6 flex flex-col flex-1">
                                                <h3 className="font-bold text-gray-900 text-lg leading-tight mb-2 group-hover:text-primary transition-colors line-clamp-2">
                                                    {item.title}
                                                </h3>

                                                <div className="text-sm text-gray-500 mb-5 flex items-center gap-1.5">
                                                    <span className="italic">Oleh</span>
                                                    <span className="font-semibold text-gray-700">
                                                        {item.instructor}
                                                    </span>
                                                </div>

                                                <div className="mt-auto space-y-3 pt-4 border-t border-gray-100">
                                                    <div className="flex justify-between items-center text-sm font-medium text-gray-600">
                                                        <div className="flex items-center gap-2">
                                                            <div className="w-7 h-7 border rounded-full overflow-hidden">
                                                                <img className="w-full h-full object-cover" src="/assets/media/images/static/peserta-icon.png" alt="" />
                                                            </div>
                                                            <span><b>{item.total_participant}</b> Peserta</span>
                                                        </div>
                                                    </div>

                                                    <div className="flex justify-between items-center text-sm font-medium text-gray-600">
                                                        <div className="flex items-center gap-2">
                                                            <div className="w-7 h-7 border rounded-full overflow-hidden">
                                                                <img className="w-full h-full object-cover" src="/assets/media/images/static/lulus-icon.png" alt="" />
                                                            </div>
                                                            <span><b>{item.total_passed}</b> Lulus</span>
                                                        </div>

                                                        <div className="flex items-center bg-amber-50 px-2 py-1 rounded-lg">
                                                            <span className="text-amber-500 font-bold mr-1 text-xs">{item.star}</span>
                                                            <box-icon type='solid' name='star' color="#f59e0b" size="12px"></box-icon>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </Link>
                                    </div>
                                )
                            })
                        ) : (
                            <div className="col-span-1 md:col-span-2 lg:col-span-3 flex flex-col items-center justify-center py-24 text-center bg-white rounded-3xl border border-gray-100 shadow-sm">
                                <div className="w-24 h-24 mb-6 text-gray-300">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full h-full">
                                        <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" strokeWidth="1.5" />
                                        <path d="M9 9h6M9 12h6M9 15h4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                                        <circle cx="18" cy="18" r="5" fill="white" stroke="currentColor" strokeWidth="1.5" />
                                        <path d="M15.5 18h5M18 15.5v5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                                    </svg>
                                </div>
                                <h3 className="text-2xl font-bold text-gray-700 mb-3">Belum Ada Materi Pilihan</h3>
                                <p className="text-gray-500 max-w-sm text-base leading-relaxed">
                                    Saat ini belum ada materi yang tersedia. Silakan kembali lagi nanti atau hubungi instruktur Anda.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </App>
    )
}

export default Index