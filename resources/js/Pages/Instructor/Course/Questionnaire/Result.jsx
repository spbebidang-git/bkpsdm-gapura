import App from "@/Layouts/Instructor/App";
import { Link } from "@inertiajs/react";

/**
 * Rekap kuesioner: ringkasan per pertanyaan (jumlah & persen tiap pilihan,
 * rata-rata untuk skala/angka) plus daftar jawaban isian bebas.
 */
const Result = ({ slug, activity, recap }) => {
    const hasResponse = recap.total_response > 0;

    return (
        <App>
            <div className="max-w-5xl mx-auto mb-10">
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-extrabold tracking-tight text-gray-800">
                            Rekap Kuesioner
                        </h1>
                        <p className="text-sm text-gray-500">{activity.title}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {hasResponse && (
                            <a
                                href={route("instructor.course.activity.questionnaire.export", {
                                    course: slug,
                                    activityId: activity.id,
                                })}
                                className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                <box-icon name="download" color="white" size="18px"></box-icon>
                                Unduh CSV
                            </a>
                        )}
                        <Link href={route("instructor.course.show", { course: slug })}>
                            <button className="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Kembali
                            </button>
                        </Link>
                    </div>
                </div>

                <div className="mb-6 grid gap-3 sm:grid-cols-3">
                    <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div className="text-xs font-bold uppercase tracking-wider text-gray-400">
                            Total Responden
                        </div>
                        <div className="text-3xl font-extrabold text-gray-800">
                            {recap.total_response}
                        </div>
                    </div>
                    <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div className="text-xs font-bold uppercase tracking-wider text-gray-400">
                            Jumlah Pertanyaan
                        </div>
                        <div className="text-3xl font-extrabold text-gray-800">
                            {recap.questions.filter((q) => q.type !== "section").length}
                        </div>
                    </div>
                    <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div className="text-xs font-bold uppercase tracking-wider text-gray-400">
                            Identitas
                        </div>
                        <div className="mt-1 text-lg font-bold text-gray-800">
                            {recap.is_anonymous ? "Anonim" : "Tercatat"}
                        </div>
                    </div>
                </div>

                {!hasResponse && (
                    <div className="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center text-gray-500">
                        Belum ada peserta yang mengisi kuesioner ini.
                    </div>
                )}

                {hasResponse && (
                    <div className="grid gap-4">
                        {recap.questions.map((question, index) => {
                            if (question.type === "section") {
                                return (
                                    <div
                                        key={question.id}
                                        className="border-l-4 border-primary bg-primary/5 px-4 py-3 text-sm font-semibold text-gray-700"
                                        dangerouslySetInnerHTML={{ __html: question.content }}
                                    />
                                );
                            }

                            return (
                                <div
                                    key={question.id}
                                    className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"
                                >
                                    <div className="mb-3 flex flex-wrap items-start justify-between gap-2">
                                        <div
                                            className="flex-1 font-semibold text-gray-800"
                                            dangerouslySetInnerHTML={{ __html: question.content }}
                                        />
                                        <div className="flex shrink-0 gap-2">
                                            <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                                {question.type_label}
                                            </span>
                                            <span className="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                                {question.answered}/{question.total} menjawab
                                            </span>
                                        </div>
                                    </div>

                                    {question.summary.length > 0 && (
                                        <div className="grid gap-2">
                                            {question.summary.map((row, rowIndex) => (
                                                <div key={rowIndex}>
                                                    <div className="mb-1 flex items-baseline justify-between gap-3 text-sm">
                                                        <span className="text-gray-700">{row.label}</span>
                                                        <span className="shrink-0 font-semibold text-gray-600">
                                                            {row.average !== undefined && row.average !== null
                                                                ? `rata-rata ${row.average} (${row.total} jawaban)`
                                                                : `${row.total}${row.percent !== null ? ` · ${row.percent}%` : ""}`}
                                                        </span>
                                                    </div>
                                                    {row.percent !== null && row.percent !== undefined && (
                                                        <div className="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                                            <div
                                                                className="h-full rounded-full bg-primary"
                                                                style={{ width: `${Math.min(row.percent, 100)}%` }}
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {question.texts.length > 0 && (
                                        <div className="mt-2 max-h-72 space-y-2 overflow-y-auto pr-1">
                                            {question.texts.map((text, textIndex) => (
                                                <div
                                                    key={textIndex}
                                                    className="rounded-xl bg-gray-50 px-3 py-2 text-sm text-gray-700 whitespace-pre-line"
                                                >
                                                    {text}
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {question.summary.length === 0 && question.texts.length === 0 && (
                                        <p className="text-sm italic text-gray-400">
                                            Belum ada jawaban untuk pertanyaan ini.
                                        </p>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}

                {hasResponse && !recap.is_anonymous && (
                    <div className="mt-8 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div className="border-b border-gray-100 px-5 py-4 font-bold text-gray-700">
                            Daftar Responden
                        </div>
                        <div className="max-h-96 overflow-auto">
                            <table className="w-full text-left text-sm text-gray-600">
                                <thead className="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr className="sticky top-0">
                                        <th className="bg-gray-50 px-5 py-3">#</th>
                                        <th className="bg-gray-50 px-5 py-3">Nama</th>
                                        <th className="bg-gray-50 px-5 py-3">NIP</th>
                                        <th className="bg-gray-50 px-5 py-3">Waktu Pengisian</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {recap.respondents.map((row, index) => (
                                        <tr key={index}>
                                            <td className="px-5 py-3">{index + 1}</td>
                                            <td className="px-5 py-3 font-medium text-gray-800">{row.name}</td>
                                            <td className="px-5 py-3">{row.nip || "-"}</td>
                                            <td className="px-5 py-3">{row.submitted_at || "-"}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </App>
    );
};

export default Result;
