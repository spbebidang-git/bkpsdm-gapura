import App from "@/Layouts/App";
import { Head, usePage } from "@inertiajs/react";
import DOMPurify from "dompurify";
import { useState } from "react";

const Index = () => {
    const { helps } = usePage().props;
    const [q, setQ] = useState("");

    const filtered = (helps || []).filter((item) => {
        const text = (item.question + " " + item.answer).toLowerCase();
        return text.includes(q.toLowerCase());
    });

    return (
        <App>
            <Head title="Bantuan" />

            <div className="max-w-4xl mx-auto py-12 px-6">
                <div className="text-center mb-10">
                    <h1 className="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight mb-4">
                        Pusat Bantuan (Q&A)
                    </h1>
                    <p className="text-lg text-gray-500">Temukan jawaban atas pertanyaan yang sering diajukan seputar layanan kami.</p>
                </div>

                <div className="mb-10 relative max-w-2xl mx-auto">
                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg className="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari pertanyaan..."
                        className="w-full border border-gray-200 rounded-full pl-11 pr-4 py-3.5 shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary focus:outline-none transition-all duration-300 text-gray-700 bg-white"
                    />
                </div>

                <div className="space-y-4 max-w-3xl mx-auto">
                    {filtered.length === 0 && (
                        <div className="p-8 bg-gray-50 border border-gray-100 rounded-2xl text-center text-gray-500">
                            <svg className="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p className="text-lg font-medium text-gray-600">Jawaban tidak ditemukan.</p>
                            <p className="text-sm">Silakan gunakan kata kunci lain untuk mencari.</p>
                        </div>
                    )}

                    {filtered.map((item) => (
                        <details
                            key={item.id}
                            className="bg-white border border-gray-100 rounded-xl shadow-sm p-6 transition-all duration-300 hover:shadow-md group relative overflow-hidden"
                        >
                            <summary className="cursor-pointer font-semibold text-gray-800 text-lg flex items-center justify-between outline-none list-none marker:hidden">
                                <span className="group-hover:text-primary transition-colors">{item.question}</span>
                                <span className="text-gray-400 group-hover:text-primary group-open:rotate-180 transition-all duration-300 ml-4 flex-shrink-0">
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </summary>
                            <div
                                className="prose prose-sm sm:prose-base max-w-none mt-4 pt-4 border-t border-gray-100 text-gray-600 leading-relaxed text-base prose-a:text-primary prose-a:font-medium hover:prose-a:text-primary/80"
                                dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(item.answer || "") }}
                            />
                        </details>
                    ))}
                </div>
            </div>
        </App>
    );
};

export default Index;
