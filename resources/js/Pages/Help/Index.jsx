import App from "@/Layouts/App";
import { Head, usePage } from "@inertiajs/react";
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

            <div className="max-w-4xl mx-auto p-6">
                <h1 className="text-2xl font-semibold mb-6 text-gray-800">
                    Bantuan (Q&A)
                </h1>

                <div className="mb-6">
                    <input
                        type="text"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Cari pertanyaan..."
                        className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-blue-200 focus:outline-none"
                    />
                </div>

                <div className="space-y-3">
                    {filtered.length === 0 && (
                        <div className="p-4 bg-white border rounded text-center text-gray-500">
                            Tidak ada hasil.
                        </div>
                    )}

                    {filtered.map((item) => (
                        <details
                            key={item.id}
                            className="bg-white border rounded-lg shadow-sm p-4 transition-all"
                        >
                            <summary className="cursor-pointer font-medium text-gray-800">
                                {item.question}
                            </summary>
                            <div className="mt-2 text-gray-600 whitespace-pre-line">
                                {item.answer}
                            </div>
                        </details>
                    ))}
                </div>
            </div>
        </App>
    );
};

export default Index;
