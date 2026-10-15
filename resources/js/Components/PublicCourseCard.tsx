import { Link } from '@inertiajs/react';
import type { Course } from '@/types/domain';

export default function PublicCourseCard({ course, variant = 'default', fontFamily = 'mixed', showDescription = false }: { course: Course; variant?: 'default' | 'home'; fontFamily?: 'mixed' | 'poppins'; showDescription?: boolean }) {
    const category = typeof course.category === 'string' ? course.category : course.category?.name;
    const rating = Number(course.star || 0).toFixed(1);
    const totalParticipant = Number(course.total_participant || 0).toLocaleString('id-ID');
    const totalPassed = Number(course.total_passed || 0).toLocaleString('id-ID');
    const isHome = variant === 'home';
    const bodyFont = fontFamily === 'poppins' ? 'font-poppins' : 'font-poly';
    const metaFont = fontFamily === 'poppins' ? 'font-poppins' : 'font-inter';
    const employeeStatuses = [...new Set((course.employee_status || '').split(',').map((status) => status.trim()).filter(Boolean))];

    if (isHome) {
        return (
            <article className="group h-full overflow-hidden rounded-[15px] bg-white p-2 shadow-[0_4px_24px_rgba(1,128,131,0.06)] transition-shadow duration-300 hover:shadow-[0_6px_28px_rgba(1,128,131,0.12)]">
                <Link href={`/course/${course.slug}`} className="flex h-full flex-col rounded-[10px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                    <div className="overflow-hidden rounded-[10px] bg-[linear-gradient(to_right,#E8EAEC,#F8F5F5)]">
                        <div className="relative aspect-[1.85/1] overflow-hidden bg-[#E6F3F3]">
                            <img src={course.thumbnail} alt={course.title} className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]" />
                            {category && <span className="absolute left-2 top-2 max-w-[calc(100%-1rem)] rounded-md bg-[#BEAF0D] px-4 py-1 font-poppins text-xs font-semibold leading-5 text-white">{category}</span>}
                        </div>
                        <div className="px-3 py-3">
                            <h3 className="line-clamp-2 font-poppins text-sm font-semibold leading-5 text-[#075D5E]">{course.title}</h3>
                            {showDescription && <p className={`mt-1 line-clamp-2 text-[11px] leading-[18px] text-[#526176] ${bodyFont}`}>
                                {course.short_description?.trim() || 'Pelatihan untuk mendukung pengembangan kompetensi ASN.'}
                            </p>}
                        </div>
                    </div>

                    {employeeStatuses.length > 0 && <div className="flex flex-wrap gap-1 px-3 pt-2.5">
                        {employeeStatuses.map((status) => <span key={status} className="rounded border border-primary/20 bg-transparent px-1.5 py-0.5 font-poppins text-[10px] font-medium leading-3 text-primary">{status}</span>)}
                    </div>}

                    <div className="mt-auto px-3 pb-2 pt-3">
                        <dl className={`grid grid-cols-3 divide-x divide-[#E8ECF1] border-y border-[#E8ECF1] py-3 text-center ${metaFont}`}>
                            <div className="flex min-w-0 flex-col gap-1 px-1">
                                <dt className="order-2 text-[10px] leading-4 text-[#66778C]">Peserta</dt>
                                <dd className="flex flex-wrap items-center justify-center gap-1 text-sm font-semibold leading-5 tabular-nums text-[#075D5E]">
                                    <i className="bx bx-group text-base font-normal text-primary" aria-hidden="true" />
                                    <span className="break-all">{totalParticipant}</span>
                                </dd>
                            </div>
                            <div className="flex min-w-0 flex-col gap-1 px-1">
                                <dt className="order-2 text-[10px] leading-4 text-[#66778C]">Lulus</dt>
                                <dd className="flex flex-wrap items-center justify-center gap-1 text-sm font-semibold leading-5 tabular-nums text-[#075D5E]">
                                    <i className="bx bx-check-circle text-base font-normal text-primary" aria-hidden="true" />
                                    <span className="break-all">{totalPassed}</span>
                                </dd>
                            </div>
                            <div className="flex min-w-0 flex-col gap-1 px-1">
                                <dt className="order-2 text-[10px] leading-4 text-[#66778C]">Penilaian</dt>
                                <dd className="flex items-center justify-center gap-1 text-sm font-semibold leading-5 tabular-nums text-[#075D5E]">
                                    <i className="bx bxs-star text-base font-normal text-[#F68104]" aria-hidden="true" />
                                    <span>{rating}</span>
                                </dd>
                            </div>
                        </dl>
                        <div className="mt-3 flex justify-center">
                            <span className="inline-flex min-h-9 items-center justify-center rounded-[10px] border border-[#39A9A1] px-5 py-1.5 font-poppins text-xs font-semibold text-primary transition-colors group-hover:bg-primary group-hover:text-white">Lihat kelas</span>
                        </div>
                    </div>
                </Link>
            </article>
        );
    }

    return (
        <article className={`group flex h-full flex-col overflow-hidden rounded-[15px] border bg-white shadow-[0_2px_16px_rgba(1,128,131,0.06)] transition duration-300 hover:border-primary/30 ${isHome ? 'border-[#E8EDF2] hover:shadow-[0_4px_16px_rgba(1,128,131,0.10)]' : 'border-[#e6efee] hover:-translate-y-1 hover:shadow-[0_18px_40px_-18px_rgba(1,128,131,0.30)]'}`}>
            <Link href={`/course/${course.slug}`} className="flex h-full flex-col focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/20">
                <div className={`relative overflow-hidden bg-primary-mist ${isHome ? 'mx-4 mt-4 aspect-[16/9] rounded-[10px]' : 'aspect-[2/1]'}`}>
                    <img src={course.thumbnail} alt={course.title} className="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    {category && <span className={`absolute left-3 top-3 bg-white/95 px-3 py-1 text-[11px] text-primary shadow-[0_2px_16px_rgba(1,128,131,0.10)] ${isHome ? 'max-w-[calc(100%-1.5rem)] rounded-md font-poppins font-semibold' : 'rounded-lg font-bold uppercase tracking-[0.05em]'}`}>{category}</span>}
                    {!isHome && course.employee_status && <span className="absolute right-3 top-3 max-w-[55%] truncate rounded-lg bg-secondary/95 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.05em] text-white shadow-[0_2px_16px_rgba(246,129,4,0.25)]">{course.employee_status}</span>}
                </div>

                <div className="flex flex-1 flex-col p-5">
                    <h3 className={`line-clamp-2 font-poppins text-base transition group-hover:text-primary ${isHome ? 'font-semibold leading-7 text-[#075D5E]' : 'font-bold leading-snug text-[#173b3a]'}`}>{course.title}</h3>
                    {showDescription ? (
                        <div className="mb-5 mt-2">
                            {employeeStatuses.length > 0 && (
                                <div className="flex flex-wrap gap-1.5">
                                    {employeeStatuses.map((status) => <span key={status} className="inline-block max-w-full rounded-md border border-primary/20 bg-transparent px-2 py-0.5 font-poppins text-[10px] font-medium leading-4 text-primary">{status}</span>)}
                                </div>
                            )}
                            <p className={`mt-2 line-clamp-2 text-xs leading-5 text-[#66778C] ${bodyFont}`}>
                                {course.short_description?.trim() || 'Pelatihan untuk mendukung pengembangan kompetensi ASN.'}
                            </p>
                        </div>
                    ) : <p className={`mb-5 mt-2 text-sm leading-6 ${isHome ? 'text-[#66778C]' : 'text-[#66716c]'} ${bodyFont}`}>{isHome ? (course.employee_status || 'Pelatihan pengembangan kompetensi ASN') : 'Pelatihan untuk pengembangan kompetensi ASN.'}</p>}

                    <div className={`mt-auto grid grid-cols-3 gap-2 border-t border-[#e6efee] pt-4 text-xs text-[#66716c] ${metaFont}`}>
                        <div className="flex items-center gap-2">
                            <span className="grid h-7 w-7 place-items-center rounded-[10px] bg-primary-mist" aria-hidden="true"><box-icon name="group" size="15px" color="#018083" /></span>
                            <span className="min-w-0"><strong className="block text-sm text-[#173b3a]">{totalParticipant}</strong>Peserta</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="grid h-7 w-7 place-items-center rounded-[10px] bg-[#eaf7ee]" aria-hidden="true"><box-icon name="check-circle" size="15px" color="#246a3c" /></span>
                            <span className="min-w-0"><strong className="block text-sm text-[#173b3a]">{totalPassed}</strong>Lulus</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="grid h-7 w-7 place-items-center rounded-[10px] bg-secondary-soft" aria-hidden="true"><box-icon type="solid" name="star" size="14px" color="#f68104" /></span>
                            <span><strong className="block text-sm text-[#173b3a]">{rating}</strong>Penilaian</span>
                        </div>
                    </div>
                </div>
            </Link>
        </article>
    );
}
