import CardActivity from "@/Components/CardActivity";
import NumberActivity from "@/Components/NumberActivity";
import TitleActivity from "@/Components/TitleActivity";
import App from "@/Layouts/Instructor/App";
import { Link } from "@inertiajs/react";
import { useState } from "react";

const Activity = ({ slug }) => {
    const [activeSection, setActiveSection] = useState(null);

    return (
        <App>
            <NumberActivity number={activeSection ? 2 : 1} />
            <TitleActivity number={activeSection ? 2 : 1} title={activeSection ? 'Activity Question' : 'All Activity'} />

            {!activeSection && (
                <div className="max-w-6xl mx-auto grid grid-cols-4 gap-5">
                    <CardActivity title="Pertanyaan" description={'Membuat dan mengelola pertanyaan - pertanyaan serta jawaban yang digunakan oleh pemateri untuk menilai tingkat pemahaman peserta. Model soal yang diberikan berupa pilihan ganda yang dapat dimanfaatkan untuk Pre Test, Quiz dan Post Test.'} onClick={() => setActiveSection('question')} />
                    <Link href={`/instructor/course/${slug}/activity/task/create`}>
                        <CardActivity title="Tugas" description={'Memasukkan deskripsi penugasan dan standart hasil yang diharapkan dari penugasan. Tugas yang dikumpulkan oleh peserta akan langsung dikirimkan ke email masing – masing pemateri sesuai dengan email yang telah didaftarkan saat Sign Up.'} />
                    </Link>
                    <CardActivity title="Interaktif" description={'Membuat dan mengelola kegiatan pertemuan interaktif yang akan diselenggarakan secara online. Pembicara dapat memilih jenis pertemuan interaktif secara Daring atau Presentasi Video.'} onClick={() => setActiveSection('interactive')} />
                    <Link href={`/instructor/course/${slug}/activity/literature/create`}>
                        <CardActivity title="Literatur" description={'Digunakan untuk memasukkan Judul Literatur, Sub Judul Literatur dan file pdf literature yang nanti diakses oleh peserta.'} />
                    </Link>
                </div>
            )}
            {/* Section Question */}
            {activeSection === 'question' && (
                <div className="max-w-xl mx-auto grid grid-cols-2 gap-5">
                    <Link href={`/instructor/course/${slug}/activity/pre-test/create`}>
                        <CardActivity
                            title="Pre Test/Quiz"
                            description={'Membuat dan mengelola pertanyaan – pertanyaan serta jawaban Pre Test dan Quiz. Soal – soal yang dimasukkan hanya untuk menguji pemahaman peserta dan tidak menjadi syarat penerbitan Sertifikat Digital.'}
                        /></Link>
                    <Link href={`/instructor/course/${slug}/activity/post-test/create`}>
                        <CardActivity
                            title="Post Test"
                            description={'Membuat dan mengelola pertanyaan – pertanyaan serta jawaban Post Test (Test Akhir). Soal – soal yang dimasukkan wajib menyertakan skor dan minimum skor karena akan menjadi syarat penerbitan Sertifikat Digital.'}
                        />
                    </Link>
                </div>
            )}
            {/* Section Interactive */}
            {activeSection === 'interactive' && (
                <div className="max-w-xl mx-auto grid grid-cols-2 gap-5">
                    <Link href={`/instructor/course/${slug}/activity/meeting/create`}>
                        <CardActivity
                            title="Pertemuan Daring"
                            description={'Digunakan untuk memasukkan detail deskripsi pertemuan Daring berikut Url : yang nanti digunakan oleh peserta untuk bergabung pada pertemuan daring.'}
                        />
                    </Link>
                    <Link href={`/instructor/course/${slug}/activity/presentation-video/create`}>
                        <CardActivity
                            title="Presentasi Video"
                            description={'Digunakan untuk memasukkan detail deskripsi Presentasi Video dan file Video yang nanti diakses oleh peserta.'}
                        />
                    </Link>
                </div>
            )}

            {activeSection ? (
                <div className="max-w-44 mx-auto text-center mt-8">
                    <button
                        className="button-primary"
                        onClick={() => setActiveSection(null)}
                    >
                        Back
                    </button>
                </div>
            ) : (<div className="max-w-44 mx-auto text-center mt-8">
                <Link
                    href={`/instructor/course/${slug}`}
                >
                    <button className="button-primary">
                        Back
                    </button>
                </Link>
            </div>)}
        </App>
    )
}

export default Activity