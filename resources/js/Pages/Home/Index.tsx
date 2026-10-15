import { Head } from '@inertiajs/react';
import Button from '@/Components/Button';
import PublicCourseCard from '@/Components/PublicCourseCard';
import App from '@/Layouts/App';
import type { Course } from '@/types/domain';

const highlights = [
    { title: 'Pemateri berpengalaman', description: 'Belajar dari praktisi dan pemateri yang kompeten.', image: '/assets/media/images/static/pemateri.png' },
    { title: 'Materi berkualitas', description: 'Materi relevan untuk mendukung kebutuhan kerja ASN.', image: '/assets/media/images/static/materi-berkualitas.png' },
    { title: 'Belajar fleksibel', description: 'Akses materi kapan saja\ndan dari mana saja.', image: '/assets/media/images/static/fleksibel.png' },
];

export default function Home({ courses = [], bannerUrl = '/assets/media/images/static/banner.png' }: { courses?: Course[]; bannerUrl?: string }) {
    return (
        <App>
            <Head title="Beranda" />

            <section className="bg-gray-100">
                <div className="mx-auto max-w-[1320px] overflow-hidden">
                    <img src={bannerUrl} alt="GAPURA E-Learning — ASN Unggul, Indonesia Maju" className="h-auto w-full object-cover" />
                </div>
            </section>

            <section className="relative z-10 -mt-12 sm:-mt-16">
                <div className="mx-auto w-full max-w-[1320px] px-6 sm:px-10 lg:px-14">
                    <div className="grid overflow-hidden rounded-[15px] border border-[#E8EDF2] bg-white shadow-[0_10px_30px_-20px_rgba(7,93,94,0.35)] md:grid-cols-3">
                        {highlights.map((highlight, index) => (
                            <div key={highlight.title} className={`flex min-h-28 items-center gap-5 px-5 py-5 sm:px-7 ${index < highlights.length - 1 ? 'border-b border-[#E8EDF2] md:border-b-0 md:border-r' : ''}`}>
                                <span className="grid h-16 w-16 shrink-0 place-items-center rounded-xl bg-[#F0FAF8]">
                                    <img src={highlight.image} alt="" className="h-12 w-12 object-contain" />
                                </span>
                                <div className="min-w-0 font-poppins">
                                    <h2 className="text-sm font-semibold leading-5 text-[#075D5E]">{highlight.title}</h2>
                                    <p className="mt-1 whitespace-pre-line text-xs leading-5 text-[#66778C]">{highlight.description}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section aria-labelledby="home-courses-title" className="relative -mt-12 bg-gray-100 sm:-mt-16">
                <div className="mx-auto w-full max-w-[1320px] bg-[#F6FAF9] px-6 pb-16 pt-24 font-poppins sm:px-10 sm:pb-20 sm:pt-28 lg:px-14 lg:pb-24">
                    <div className="mx-auto max-w-2xl text-center">
                        <p className="text-sm font-medium italic text-[#019B91]">Pilihan untuk Anda</p>
                        <h1 id="home-courses-title" className="mt-2 text-3xl font-semibold leading-tight text-[#075D5E] sm:text-4xl">Kelas terbaru GAPURA</h1>
                        <p className="mt-3 text-sm leading-6 text-[#526176] sm:text-base sm:leading-7">
                            Tingkatkan kompetensi melalui pelatihan yang relevan dengan kebutuhan dan peran Anda.
                        </p>
                    </div>

                    {courses.length > 0 ? (
                        <div className="mt-9 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                            {courses.map((course) => <PublicCourseCard key={course.id} course={course} variant="home" fontFamily="poppins" showDescription />)}
                        </div>
                    ) : (
                        <div className="mt-8 rounded-[15px] border border-dashed border-[#BDE8E2] bg-white px-6 py-14 text-center">
                            <box-icon name="book-open" color="#018083" size="36px" />
                            <h2 className="mt-3 font-poppins text-xl font-semibold text-[#075D5E]">Kelas belum tersedia</h2>
                            <p className="mt-2 text-sm text-[#66778C]">Silakan kembali lagi untuk melihat pelatihan yang dapat diikuti.</p>
                        </div>
                    )}

                    <div className="mt-9 text-center">
                        <Button href="/course" variant="secondary" size="md" radius="lg" className="group min-h-11 w-full !rounded-[10px] !border-[#39A9A1] !bg-transparent px-6 !text-primary hover:!border-primary hover:!bg-primary hover:!text-white sm:w-auto">
                            Lihat semua kelas
                            <i className="bx bx-right-arrow-alt text-xl" aria-hidden="true" />
                        </Button>
                    </div>
                </div>
            </section>
        </App>
    );
}
