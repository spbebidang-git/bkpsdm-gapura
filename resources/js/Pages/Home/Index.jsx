import { Link } from '@inertiajs/react';
import App from '../../Layouts/App';

export default function Home({ courses }) {
    return (
        <App>
            {/* Slide */}
            <div className="w-full">
                <img
                    src="/assets/media/images/static/banner.jpg"
                    alt="#"
                    className="w-full h-auto object-cover"
                />
            </div>

            {/* About Course */}
            <div className="max-w-6xl mx-auto p-5">
                <div className="grid grid-cols-3 gap-0 xl:gap-5">

                    {/* Item 1 */}
                    <div className="p-3 col-span-3 lg:col-span-1">
                        <div className="h-36 xl:h-52">
                            <img
                                className="object-contain w-full h-full"
                                src="/assets/media/images/static/pemateri.png"
                                alt=""
                            />
                        </div>
                        <div className="px-2 py-1">
                            <div className="text-xl font-bold text-center text-gray-700 py-1">
                                Pemateri Berpengalaman
                            </div>
                            <div className="text-center text-gray-600">
                                <p>Materi dibawakan oleh pemateri yang berpengalaman</p>
                            </div>
                        </div>
                    </div>

                    {/* Item 2 */}
                    <div className="p-3 col-span-3 lg:col-span-1">
                        <div className="h-36 xl:h-52">
                            <img
                                className="object-contain w-full h-full"
                                src="/assets/media/images/static/materi-berkualitas.png"
                                alt=""
                            />
                        </div>
                        <div className="px-2 py-1">
                            <div className="text-xl font-bold text-center text-gray-700 py-1">
                                Materi Berkualitas
                            </div>
                            <div className="text-center text-gray-600">
                                <p>Materi yang diberikan relevan dengan kebutuhan dunia kerja</p>
                            </div>
                        </div>
                    </div>

                    {/* Item 3 */}
                    <div className="p-3 col-span-3 lg:col-span-1">
                        <div className="h-36 xl:h-52">
                            <img
                                className="object-contain w-full h-full"
                                src="/assets/media/images/static/fleksibel.png"
                                alt=""
                            />
                        </div>
                        <div className="px-2 py-1">
                            <div className="text-xl font-bold text-center text-gray-700 py-1">
                                Fleksibel
                            </div>
                            <div className="text-center text-gray-600">
                                <p>Pembelajaran dapat dilakukan dimana dan kapan saja</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {/* List of Courses */}
            <div className="bg-gray-100 py-20 px-4 md:px-8 border-t border-gray-200">
                <div className="max-w-7xl mx-auto">
                    <div className="text-center mb-12">
                        <h2 className="text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight mb-4">
                            Materi <span className="text-primary">Pilihan</span>
                        </h2>
                        <p className="text-gray-500 max-w-2xl mx-auto text-lg">
                            Tingkatkan kompetensi Anda dengan berbagai kelas unggulan kami yang dirancang oleh para ahli.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        {courses.length > 0 ? (
                            courses.map((item, index) => (
                                <div
                                    key={index}
                                    className="bg-white rounded-3xl overflow-hidden shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-100/50 transition-all duration-300 hover:-translate-y-2 group flex flex-col"
                                >
                                    <Link className="flex flex-col h-full" href={`/course/${item.slug}`}>
                                        <div className="w-full relative aspect-[4/3] overflow-hidden bg-gray-200">
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
                            ))
                        ) : (
                            <div className="col-span-1 md:col-span-2 lg:col-span-4 flex flex-col items-center justify-center py-24 bg-white rounded-3xl shadow-sm border border-gray-100 text-center">
                                <div className="w-24 h-24 mb-6 text-gray-200">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full h-full">
                                        <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </div>
                                <h3 className="text-2xl font-bold text-gray-800 mb-3">Materi Belum Tersedia</h3>
                                <p className="text-gray-500 max-w-sm text-base leading-relaxed">
                                    Materi pelatihan unggulan masih dalam tahap persiapan. Silakan periksa kembali beberapa saat lagi.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </App>
    );
}
