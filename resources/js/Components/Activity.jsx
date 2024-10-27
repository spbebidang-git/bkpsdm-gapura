import { Link, router } from "@inertiajs/react";
import { useState } from "react";
import { pdfjs } from 'react-pdf';
import 'videojs-youtube'; // Import plugin YouTube

pdfjs.GlobalWorkerOptions.workerSrc = `//unpkg.com/pdfjs-dist@${pdfjs.version}/build/pdf.worker.min.mjs`;

const Activity = ({ id, type, data, slug, question_result, status }) => {
    // State untuk menyimpan jawaban
    const [answers, setAnswers] = useState({});
    const [submitted, setSubmitted] = useState(status);
    // State untuk menyimpan file
    const [file, setFile] = useState(null);

    if (type === 'pre-test') {
        const handleAnswerChange = (index, answer, questionId) => {
            setAnswers(prevAnswers => ({
                ...prevAnswers,
                [index]: {
                    question: data.question_items[index].question,
                    option_select: answer,
                    question_id: questionId
                }
            }));
        };

        const handleSubmit = () => {
            const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));
            router.post(`/learn/${slug}/1/${id}`, formattedAnswers, {
                onSuccess: () => {
                    setSubmitted('done'); // Ubah state menjadi "done" setelah berhasil submit
                },
            });
        };

        const handleSubmitNext = () => {
            setSubmitted(false);
            setAnswers({}); // Reset jawaban sebelum submit berikutnya
            router.post(`/learn/${slug}/1/${id}`, []);
        };

        const handleLoop = () => {
            setSubmitted(false);
            setAnswers({}); // Reset jawaban saat mulai ulang
        };

        if (submitted === 'done') {
            // Jika sudah submit, tampilkan hasil
            return (
                <div className="">
                    {question_result && (
                        <div>
                            <div className="text-center text-2xl font-semibold mb-5">
                                Selamat, kamu telah menyelesaikan tes ini.
                            </div>
                            <div className="flex text-center justify-center gap-8 text-xl">
                                <div>
                                    <div>Benar</div>
                                    <div>{question_result.correct}</div>
                                </div>
                                <div>
                                    <div>Salah</div>
                                    <div>{question_result.wrong}</div>
                                </div>
                            </div>
                        </div>
                    )}
                    {data.is_loop === 1 ? (
                        <div className="w-full max-w-sm flex mx-auto mt-6">
                            <div className="flex gap-3 w-full">
                                <div className="w-full">
                                    <button onClick={handleLoop} className="button-primary max-w-56">
                                        Ulangi
                                    </button>
                                </div>
                                <div className="w-full">
                                    <button onClick={handleSubmitNext} className="button-primary max-w-56">
                                        Selanjutnya
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="w-full flex justify-center mt-6">
                            <button onClick={handleSubmit} className="button-primary max-w-56">
                                Selanjutnya
                            </button>
                        </div>
                    )}
                </div>
            );
        }

        return (
            <div>
                {data.question_items.map((item, index) => {
                    const option = Object.entries(item.option).map(([key, value], index2) => {
                        return (
                            <li key={index2}>
                                <input
                                    type="radio"
                                    id={`option-${index}-${index2}`}
                                    name={`option-${index}`}
                                    value={key}
                                    className="hidden peer"
                                    required
                                    onChange={() => handleAnswerChange(index, value, item.question_id)}
                                />
                                <label
                                    htmlFor={`option-${index}-${index2}`}
                                    className="flex gap-4 p-3 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100"
                                >
                                    <div className="font-bold border-2 rounded-full text-sm w-8 h-8 flex items-center justify-center uppercase">
                                        {key}
                                    </div>
                                    <div className="block">
                                        <div className="w-full">{value}</div>
                                    </div>
                                </label>
                            </li>
                        );
                    });

                    return (
                        <div className="mb-8 px-5" key={index}>
                            <h3 className="mb-3 text-lg font-medium text-gray-800 dark:text-white">
                                {index + 1 + '. ' + item.question}
                            </h3>
                            <ul className="grid w-full gap-4 md:grid-cols-1">
                                {option}
                            </ul>
                        </div>
                    );
                })}

                <div className="w-full flex justify-center mt-6">
                    <button onClick={handleSubmit} className="button-primary max-w-56">
                        Kirim Jawaban
                    </button>
                </div>
            </div>
        );
    }

    if (type === 'post-test') {
        const handleAnswerChange = (index, answer, questionId) => {
            setAnswers(prevAnswers => ({
                ...prevAnswers,
                [index]: {
                    question: data.question_items[index].question,
                    option_select: answer,
                    question_id: questionId
                }
            }));
        };

        const handleSubmit = () => {
            const formattedAnswers = Object.values(answers).map(answer => ({ ...answer }));
            router.post(`/learn/${slug}/1/${id}`, formattedAnswers, {
                onSuccess: () => {
                    setSubmitted('done'); // Ubah state menjadi "done" setelah berhasil submit
                },
            });
        };

        const handleSubmitNext = () => {
            setSubmitted(false);
            setAnswers({}); // Reset jawaban sebelum submit berikutnya
            router.post(`/learn/${slug}/1/${id}`, []);
        };

        const handleLoop = () => {
            setSubmitted(false);
            setAnswers({}); // Reset jawaban saat mulai ulang
        };

        if (submitted === 'done') {
            // Jika sudah submit, tampilkan hasil
            return (
                <div className="">
                    {question_result && (
                        <div>
                            <div className="text-center text-2xl font-semibold mb-5">
                                Selamat, kamu telah menyelesaikan tes ini.
                            </div>
                            <div className="flex text-center justify-center gap-8 text-xl">
                                <div>
                                    <div>Benar</div>
                                    <div>{question_result.correct}</div>
                                </div>
                                <div>
                                    <div>Salah</div>
                                    <div>{question_result.wrong}</div>
                                </div>
                            </div>
                        </div>
                    )}
                    {data.is_loop === 1 ? (
                        <div className="w-full max-w-sm flex mx-auto mt-6">
                            <div className="flex gap-3 w-full">
                                <div className="w-full">
                                    <button onClick={handleLoop} className="button-primary max-w-56">
                                        Ulangi
                                    </button>
                                </div>
                                <div className="w-full">
                                    <button onClick={handleSubmitNext} className="button-primary max-w-56">
                                        Selanjutnya
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="w-full flex justify-center mt-6">
                            <button onClick={handleSubmit} className="button-primary max-w-56">
                                Selanjutnya
                            </button>
                        </div>
                    )}
                </div>
            );
        }

        return (
            <div>
                {data.question_items.map((item, index) => {
                    const option = Object.entries(item.option).map(([key, value], index2) => {
                        return (
                            <li key={index2}>
                                <input
                                    type="radio"
                                    id={`option-${index}-${index2}`}
                                    name={`option-${index}`}
                                    value={key}
                                    className="hidden peer"
                                    required
                                    onChange={() => handleAnswerChange(index, value, item.question_id)}
                                />
                                <label
                                    htmlFor={`option-${index}-${index2}`}
                                    className="flex gap-4 p-3 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100"
                                >
                                    <div className="font-bold border-2 rounded-full text-sm w-8 h-8 flex items-center justify-center uppercase">
                                        {key}
                                    </div>
                                    <div className="block">
                                        <div className="w-full">{value}</div>
                                    </div>
                                </label>
                            </li>
                        );
                    });

                    return (
                        <div className="mb-8 px-5" key={index}>
                            <h3 className="mb-3 text-lg font-medium text-gray-800 dark:text-white">
                                {index + 1 + '. ' + item.question}
                            </h3>
                            <ul className="grid w-full gap-4 md:grid-cols-1">
                                {option}
                            </ul>
                        </div>
                    );
                })}

                <div className="w-full flex justify-center mt-6">
                    <button onClick={handleSubmit} className="button-primary max-w-56">
                        Kirim Jawaban
                    </button>
                </div>
            </div>
        );
    }

    if (type === 'task') {

        // Fungsi untuk menangani perubahan file
        const handleFileChange = (e) => {
            setFile(e.target.files[0]);
        };

        // Fungsi submit
        const handleSubmit = () => {
            if (!file) {
                alert("Please select a file first");
                return;
            }

            // Membuat FormData
            const formData = new FormData();
            formData.append('file', file);  // Tambahkan file ke formData
            formData.append('id', id);  // Tambahkan data lain jika diperlukan

            // Kirim FormData menggunakan Inertia router
            router.post(`/learn/${slug}/1/${id}`, formData, {
                forceFormData: true, // Pastikan formData dikirim sebagai multipart/form-data
                onSuccess: () => {
                    alert("File uploaded successfully");
                },
                onError: () => {
                    alert("File upload failed");
                }
            });
        };

        return (
            <div className="h-full p-8">
                <div className="flex items-center flex-col">
                    <div className="text-xl font-bold text-gray-700 capitalize">title</div>
                    <div className="text-gray-600">description</div>

                    <div className="mb-3 w-full max-w-xl">
                        <div className="flex items-center justify-center w-full">
                            <label htmlFor="thumbnail" className={`flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100`}>
                                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg className="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                        <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                                    </svg>
                                    <p className="mb-2 text-sm text-gray-500"><span className="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p className="text-xs text-gray-500">SVG, PNG, JPG or GIF (MAX. 800x400px)</p>
                                </div>
                                <input id="thumbnail" type="file" className="hidden" onChange={handleFileChange} />
                            </label>
                        </div>
                    </div>
                </div>
                <div className="w-full flex justify-center mt-6">
                    <button onClick={handleSubmit} className="button-primary max-w-56">
                        Submit
                    </button>
                </div>
            </div>
        );
    }

    if (type === 'meeting') {
        const handleSubmit = () => {
            router.post(`/learn/${slug}/1/${id}`, { status: 'in_progress' })
        }

        const handleSubmitDone = () => {
            router.post(`/learn/${slug}/1/${id}`, {
                status: 'done'
            })
        }

        const handleSubmitNext = () => {
            router.post(`/learn/${slug}/1/${id}`, [])
        }

        return <div className="h-full text-gray-700 text-center">
            <div className="mb-5">Klik link online meeting di bawah : </div>
            <a href={data.meeting_url} className="block text-blue-500 text-xl mb-5">
                {data.meeting_url}
            </a>
            <div className="mb-6">
                Pastikan anda sudah bergabung didalam online meeting, lalu tekan tombol "Mulai" di bawah.
            </div>

            {status === 'done' ? (
                <Link className="button-primary">
                    Meeting Selesai
                </Link>
            ) : status === 'in_progress' ? (
                <div className="button-primary">
                    Sedang Berlangsung
                </div>
            ) : (
                ''
            )}

            <div className="w-full max-w-sm mx-auto flex justify-center mt-6">
                {status === 'done' ? (
                    <button onClick={handleSubmitNext} className="button-primary">
                        Selanjutnya
                    </button>
                ) : status === 'in_progress' ? (
                    <button onClick={handleSubmitDone} className="button-primary">
                        Selesaikan Meeting
                    </button>
                ) : (
                    <button onClick={handleSubmit} className="button-primary">
                        Mulai
                    </button>
                )}
            </div>
        </div>;
    }

    if (type === 'video') {
        const handleSubmit = () => {
            // console.log(answers)
            // const formattedAnswers = Object.values(answers);
            router.post(`/learn/${slug}/1/${id}`, [])
        }

        return (
            <div className="flex flex-col items-center rounded-xl overflow-hidden p-6">
                <iframe
                    width="1024px"
                    height="512px"
                    className="aspect-video"
                    src={`${data.src_video}`}
                    title="YouTube video player"
                    frameBorder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"

                ></iframe>

                <div className="w-full flex justify-center mt-6">
                    <button onClick={handleSubmit} className="button-primary max-w-56">
                        Selanjutnya
                    </button>
                </div>
            </div>
        )
    }

    if (type === 'literature') {
        const handleSubmit = () => {
            // console.log(answers)
            // const formattedAnswers = Object.values(answers);
            router.post(`/learn/${slug}/1/${id}`, [])
        }
        return (
            <div className="pdf-container">
                <iframe
                    src={`${data.src_pdf}`}
                    width="100%"
                    height="600px"
                    title="PDF Viewer"
                ></iframe>

                <div className="w-full flex justify-center mt-6">
                    <button onClick={handleSubmit} className="button-primary max-w-56">
                        Selanjutnya
                    </button>
                </div>
            </div>
        )
    }

    return null;
};


export default Activity;
