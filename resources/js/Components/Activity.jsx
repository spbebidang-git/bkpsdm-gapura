import { router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { toast } from "react-toastify";

const Activity = ({ id, type, data, slug, question_result, status, answer, setAnswers, file, setFile, schedule }) => {
    const [submitted, setSubmitted] = useState(status);

    console.log(123 + status)

    const [duration, setDuration] = useState(data.duration)
    const [activeButton, setActiveButton] = useState(false)

    const [questions, setQuestions] = useState([]);
    const { errors } = usePage().props


    useEffect(() => {

        if (type === 'pre-test' || type === 'post-test') {
            if (data.mix === 1) {
                // Fungsi untuk mengacak array
                const shuffleArray = (array) => {
                    return array.sort(() => Math.random() - 0.5);
                };

                // Mengacak pertanyaan
                const shuffledQuestions = shuffleArray([...data.question_items]);

                // Mengacak jawaban untuk setiap pertanyaan
                const shuffledQuestionsWithOptions = shuffledQuestions.map(item => {
                    const shuffledOptions = shuffleArray(Object.entries(item.option));
                    return {
                        ...item,
                        option: Object.fromEntries(shuffledOptions)
                    };
                });

                setQuestions(shuffledQuestionsWithOptions);
            }
            else {
                setQuestions(data.question_items);
            }
        }

        if (type === 'video' || type === 'literature') {
            // setDuration(data.duration)
            if (duration === 0) {
                setActiveButton(true)
                return;
            }
            // Atur interval untuk menghitung mundur
            const interval = setInterval(() => {
                setDuration(prevDuration => {
                    if (prevDuration > 1) {
                        return prevDuration - 1;
                    } else {
                        clearInterval(interval); // Hentikan interval saat durasi mencapai 0
                        setActiveButton(true); // Aktifkan tombol
                        return 0;
                    }
                });
            }, 1000);

            // Bersihkan interval ketika komponen unmount
            return () => clearInterval(interval);
        }
    }, [data, duration]);

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

        return (
            <div className="">
                {questions.map((item, index) => {
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
                                {/* <label
                                    htmlFor={`option-${index}-${index2}`}
                                    className="flex text-sm xl:text-base gap-4 p-2 xl:p-3 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100"
                                >
                                    <div className="font-bold border-2 rounded-full text-sm w-8 h-8 flex items-center justify-center uppercase">
                                        {key}
                                    </div>
                                    <div className="block">
                                        <div className="w-full">{value}</div>
                                    </div>
                                </label> */}
                                <label
                                    htmlFor={`option-${index}-${index2}`}
                                    className="flex text-sm xl:text-base gap-4 p-3 xl:p-4 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100 transition-all"
                                >
                                    {/* 🔹 HURUF ABCD SUDAH DIHAPUS DI SINI 🔹 */}
                                    <div className="block">
                                        <div className="w-full">{value}</div>
                                    </div>
                                </label>
                            </li>
                        );
                    });

                    return (
                        <div className="mb-5 xl:mb-8 px-5" key={index}>
                            <h3 className="mb-3 text-lg font-medium text-gray-800 dark:text-white">
                                {index + 1 + '. ' + item.question}
                            </h3>
                            <ul className="grid w-full gap-4 md:grid-cols-1">
                                {option}
                            </ul>
                        </div>
                    );
                })}
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

            // const combinedData = {
            //     formattedAnswers,
            //     captcha: captchaInput
            // };

            router.post(`/learn/${slug}/1/${id}`, formattedAnswers, {
                onSuccess: () => {
                    setSubmitted('done'); // Ubah state menjadi "done" setelah berhasil submit
                },
            });
        };

        const handleSubmitNext = () => {

            if (question_result.score < data.min_score) {
                toast.error('Kamu belum memenuhi nilai yang ditetapkan. Silakan ulangi post-test untuk mencoba lagi', {
                    position: "top-center",
                    autoClose: 5000,
                    hideProgressBar: false,
                    closeOnClick: true,
                    pauseOnHover: true,
                    draggable: true,
                    theme: "colored",
                });
            } else {
                setSubmitted(false);
                setAnswers({}); // Reset jawaban sebelum submit berikutnya
                router.post(`/learn/${slug}/1/${id}`, []);
            }

        };

        const handleLoop = () => {
            router.post(`/learn/${slug}/1/${id}`, { loop: 1 }, {
                onSuccess: () => {
                    setSubmitted(false);
                    setAnswers({}); // Reset jawaban saat mulai ulang
                },
            });
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
                            <div className="flex text-center justify-center gap-8 text-xl mb-4">
                                <div>
                                    <div>Benar</div>
                                    <div>{question_result.correct}</div>
                                </div>
                                <div>
                                    <div>Salah</div>
                                    <div>{question_result.wrong}</div>
                                </div>
                            </div>
                            <div className="text-center text-xl font-bold">Nilai Kamu : {question_result.score}</div>
                        </div>
                    )}
                    {data.is_loop === 1 ? (
                        <div className="w-full max-w-sm flex mx-auto mt-6">
                            <div className="flex px-5 xl:px-0 items-center gap-3 w-full">
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
                            <button onClick={handleSubmitNext} className="button-primary max-w-56">
                                Selanjutnya
                            </button>
                        </div>
                    )}
                </div>
            );
        }

        return (
            <div className="">
                {questions.map((item, index) => {
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
                                    className="flex text-sm xl:text-base gap-4 p-2 xl:p-3 items-center w-full text-gray-500 bg-white border border-gray-200 rounded-xl cursor-pointer peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white hover:text-gray-600 hover:bg-gray-100"
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
                        <div className="mb-5 xl:mb-8 px-5" key={index}>
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

        return (
            <div className="h-full p-5 xl:p-8">
                <div className="flex items-center flex-col">
                    <div className="mb-5">
                        <div className="text-gray-600 text-center">{data.description}</div>
                    </div>

                    <div className="mb-5 w-full max-w-xl">
                        <div className="flex items-center justify-center w-full">
                            <label htmlFor="thumbnail" className={`flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100`}>
                                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg className="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                        <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                                    </svg>
                                    <p className="mb-2 text-sm text-gray-500"><span className="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p className="text-xs text-gray-500">(Format: PDF, XLS, JPG, DOC (Maks. 2MB) )</p>
                                </div>
                                <input id="thumbnail" type="file" className="hidden" onChange={handleFileChange} />
                            </label>
                        </div>
                        {file && (
                            <p className="mt-2 text-sm text-green-500">File selected: {file.name}</p>
                        )}
                        {errors.file && <small className="text-red-500 text-sm mt-1">{errors.file}</small>}
                    </div>
                </div>
            </div>
        );
    }

    if (type === 'meeting') {
        const handleSubmit = () => {
            router.post(`/learn/${slug}/${schedule.id}/${id}`, { status: 'in_progress' })
        }

        const { flash } = usePage().props

        {
            flash.danger && (
                <div className="bg-red-500 text-white p-3 rounded">
                    {flash.danger}
                </div>
            )
        }

        const handleSubmitDone = () => {
            console.log(schedule)
            router.post(`/learn/${slug}/${schedule.id}/${id}`, {
                status: 'done',
                // captcha: captchaInput
            }, {
                // onSuccess: page => {
                //     // console.log(page);
                //     toast.success('Meeting telah berakhir.', {
                //         position: "top-right",
                //         autoClose: 3000,
                //         hideProgressBar: false,
                //         closeOnClick: true,
                //         pauseOnHover: true,
                //         draggable: true,
                //         progress: undefined,
                //         theme: "colored",
                //     });
                // },

            })
        }

        // const handleSubmitNext = () => {
        //     router.post(`/learn/${slug}/1/${id}`, [])
        // }


        return <div className="h-full text-gray-700 text-center">
            <div className="mb-5">Klik link online meeting di bawah : </div>
            <a href={data.meeting_url} target="_blank" className="block text-blue-500 text-xl mb-5">
                {data.meeting_url}
            </a>


            {status === 'done' ? (
                <div className="mb-6">
                    Meeting Online "Selesai". <br /> Terimakasih telah mengikuti sesi Meeting Online.
                </div>
            ) : status === 'in_progress' ? (
                <div>
                    <div className="mb-6">
                        Meeting Online "Sedang Berlangsung". <br /> Tunggu informasi dari pembicara untuk mengakhiri Meeting Online.
                    </div>
                </div>
            ) : (
                <div className="mb-6">
                    Pastikan anda sudah bergabung didalam online meeting, lalu tekan tombol "Mulai" di bawah.
                </div>
            )}



            <div className="w-full max-w-sm mx-auto flex justify-center mt-6">
                {status === 'done' ? (
                    ''
                    // <div>

                    //     <button onClick={handleSubmitNext} className="button-primary">
                    //         Selanjutnya
                    //     </button>
                    // </div>
                ) : status === 'in_progress' ? (
                    <div>
                        <button onClick={handleSubmitDone} className="button-primary">
                            Akhiri Meeting
                        </button>
                    </div>
                ) : (
                    <div>
                        <button onClick={handleSubmit} className="button-primary">
                            Mulai
                        </button>
                    </div>
                )}
            </div>
        </div>;
    }

    if (type === 'video') {
        const handleSubmit = () => {
            // setActiveButton(false)
            // console.log(answers)
            // const formattedAnswers = Object.values(answers);
            router.post(`/learn/${slug}/1/${id}`)
        }

        return (
            <div className="flex flex-col items-center rounded-xl overflow-hidden p-6">
                <iframe
                    className="xl:w-[912px] xl:h-[512px] aspect-video shadow"
                    src={`${data.src_video}?rel=0`}
                    title="YouTube video player"
                    frameBorder="0"
                    allowfullscreen="true"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope;"

                ></iframe>
            </div>

        )
    }

    if (type === 'literature') {
        const handleSubmit = () => {
            // console.log(answers)
            // const formattedAnswers = Object.values(answers);
            router.post(`/learn/${slug}/1/${id}`)
        }
        return (
            <div className="pdf-container">
                <iframe
                    src={`${data.src_pdf}`}
                    width="100%"
                    height="600px"
                    title="PDF Viewer"
                ></iframe>
                {/* <div className="w-full flex justify-center mt-6">
                    {activeButton ? <button onClick={handleSubmit} className="button-primary max-w-56">
                        Selanjutnya
                    </button> : <button className="button-disable max-w-56">
                        Selanjutnya
                    </button>}
                </div> */}
            </div>
        )
    }

    return null;
};


export default Activity;
